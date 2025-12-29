<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankTransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = BankTransaction::with('account');
        
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }
        
        // Apply pagination
        $transactions = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $transactions->items(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:bank_accounts,id',
            'type' => 'required|in:deposit,withdrawal',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        // Get previous balance before creating transaction
        $account = BankAccount::findOrFail($validated['account_id']);
        $previousBalance = $account->opening_balance;
        
        // Add previous_balance to validated data
        $validated['previous_balance'] = $previousBalance;
        
        $transaction = BankTransaction::create($validated);
        
        // Update account balance
        if ($validated['type'] === 'deposit') {
            $account->increment('opening_balance', $validated['amount']);
        } else {
            $account->decrement('opening_balance', $validated['amount']);
        }
        
        $transaction->load('account');
        return response()->json($transaction, 201);
    }

    public function show(BankTransaction $bankTransaction)
    {
        $bankTransaction->load('account');
        return response()->json($bankTransaction);
    }

    public function update(Request $request, BankTransaction $bankTransaction)
    {
        $oldAmount = $bankTransaction->amount;
        $oldType = $bankTransaction->type;
        $account = $bankTransaction->account;
        
        // Revert old transaction
        if ($oldType === 'deposit') {
            $account->decrement('opening_balance', $oldAmount);
        } else {
            $account->increment('opening_balance', $oldAmount);
        }
        
        $validated = $request->validate([
            'account_id' => 'sometimes|required|exists:bank_accounts,id',
            'type' => 'sometimes|required|in:deposit,withdrawal',
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        // Get previous balance before updating
        $previousBalance = $account->opening_balance;
        $validated['previous_balance'] = $previousBalance;
        
        $bankTransaction->update($validated);
        
        // Apply new transaction
        $newType = $validated['type'] ?? $oldType;
        $newAmount = $validated['amount'] ?? $oldAmount;
        
        if ($newType === 'deposit') {
            $account->increment('opening_balance', $newAmount);
        } else {
            $account->decrement('opening_balance', $newAmount);
        }
        
        $bankTransaction->load('account');
        return response()->json($bankTransaction);
    }

    public function destroy(BankTransaction $bankTransaction)
    {
        $account = $bankTransaction->account;
        
        // Revert transaction
        if ($bankTransaction->type === 'deposit') {
            $account->decrement('opening_balance', $bankTransaction->amount);
        } else {
            $account->increment('opening_balance', $bankTransaction->amount);
        }
        
        $bankTransaction->delete();
        return response()->json(['message' => 'Bank transaction deleted successfully']);
    }
}
