<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::all();
        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
        ]);

        $location = Location::create($validated);
        return response()->json($location, 201);
    }

    public function show(Location $location)
    {
        return response()->json($location);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:locations,name,' . $location->id,
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
        ]);

        $location->update($validated);
        return response()->json($location);
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json(['message' => 'Location deleted successfully']);
    }
}
