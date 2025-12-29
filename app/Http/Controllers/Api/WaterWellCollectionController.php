<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterWellCollection;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class WaterWellCollectionController extends Controller
{
    public function index(Request $request)
    {
        $query = WaterWellCollection::with(['operator', 'bankAccount']);
        
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $collections = $query->orderBy('date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json($collections);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'buckets_sold' => 'required|integer|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'operator_id' => 'required|exists:users,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'deposit_id' => 'nullable|string|max:255',
            'deposit_date' => 'nullable|date',
            'is_deposited' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        // Create collection
        $collection = WaterWellCollection::create($validated);

        // Create bank transaction (deposit) for this collection
        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $operator = $collection->operator;
        
        $description = "Water well collection";
        if ($operator) {
            $description .= " by {$operator->name}";
        }
        
        $previousBalance = $bankAccount->opening_balance;
        
        $bankTransaction = BankTransaction::create([
            'account_id' => $validated['bank_account_id'],
            'type' => 'deposit',
            'amount' => $validated['total_amount'],
            'previous_balance' => $previousBalance,
            'date' => $validated['date'],
            'description' => $description,
            'category' => 'Water Well Collection Income',
            'reference' => null,
        ]);

        // Update bank account balance (add the deposit amount)
        $bankAccount->increment('opening_balance', $validated['total_amount']);

        $collection->load('operator', 'bankAccount');
        return response()->json($collection, 201);
    }

    public function show(WaterWellCollection $waterWellCollection)
    {
        $waterWellCollection->load('operator', 'bankAccount');
        return response()->json($waterWellCollection);
    }

    public function update(Request $request, WaterWellCollection $waterWellCollection)
    {
        $validated = $request->validate([
            'date' => 'sometimes|required|date',
            'buckets_sold' => 'sometimes|required|integer|min:0',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'operator_id' => 'sometimes|required|exists:users,id',
            'bank_account_id' => 'sometimes|required|exists:bank_accounts,id',
            'deposit_id' => 'nullable|string|max:255',
            'deposit_date' => 'nullable|date',
            'is_deposited' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        $oldAmount = $waterWellCollection->total_amount;
        $oldBankAccountId = $waterWellCollection->bank_account_id;
        $oldDate = $waterWellCollection->date;

        $waterWellCollection->update($validated);

        // Handle bank transaction updates - always ensure bank transaction exists if bank_account_id is present
        $newBankAccountId = $validated['bank_account_id'] ?? $waterWellCollection->bank_account_id;
        $newAmount = $validated['total_amount'] ?? $waterWellCollection->total_amount;
        $newDate = $validated['date'] ?? $waterWellCollection->date;

        if ($newBankAccountId) {
            // Find the associated bank transaction
            $bankTransaction = null;
            if ($oldBankAccountId) {
                $bankTransaction = BankTransaction::where('account_id', $oldBankAccountId)
                    ->where('date', $oldDate)
                    ->where('category', 'Water Well Collection Income')
                    ->where('amount', $oldAmount)
                    ->first();
            }

            if ($bankTransaction) {
                // Revert old transaction (subtract from old account)
                $oldBankAccount = BankAccount::find($oldBankAccountId);
                if ($oldBankAccount) {
                    $oldBankAccount->decrement('opening_balance', $oldAmount);
                }

                // Update or create new transaction
                $newBankAccount = BankAccount::findOrFail($newBankAccountId);
                $operator = $waterWellCollection->operator;
                
                $description = "Water well collection";
                if ($operator) {
                    $description .= " by {$operator->name}";
                }

                $previousBalance = $newBankAccount->opening_balance;
                
                if ($bankTransaction->account_id == $newBankAccountId) {
                    // Same account, just update the transaction
                    $bankTransaction->update([
                        'amount' => $newAmount,
                        'previous_balance' => $previousBalance,
                        'date' => $newDate,
                        'description' => $description,
                    ]);
                } else {
                    // Different account, delete old and create new
                    $bankTransaction->delete();
                    $bankTransaction = BankTransaction::create([
                        'account_id' => $newBankAccountId,
                        'type' => 'deposit',
                        'amount' => $newAmount,
                        'previous_balance' => $previousBalance,
                        'date' => $newDate,
                        'description' => $description,
                        'category' => 'Water Well Collection Income',
                        'reference' => null,
                    ]);
                }

                // Add new amount to new account
                $newBankAccount->increment('opening_balance', $newAmount);
            } elseif ($oldBankAccountId != $newBankAccountId || $oldAmount != $newAmount || $oldDate != $newDate) {
                // No existing transaction but we have a bank account - create one
                $newBankAccount = BankAccount::findOrFail($newBankAccountId);
                $operator = $waterWellCollection->operator;
                
                $description = "Water well collection";
                if ($operator) {
                    $description .= " by {$operator->name}";
                }

                $previousBalance = $newBankAccount->opening_balance;
                
                BankTransaction::create([
                    'account_id' => $newBankAccountId,
                    'type' => 'deposit',
                    'amount' => $newAmount,
                    'previous_balance' => $previousBalance,
                    'date' => $newDate,
                    'description' => $description,
                    'category' => 'Water Well Collection Income',
                    'reference' => null,
                ]);

                // Add amount to account
                $newBankAccount->increment('opening_balance', $newAmount);
            }
        }

        $waterWellCollection->load('operator', 'bankAccount');
        return response()->json($waterWellCollection);
    }

    public function destroy(WaterWellCollection $waterWellCollection)
    {
        // Revert the bank transaction if it exists
        if ($waterWellCollection->bank_account_id) {
            $bankTransaction = BankTransaction::where('account_id', $waterWellCollection->bank_account_id)
                ->where('date', $waterWellCollection->date)
                ->where('category', 'Water Well Collection Income')
                ->where('amount', $waterWellCollection->total_amount)
                ->first();

            if ($bankTransaction) {
                // Revert the bank account balance (subtract the deposit amount)
                $bankAccount = BankAccount::find($waterWellCollection->bank_account_id);
                if ($bankAccount) {
                    $bankAccount->decrement('opening_balance', $waterWellCollection->total_amount);
                }
                $bankTransaction->delete();
            }
        }

        $waterWellCollection->delete();
        return response()->json(['message' => 'Water well collection deleted successfully']);
    }
}
