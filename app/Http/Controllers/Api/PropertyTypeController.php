<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;

class PropertyTypeController extends Controller
{
    public function index()
    {
        $types = PropertyType::all();
        return response()->json($types);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:property_types',
            'description' => 'nullable|string',
        ]);

        $type = PropertyType::create($validated);
        return response()->json($type, 201);
    }

    public function show(PropertyType $propertyType)
    {
        return response()->json($propertyType);
    }

    public function update(Request $request, PropertyType $propertyType)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:property_types,name,' . $propertyType->id,
            'description' => 'nullable|string',
        ]);

        $propertyType->update($validated);
        return response()->json($propertyType);
    }

    public function destroy(PropertyType $propertyType)
    {
        $propertyType->delete();
        return response()->json(['message' => 'Property type deleted successfully']);
    }
}
