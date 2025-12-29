<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::with('transactions')->get();
        return response()->json($accounts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'opening_balance' => 'required|numeric|min:0',
            'type' => 'required|in:checking,savings,business',
        ]);

        $account = BankAccount::create($validated);
        return response()->json($account, 201);
    }

    public function show(BankAccount $bankAccount)
    {
        $bankAccount->load('transactions');
        return response()->json($bankAccount);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'account_name' => 'sometimes|required|string|max:255',
            'bank_name' => 'sometimes|required|string|max:255',
            'branch_name' => 'sometimes|required|string|max:255',
            'account_number' => 'sometimes|required|string|max:255',
            'opening_balance' => 'sometimes|required|numeric|min:0',
            'type' => 'sometimes|required|in:checking,savings,business',
        ]);

        $bankAccount->update($validated);
        return response()->json($bankAccount);
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return response()->json(['message' => 'Bank account deleted successfully']);
    }
}
