<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstructionMaterial;
use Illuminate\Http\Request;

class ConstructionMaterialController extends Controller
{
    public function index()
    {
        $materials = ConstructionMaterial::all();
        return response()->json($materials);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $material = ConstructionMaterial::create($validated);
        return response()->json($material, 201);
    }

    public function show(ConstructionMaterial $constructionMaterial)
    {
        return response()->json($constructionMaterial);
    }

    public function update(Request $request, ConstructionMaterial $constructionMaterial)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'unit' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $constructionMaterial->update($validated);
        return response()->json($constructionMaterial);
    }

    public function destroy(ConstructionMaterial $constructionMaterial)
    {
        $constructionMaterial->delete();
        return response()->json(['message' => 'Construction material deleted successfully']);
    }
}
