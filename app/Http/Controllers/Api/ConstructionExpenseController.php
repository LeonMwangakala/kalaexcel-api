<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstructionExpense;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;

class ConstructionExpenseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = ConstructionExpense::with(['project', 'material', 'vendor', 'bankAccount']);
        
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        
        // Apply pagination
        $expenses = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $expenses->items(),
            'current_page' => $expenses->currentPage(),
            'last_page' => $expenses->lastPage(),
            'per_page' => $expenses->perPage(),
            'total' => $expenses->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:construction_projects,id',
            'type' => 'required|in:materials,labor,equipment',
            'material_id' => 'nullable|required_if:type,materials|exists:construction_materials,id',
            'quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
            'vendor_id' => 'required|exists:vendors,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'description' => 'nullable|string',
            'receipt_url' => 'nullable|string|max:255',
        ]);

        // Calculate amount from quantity and unit_price if not provided
        if (isset($validated['quantity']) && isset($validated['unit_price']) && !isset($validated['amount'])) {
            $validated['amount'] = $validated['quantity'] * $validated['unit_price'];
        }

        $expense = ConstructionExpense::create($validated);
        
        // Update project total_spent
        $project = $expense->project;
        $project->increment('total_spent', $expense->amount);
        
        // Create bank transaction (withdrawal) for this expense
        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $previousBalance = $bankAccount->opening_balance;
        
        $vendor = $expense->vendor;
        $description = "Construction expense";
        if ($vendor) {
            $description .= " - {$vendor->name}";
        }
        if ($validated['description']) {
            $description .= ": {$validated['description']}";
        }
        
        $bankTransaction = BankTransaction::create([
            'account_id' => $validated['bank_account_id'],
            'type' => 'withdrawal',
            'amount' => $expense->amount,
            'previous_balance' => $previousBalance,
            'date' => $validated['date'],
            'description' => $description,
            'category' => 'Construction Expense',
            'reference' => $validated['receipt_url'] ?? null,
        ]);

        // Update bank account balance (deduct the withdrawal amount)
        $bankAccount->decrement('opening_balance', $expense->amount);
        
        $expense->load(['project', 'material', 'vendor', 'bankAccount']);
        return response()->json($expense, 201);
    }

    public function show(ConstructionExpense $constructionExpense)
    {
        $constructionExpense->load(['project', 'material', 'vendor', 'bankAccount']);
        return response()->json($constructionExpense);
    }

    public function update(Request $request, ConstructionExpense $constructionExpense)
    {
        $oldAmount = $constructionExpense->amount;
        $oldBankAccountId = $constructionExpense->bank_account_id;
        $oldDate = $constructionExpense->date;
        
        $validated = $request->validate([
            'project_id' => 'sometimes|required|exists:construction_projects,id',
            'type' => 'sometimes|required|in:materials,labor,equipment',
            'material_id' => 'nullable|required_if:type,materials|exists:construction_materials,id',
            'quantity' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'amount' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|required|date',
            'vendor_id' => 'sometimes|required|exists:vendors,id',
            'bank_account_id' => 'sometimes|required|exists:bank_accounts,id',
            'description' => 'nullable|string',
            'receipt_url' => 'nullable|string|max:255',
        ]);

        // Calculate amount from quantity and unit_price if both are provided
        if (isset($validated['quantity']) && isset($validated['unit_price'])) {
            $validated['amount'] = $validated['quantity'] * $validated['unit_price'];
        }

        $constructionExpense->update($validated);
        
        // Update project total_spent if amount changed
        if (isset($validated['amount']) && $validated['amount'] != $oldAmount) {
            $project = $constructionExpense->project;
            $project->decrement('total_spent', $oldAmount);
            $project->increment('total_spent', $validated['amount']);
        }
        
        // Find and update the associated bank transaction
        $bankTransaction = BankTransaction::where('account_id', $oldBankAccountId)
            ->where('date', $oldDate)
            ->where('category', 'Construction Expense')
            ->where('amount', $oldAmount)
            ->first();

        if ($bankTransaction) {
            $oldBankAccount = BankAccount::find($oldBankAccountId);
            $newBankAccountId = $validated['bank_account_id'] ?? $oldBankAccountId;
            $newAmount = $validated['amount'] ?? $oldAmount;
            $newDate = $validated['date'] ?? $oldDate;

            // Revert old transaction (add back to old account)
            if ($oldBankAccount) {
                $oldBankAccount->increment('opening_balance', $oldAmount);
            }

            // Update or create new transaction
            $newBankAccount = BankAccount::findOrFail($newBankAccountId);
            $previousBalance = $newBankAccount->opening_balance;
            
            $vendor = $constructionExpense->vendor;
            $description = "Construction expense";
            if ($vendor) {
                $description .= " - {$vendor->name}";
            }
            $expenseDescription = $validated['description'] ?? $constructionExpense->description;
            if ($expenseDescription) {
                $description .= ": {$expenseDescription}";
            }

            if ($bankTransaction->account_id == $newBankAccountId) {
                // Same account, just update the transaction
                $bankTransaction->update([
                    'amount' => $newAmount,
                    'previous_balance' => $previousBalance,
                    'date' => $newDate,
                    'description' => $description,
                    'reference' => $validated['receipt_url'] ?? $constructionExpense->receipt_url,
                ]);
            } else {
                // Different account, delete old and create new
                $bankTransaction->delete();
                $bankTransaction = BankTransaction::create([
                    'account_id' => $newBankAccountId,
                    'type' => 'withdrawal',
                    'amount' => $newAmount,
                    'previous_balance' => $previousBalance,
                    'date' => $newDate,
                    'description' => $description,
                    'category' => 'Construction Expense',
                    'reference' => $validated['receipt_url'] ?? $constructionExpense->receipt_url,
                ]);
            }

            // Deduct new amount from new account
            $newBankAccount->decrement('opening_balance', $newAmount);
        } else {
            // No existing transaction found, create a new one
            $newBankAccountId = $validated['bank_account_id'] ?? $oldBankAccountId;
            $newAmount = $validated['amount'] ?? $oldAmount;
            $newDate = $validated['date'] ?? $oldDate;
            
            $newBankAccount = BankAccount::findOrFail($newBankAccountId);
            $previousBalance = $newBankAccount->opening_balance;
            
            $vendor = $constructionExpense->vendor;
            $description = "Construction expense";
            if ($vendor) {
                $description .= " - {$vendor->name}";
            }
            $expenseDescription = $validated['description'] ?? $constructionExpense->description;
            if ($expenseDescription) {
                $description .= ": {$expenseDescription}";
            }

            BankTransaction::create([
                'account_id' => $newBankAccountId,
                'type' => 'withdrawal',
                'amount' => $newAmount,
                'previous_balance' => $previousBalance,
                'date' => $newDate,
                'description' => $description,
                'category' => 'Construction Expense',
                'reference' => $validated['receipt_url'] ?? $constructionExpense->receipt_url,
            ]);

            $newBankAccount->decrement('opening_balance', $newAmount);
        }
        
        $constructionExpense->load(['project', 'material', 'vendor', 'bankAccount']);
        return response()->json($constructionExpense);
    }

    public function destroy(ConstructionExpense $constructionExpense)
    {
        // Find and delete the associated bank transaction
        $bankTransaction = BankTransaction::where('account_id', $constructionExpense->bank_account_id)
            ->where('date', $constructionExpense->date)
            ->where('category', 'Construction Expense')
            ->where('amount', $constructionExpense->amount)
            ->first();

        if ($bankTransaction) {
            // Revert the bank account balance (add back the withdrawal amount)
            $bankAccount = BankAccount::find($constructionExpense->bank_account_id);
            if ($bankAccount) {
                $bankAccount->increment('opening_balance', $constructionExpense->amount);
            }
            $bankTransaction->delete();
        }
        
        $project = $constructionExpense->project;
        $project->decrement('total_spent', $constructionExpense->amount);
        
        $constructionExpense->delete();
        return response()->json(['message' => 'Construction expense deleted successfully']);
    }
}
