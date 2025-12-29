<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    public function index()
    {
        $types = BusinessType::all();
        return response()->json($types);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:business_types',
            'description' => 'nullable|string',
        ]);

        $type = BusinessType::create($validated);
        return response()->json($type, 201);
    }

    public function show(BusinessType $businessType)
    {
        return response()->json($businessType);
    }

    public function update(Request $request, BusinessType $businessType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:business_types,name,' . $businessType->id,
            'description' => 'nullable|string',
        ]);

        $businessType->update($validated);
        return response()->json($businessType);
    }

    public function destroy(BusinessType $businessType)
    {
        $businessType->delete();
        return response()->json(['message' => 'Business type deleted successfully']);
    }
}
