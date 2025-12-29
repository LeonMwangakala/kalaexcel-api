<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentPayment;
use App\Models\Contract;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class RentPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = RentPayment::with(['tenant', 'contract.property', 'bankAccount']);
        
        // Add search functionality if needed
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->whereHas('tenant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        $payments = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $payments->items(),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
            'from' => $payments->firstItem(),
            'to' => $payments->lastItem(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'bank_receipt' => 'nullable|string|max:255', // Receipt number
        ]);

        // Get the contract to determine tenant and rent amount
        $contract = Contract::with(['tenant', 'property'])->findOrFail($validated['contract_id']);

        // Calculate month from payment date (YYYY-MM format)
        $paymentDate = \Carbon\Carbon::parse($validated['payment_date']);
        $month = $paymentDate->format('Y-m');

        // Calculate status based on amount vs contract rent amount
        $status = 'pending';
        if ($validated['amount'] >= $contract->rent_amount) {
            $status = 'paid';
        } elseif ($validated['amount'] > 0) {
            $status = 'partial';
        }

        // Create payment record
        $payment = RentPayment::create([
            'tenant_id' => $contract->tenant_id,
            'contract_id' => $validated['contract_id'],
            'bank_account_id' => $validated['bank_account_id'],
            'amount' => $validated['amount'],
            'month' => $month,
            'payment_date' => $validated['payment_date'],
            'payment_method' => 'bank_transfer',
            'bank_receipt' => $validated['bank_receipt'] ?? null,
            'status' => $status,
        ]);

        // Create bank transaction (deposit) for this rent payment
        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $tenant = $contract->tenant;
        $property = $contract->property;
        
        $description = "Rent payment from {$tenant->name}";
        if ($property) {
            $description .= " for {$property->name}";
        }
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
            'category' => 'Rent Income',
            'reference' => $validated['bank_receipt'] ?? null,
        ]);

        // Update bank account balance (add the deposit amount)
        $bankAccount->increment('opening_balance', $validated['amount']);

        $payment->load(['tenant', 'contract.property', 'bankAccount']);

        return response()->json($payment, 201);
    }

    public function show(RentPayment $rentPayment)
    {
        $rentPayment->load(['tenant', 'contract.property', 'bankAccount']);
        return response()->json($rentPayment);
    }

    public function update(Request $request, RentPayment $rentPayment)
    {
        $validated = $request->validate([
            'contract_id' => 'sometimes|required|exists:contracts,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'bank_account_id' => 'sometimes|required|exists:bank_accounts,id',
            'bank_receipt' => 'nullable|string|max:255', // Receipt number
        ]);

        // Store old values for bank transaction update
        $oldAmount = $rentPayment->amount;
        $oldBankAccountId = $rentPayment->bank_account_id;
        $oldPaymentDate = $rentPayment->payment_date;

        // If payment_date changed, recalculate month
        if (isset($validated['payment_date'])) {
            $paymentDate = \Carbon\Carbon::parse($validated['payment_date']);
            $validated['month'] = $paymentDate->format('Y-m');
        }

        // If contract_id or amount changed, recalculate status
        if (isset($validated['contract_id']) || isset($validated['amount'])) {
            $contractId = $validated['contract_id'] ?? $rentPayment->contract_id;
            $contract = Contract::findOrFail($contractId);
            $amount = $validated['amount'] ?? $rentPayment->amount;
            
            if ($amount >= $contract->rent_amount) {
                $validated['status'] = 'paid';
            } elseif ($amount > 0) {
                $validated['status'] = 'partial';
            } else {
                $validated['status'] = 'pending';
            }
        }

        // Update tenant_id if contract changed
        if (isset($validated['contract_id'])) {
            $contract = Contract::findOrFail($validated['contract_id']);
            $validated['tenant_id'] = $contract->tenant_id;
        }

        $rentPayment->update($validated);
        
        // Find and update the associated bank transaction
        $bankTransaction = BankTransaction::where('account_id', $oldBankAccountId)
            ->where('date', $oldPaymentDate)
            ->where('category', 'Rent Income')
            ->where('amount', $oldAmount)
            ->first();

        if ($bankTransaction) {
            $oldBankAccount = BankAccount::find($oldBankAccountId);
            $newBankAccountId = $validated['bank_account_id'] ?? $oldBankAccountId;
            $newAmount = $validated['amount'] ?? $oldAmount;
            $newPaymentDate = $validated['payment_date'] ?? $oldPaymentDate;

            // Revert old transaction (subtract from old account)
            if ($oldBankAccount) {
                $oldBankAccount->decrement('opening_balance', $oldAmount);
            }

            // Update or create new transaction
            $newBankAccount = BankAccount::findOrFail($newBankAccountId);
            $contract = Contract::with(['tenant', 'property'])->findOrFail($rentPayment->contract_id);
            $tenant = $contract->tenant;
            $property = $contract->property;
            
            $description = "Rent payment from {$tenant->name}";
            if ($property) {
                $description .= " for {$property->name}";
            }
            $bankReceipt = $validated['bank_receipt'] ?? $rentPayment->bank_receipt;
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
                    'category' => 'Rent Income',
                    'reference' => $bankReceipt,
                ]);
            }

            // Add new amount to new account
            $newBankAccount->increment('opening_balance', $newAmount);
        } else {
            // No existing transaction found, create a new one
            $newBankAccountId = $validated['bank_account_id'] ?? $oldBankAccountId;
            $newAmount = $validated['amount'] ?? $oldAmount;
            $newPaymentDate = $validated['payment_date'] ?? $oldPaymentDate;
            
            $newBankAccount = BankAccount::findOrFail($newBankAccountId);
            $previousBalance = $newBankAccount->opening_balance;
            $contract = Contract::with(['tenant', 'property'])->findOrFail($rentPayment->contract_id);
            $tenant = $contract->tenant;
            $property = $contract->property;
            
            $description = "Rent payment from {$tenant->name}";
            if ($property) {
                $description .= " for {$property->name}";
            }
            $bankReceipt = $validated['bank_receipt'] ?? $rentPayment->bank_receipt;
            if ($bankReceipt) {
                $description .= " (Receipt: {$bankReceipt})";
            }

            BankTransaction::create([
                'account_id' => $newBankAccountId,
                'type' => 'deposit',
                'amount' => $newAmount,
                'previous_balance' => $previousBalance,
                'date' => $newPaymentDate,
                'description' => $description,
                'category' => 'Rent Income',
                'reference' => $bankReceipt,
            ]);

            $newBankAccount->increment('opening_balance', $newAmount);
        }

        $rentPayment->load(['tenant', 'contract.property', 'bankAccount']);

        return response()->json($rentPayment);
    }

    public function destroy(RentPayment $rentPayment)
    {
        // Find and delete the associated bank transaction
        $bankTransaction = BankTransaction::where('account_id', $rentPayment->bank_account_id)
            ->where('date', $rentPayment->payment_date)
            ->where('category', 'Rent Income')
            ->where('amount', $rentPayment->amount)
            ->first();

        if ($bankTransaction) {
            // Revert the bank account balance (subtract the deposit amount)
            $bankAccount = BankAccount::find($rentPayment->bank_account_id);
            if ($bankAccount) {
                $bankAccount->decrement('opening_balance', $rentPayment->amount);
            }
            $bankTransaction->delete();
        }

        $rentPayment->delete();
        return response()->json(['message' => 'Rent payment deleted successfully']);
    }
}
