<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionCategory;
use Illuminate\Http\Request;

class TransactionCategoryController extends Controller
{
    public function index()
    {
        $categories = TransactionCategory::all();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string',
        ]);

        $category = TransactionCategory::create($validated);
        return response()->json($category, 201);
    }

    public function show(TransactionCategory $transactionCategory)
    {
        return response()->json($transactionCategory);
    }

    public function update(Request $request, TransactionCategory $transactionCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:income,expense',
            'description' => 'nullable|string',
        ]);

        $transactionCategory->update($validated);
        return response()->json($transactionCategory);
    }

    public function destroy(TransactionCategory $transactionCategory)
    {
        $transactionCategory->delete();
        return response()->json(['message' => 'Transaction category deleted successfully']);
    }
}
