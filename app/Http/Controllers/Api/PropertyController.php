<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = Property::with('tenants', 'propertyType', 'location');
        
        // Add search functionality if needed
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('size', 'like', "%{$search}%");
        }
        
        $properties = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $properties->items(),
            'current_page' => $properties->currentPage(),
            'last_page' => $properties->lastPage(),
            'per_page' => $properties->perPage(),
            'total' => $properties->total(),
            'from' => $properties->firstItem(),
            'to' => $properties->lastItem(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'property_type_id' => 'nullable|exists:property_types,id',
            'location_id' => 'nullable|exists:locations,id',
            'size' => 'required|string|max:255',
            'status' => 'required|in:available,occupied',
            'monthly_rent' => 'required|numeric|min:0',
            'date_added' => 'required|date',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $property = Property::create($validated);
        $property->load('tenants', 'propertyType', 'location');

        return response()->json($property, 201);
    }

    public function show(Property $property)
    {
        $property->load('tenants', 'propertyType', 'location', 'contracts');
        return response()->json($property);
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'property_type_id' => 'nullable|exists:property_types,id',
            'location_id' => 'nullable|exists:locations,id',
            'size' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:available,occupied',
            'monthly_rent' => 'sometimes|required|numeric|min:0',
            'date_added' => 'sometimes|required|date',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $property->update($validated);
        $property->load('tenants', 'propertyType', 'location');

        return response()->json($property);
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return response()->json(['message' => 'Property deleted successfully']);
    }

    public function available(Request $request)
    {
        $properties = Property::with('propertyType', 'location')
            ->whereDoesntHave('contracts', function ($query) {
                $query->where('status', 'active')
                      ->where('end_date', '>=', now()->toDateString());
            })
            ->get();
        
        return response()->json($properties);
    }

    public function stats(Request $request)
    {
        $total = Property::count();
        $occupied = Property::whereHas('contracts', function ($query) {
            $query->where('status', 'active')
                  ->where('end_date', '>=', now()->toDateString());
        })->count();
        $available = $total - $occupied;
        
        return response()->json([
            'total' => $total,
            'occupied' => $occupied,
            'available' => $available,
        ]);
    }
}
