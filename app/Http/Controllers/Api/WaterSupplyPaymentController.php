<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterSupplyPayment;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class WaterSupplyPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = WaterSupplyPayment::with(['reading', 'customer', 'bankAccount']);
        
        if ($request->has('reading_id')) {
            $query->where('reading_id', $request->reading_id);
        }
        
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        $payments = $query->get();
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reading_id' => 'required|exists:water_supply_readings,id',
            'customer_id' => 'required|exists:water_supply_customers,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'sometimes|in:cash,bank_transfer,check,card',
            'reference' => 'nullable|string|max:255',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'bank_receipt' => 'nullable|string|max:255',
        ]);

        // Set payment method to bank_transfer if not provided
        if (!isset($validated['payment_method'])) {
            $validated['payment_method'] = 'bank_transfer';
        }

        $payment = WaterSupplyPayment::create($validated);
        
        // Update reading payment status
        $reading = $payment->reading;
        $reading->update([
            'payment_status' => 'paid',
            'payment_date' => $validated['payment_date'],
        ]);

        // Create bank transaction for the payment
        if (isset($validated['bank_account_id'])) {
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            $customer = $payment->customer;
            
            $description = "Water supply payment from {$customer->name}";
            if ($validated['bank_receipt']) {
                $description .= " (Receipt: {$validated['bank_receipt']})";
            }
            
            $previousBalance = $bankAccount->opening_balance;
            
            $bankTransaction = BankTransaction::create([
                'account_id' => $validated['bank_account_id'],
                'type' => 'deposit',
                'amount' => $validated['amount'],
                'previous_balance' => $previousBalance,
                'date' => $validated['payment_date'],
                'description' => $description,
                'category' => 'Water Supply Income',
                'reference' => $validated['bank_receipt'] ?? null,
            ]);

            // Update bank account balance (add the deposit amount)
            $bankAccount->increment('opening_balance', $validated['amount']);
        }
        
        $payment->load('reading', 'customer', 'bankAccount');
        return response()->json($payment, 201);
    }

    public function show(WaterSupplyPayment $waterSupplyPayment)
    {
        $waterSupplyPayment->load('reading', 'customer', 'bankAccount');
        return response()->json($waterSupplyPayment);
    }

    public function update(Request $request, WaterSupplyPayment $waterSupplyPayment)
    {
        $validated = $request->validate([
            'reading_id' => 'sometimes|required|exists:water_supply_readings,id',
            'customer_id' => 'sometimes|required|exists:water_supply_customers,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|in:cash,bank_transfer,check,card',
            'reference' => 'nullable|string|max:255',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'bank_receipt' => 'nullable|string|max:255',
        ]);

        $oldAmount = $waterSupplyPayment->amount;
        $oldBankAccountId = $waterSupplyPayment->bank_account_id;
        $oldPaymentDate = $waterSupplyPayment->payment_date;
        $oldPaymentMethod = $waterSupplyPayment->payment_method;

        $waterSupplyPayment->update($validated);

        // Handle bank transaction updates
        if (isset($validated['bank_account_id'])) {
            // Find and update/delete the associated bank transaction
            $bankTransaction = BankTransaction::where('account_id', $oldBankAccountId)
                ->where('date', $oldPaymentDate)
                ->where('category', 'Water Supply Income')
                ->where('amount', $oldAmount)
                ->first();

            if ($bankTransaction) {
                // Revert old transaction (subtract from old account)
                $oldBankAccount = BankAccount::find($oldBankAccountId);
                if ($oldBankAccount) {
                    $oldBankAccount->decrement('opening_balance', $oldAmount);
                }

                // Update or create new transaction
                $newBankAccountId = $validated['bank_account_id'];
                $newAmount = $validated['amount'] ?? $oldAmount;
                $newPaymentDate = $validated['payment_date'] ?? $oldPaymentDate;
                
                $newBankAccount = BankAccount::findOrFail($newBankAccountId);
                $customer = $waterSupplyPayment->customer;
                
                $description = "Water supply payment from {$customer->name}";
                $bankReceipt = $validated['bank_receipt'] ?? $waterSupplyPayment->bank_receipt;
                if ($bankReceipt) {
                    $description .= " (Receipt: {$bankReceipt})";
                }

                $previousBalance = $newBankAccount->opening_balance;
                
                if ($bankTransaction->account_id == $newBankAccountId) {
                    // Same account, just update the transaction
                    $bankTransaction->update([
                        'amount' => $newAmount,
                        'previous_balance' => $previousBalance,
                        'date' => $newPaymentDate,
                        'description' => $description,
                        'reference' => $bankReceipt,
                    ]);
                } else {
                    // Different account, delete old and create new
                    $bankTransaction->delete();
                    $bankTransaction = BankTransaction::create([
                        'account_id' => $newBankAccountId,
                        'type' => 'deposit',
                        'amount' => $newAmount,
                        'previous_balance' => $previousBalance,
                        'date' => $newPaymentDate,
                        'description' => $description,
                        'category' => 'Water Supply Income',
                        'reference' => $bankReceipt,
                    ]);
                }

                // Add new amount to new account
                $newBankAccount->increment('opening_balance', $newAmount);
            }
        } elseif ($oldBankAccountId) {
            // Bank account was removed, revert the transaction
            $bankTransaction = BankTransaction::where('account_id', $oldBankAccountId)
                ->where('date', $oldPaymentDate)
                ->where('category', 'Water Supply Income')
                ->where('amount', $oldAmount)
                ->first();

            if ($bankTransaction) {
                $oldBankAccount = BankAccount::find($oldBankAccountId);
                if ($oldBankAccount) {
                    $oldBankAccount->decrement('opening_balance', $oldAmount);
                }
                $bankTransaction->delete();
            }
        }

        $waterSupplyPayment->load('reading', 'customer', 'bankAccount');
        return response()->json($waterSupplyPayment);
    }

    public function destroy(WaterSupplyPayment $waterSupplyPayment)
    {
        // Revert the bank transaction if it exists
        if ($waterSupplyPayment->bank_account_id) {
            $bankTransaction = BankTransaction::where('account_id', $waterSupplyPayment->bank_account_id)
                ->where('date', $waterSupplyPayment->payment_date)
                ->where('category', 'Water Supply Income')
                ->where('amount', $waterSupplyPayment->amount)
                ->first();

            if ($bankTransaction) {
                // Revert the bank account balance (subtract the deposit amount)
                $bankAccount = BankAccount::find($waterSupplyPayment->bank_account_id);
                if ($bankAccount) {
                    $bankAccount->decrement('opening_balance', $waterSupplyPayment->amount);
                }
                $bankTransaction->delete();
            }
        }

        $waterSupplyPayment->delete();
        return response()->json(['message' => 'Water supply payment deleted successfully']);
    }
}
