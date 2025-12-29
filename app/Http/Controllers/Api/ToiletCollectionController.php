<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ToiletCollection;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class ToiletCollectionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = ToiletCollection::with(['cashier', 'bankAccount']);
        
        // Apply pagination
        $collections = $query->orderBy('date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $collections->items(),
            'current_page' => $collections->currentPage(),
            'last_page' => $collections->lastPage(),
            'per_page' => $collections->perPage(),
            'total' => $collections->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'total_users' => 'required|integer|min:0',
            'amount_collected' => 'required|numeric|min:0',
            'cashier_id' => 'required|exists:users,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'deposit_id' => 'nullable|string|max:255',
            'deposit_date' => 'nullable|date',
            'is_deposited' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        // Create collection
        $collection = ToiletCollection::create($validated);

        // Create bank transaction (deposit) for this collection
        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $cashier = $collection->cashier;
        
        $description = "Public toilet collection";
        if ($cashier) {
            $description .= " by {$cashier->name}";
        }
        
        $previousBalance = $bankAccount->opening_balance;
        
        $bankTransaction = BankTransaction::create([
            'account_id' => $validated['bank_account_id'],
            'type' => 'deposit',
            'amount' => $validated['amount_collected'],
            'previous_balance' => $previousBalance,
            'date' => $validated['date'],
            'description' => $description,
            'category' => 'Toilet Collection Income',
            'reference' => null,
        ]);

        // Update bank account balance (add the deposit amount)
        $bankAccount->increment('opening_balance', $validated['amount_collected']);

        $collection->load(['cashier', 'bankAccount']);
        return response()->json($collection, 201);
    }

    public function show(ToiletCollection $toiletCollection)
    {
        $toiletCollection->load(['cashier', 'bankAccount']);
        return response()->json($toiletCollection);
    }

    public function update(Request $request, ToiletCollection $toiletCollection)
    {
        $validated = $request->validate([
            'date' => 'sometimes|required|date',
            'total_users' => 'sometimes|required|integer|min:0',
            'amount_collected' => 'sometimes|required|numeric|min:0',
            'cashier_id' => 'sometimes|required|exists:users,id',
            'bank_account_id' => 'sometimes|required|exists:bank_accounts,id',
            'deposit_id' => 'nullable|string|max:255',
            'deposit_date' => 'nullable|date',
            'is_deposited' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ]);

        // Store old values for transaction reversal
        $oldBankAccountId = $toiletCollection->bank_account_id;
        $oldAmount = $toiletCollection->amount_collected;
        $oldDate = $toiletCollection->date;

        // Update collection
        $toiletCollection->update($validated);

        // Find and update the associated bank transaction
        $bankTransaction = BankTransaction::where('account_id', $oldBankAccountId)
            ->where('date', $oldDate)
            ->where('category', 'Toilet Collection Income')
            ->where('amount', $oldAmount)
            ->first();

        if ($bankTransaction) {
            $oldBankAccount = BankAccount::find($oldBankAccountId);
            $newBankAccountId = $validated['bank_account_id'] ?? $oldBankAccountId;
            $newAmount = $validated['amount_collected'] ?? $oldAmount;
            $newDate = $validated['date'] ?? $oldDate;

            // Revert old transaction (subtract from old account)
            if ($oldBankAccount) {
                $oldBankAccount->decrement('opening_balance', $oldAmount);
            }

            // Update or create new transaction
            $newBankAccount = BankAccount::findOrFail($newBankAccountId);
            $cashier = $toiletCollection->cashier;
            
            $description = "Public toilet collection";
            if ($cashier) {
                $description .= " by {$cashier->name}";
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
                    'category' => 'Toilet Collection Income',
                    'reference' => null,
                ]);
            }

            // Add new amount to new account
            $newBankAccount->increment('opening_balance', $newAmount);
        } else {
            // No existing transaction found, create a new one
            $newBankAccountId = $validated['bank_account_id'] ?? $oldBankAccountId;
            $newAmount = $validated['amount_collected'] ?? $oldAmount;
            $newDate = $validated['date'] ?? $oldDate;
            
            $newBankAccount = BankAccount::findOrFail($newBankAccountId);
            $previousBalance = $newBankAccount->opening_balance;
            $cashier = $toiletCollection->cashier;
            
            $description = "Public toilet collection";
            if ($cashier) {
                $description .= " by {$cashier->name}";
            }

            BankTransaction::create([
                'account_id' => $newBankAccountId,
                'type' => 'deposit',
                'amount' => $newAmount,
                'previous_balance' => $previousBalance,
                'date' => $newDate,
                'description' => $description,
                'category' => 'Toilet Collection Income',
                'reference' => null,
            ]);

            $newBankAccount->increment('opening_balance', $newAmount);
        }

        $toiletCollection->load(['cashier', 'bankAccount']);
        return response()->json($toiletCollection);
    }

    public function destroy(ToiletCollection $toiletCollection)
    {
        // Find and delete the associated bank transaction
        $bankTransaction = BankTransaction::where('account_id', $toiletCollection->bank_account_id)
            ->where('date', $toiletCollection->date)
            ->where('category', 'Toilet Collection Income')
            ->where('amount', $toiletCollection->amount_collected)
            ->first();

        if ($bankTransaction) {
            // Revert the bank account balance (subtract the deposit amount)
            $bankAccount = BankAccount::find($toiletCollection->bank_account_id);
            if ($bankAccount) {
                $bankAccount->decrement('opening_balance', $toiletCollection->amount_collected);
            }
            $bankTransaction->delete();
        }

        $toiletCollection->delete();
        return response()->json(['message' => 'Toilet collection deleted successfully']);
    }
}
