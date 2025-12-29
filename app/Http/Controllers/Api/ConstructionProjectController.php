<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConstructionProject;
use Illuminate\Http\Request;

class ConstructionProjectController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = ConstructionProject::with('expenses');
        
        // Apply pagination
        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $projects->items(),
            'current_page' => $projects->currentPage(),
            'last_page' => $projects->lastPage(),
            'per_page' => $projects->perPage(),
            'total' => $projects->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'required|numeric|min:0',
            'total_spent' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:planning,in_progress,completed,on_hold',
            'progress' => 'sometimes|integer|min:0|max:100',
        ]);

        $project = ConstructionProject::create($validated);
        return response()->json($project, 201);
    }

    public function show(ConstructionProject $constructionProject)
    {
        $constructionProject->load('expenses');
        return response()->json($constructionProject);
    }

    public function update(Request $request, ConstructionProject $constructionProject)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'sometimes|required|numeric|min:0',
            'total_spent' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:planning,in_progress,completed,on_hold',
            'progress' => 'sometimes|integer|min:0|max:100',
        ]);

        $constructionProject->update($validated);
        return response()->json($constructionProject);
    }

    public function destroy(ConstructionProject $constructionProject)
    {
        $constructionProject->delete();
        return response()->json(['message' => 'Construction project deleted successfully']);
    }
}
