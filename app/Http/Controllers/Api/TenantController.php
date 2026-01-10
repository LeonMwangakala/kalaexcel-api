<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $query = Tenant::with('properties');
        
        // Add search functionality if needed
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
        }
        
        $tenants = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'data' => $tenants->items(),
            'current_page' => $tenants->currentPage(),
            'last_page' => $tenants->lastPage(),
            'per_page' => $tenants->perPage(),
            'total' => $tenants->total(),
            'from' => $tenants->firstItem(),
            'to' => $tenants->lastItem(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'id_number' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:properties,id',
            'status' => 'sometimes|in:active,ended,pending_payment',
        ]);

        $propertyIds = $validated['property_ids'];
        unset($validated['property_ids']);

        // Check if tenant already exists (by phone - primary identifier)
        // Also check by id_number as secondary check
        $existingTenant = Tenant::where('phone', $validated['phone'])->first();
        $isExistingTenant = false;
        
        if (!$existingTenant) {
            // If not found by phone, check by id_number
            $existingTenant = Tenant::where('id_number', $validated['id_number'])->first();
        }

        if ($existingTenant) {
            // Tenant already exists - assign to new properties
            $isExistingTenant = true;
            $tenant = $existingTenant;
            
            // Get properties the tenant doesn't already have
            $existingPropertyIds = $tenant->properties->pluck('id')->toArray();
            $newPropertyIds = array_values(array_diff($propertyIds, $existingPropertyIds));
            
            if (empty($newPropertyIds)) {
                throw ValidationException::withMessages([
                    'property_ids' => 'This tenant is already assigned to all selected properties.'
                ]);
            }

            // Check if any of the new properties already have an active contract with another tenant
            $propertiesWithActiveContracts = Contract::whereIn('property_id', $newPropertyIds)
                ->where('status', 'active')
                ->where('end_date', '>=', now()->toDateString())
                ->where('tenant_id', '!=', $tenant->id)
                ->pluck('property_id')
                ->unique()
                ->toArray();

            if (!empty($propertiesWithActiveContracts)) {
                $propertyNames = Property::whereIn('id', $propertiesWithActiveContracts)
                    ->pluck('name')
                    ->toArray();
                
                throw ValidationException::withMessages([
                    'property_ids' => 'The following properties are already leased to other tenants: ' . implode(', ', $propertyNames)
                ]);
            }

            // Attach only the new properties (avoid duplicates)
            $tenant->properties()->attach($newPropertyIds);
        } else {
            // Create new tenant
            // Check if any property already has an active contract
            $propertiesWithActiveContracts = Contract::whereIn('property_id', $propertyIds)
                ->where('status', 'active')
                ->where('end_date', '>=', now()->toDateString())
                ->pluck('property_id')
                ->unique()
                ->toArray();

            if (!empty($propertiesWithActiveContracts)) {
                $propertyNames = Property::whereIn('id', $propertiesWithActiveContracts)
                    ->pluck('name')
                    ->toArray();
                
                throw ValidationException::withMessages([
                    'property_ids' => 'The following properties are already leased: ' . implode(', ', $propertyNames)
                ]);
            }

            $tenant = Tenant::create($validated);
            $tenant->properties()->attach($propertyIds);
        }

        $tenant->load('properties');

        // Return tenant in same format as before, but include message in response
        $response = $tenant->toArray();
        if ($isExistingTenant) {
            $response['_message'] = 'Existing tenant assigned to new properties successfully.';
        }

        return response()->json($response, 201);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('properties', 'contracts', 'rentPayments');
        return response()->json($tenant);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255|unique:tenants,phone,' . $tenant->id,
            'id_number' => 'sometimes|required|string|max:255',
            'business_type' => 'sometimes|required|string|max:255',
            'property_ids' => 'sometimes|array',
            'property_ids.*' => 'exists:properties,id',
            'status' => 'sometimes|in:active,ended,pending_payment',
        ]);

        if (isset($validated['property_ids'])) {
            $propertyIds = $validated['property_ids'];
            unset($validated['property_ids']);
            
            // Check if any property already has an active contract (excluding current tenant's contracts)
            // Use direct Contract query to ensure fresh data
            $propertiesWithActiveContracts = Contract::whereIn('property_id', $propertyIds)
                ->where('status', 'active')
                ->where('end_date', '>=', now()->toDateString())
                ->where('tenant_id', '!=', $tenant->id)
                ->pluck('property_id')
                ->unique()
                ->toArray();

            if (!empty($propertiesWithActiveContracts)) {
                $propertyNames = Property::whereIn('id', $propertiesWithActiveContracts)
                    ->pluck('name')
                    ->toArray();
                
                throw ValidationException::withMessages([
                    'property_ids' => 'The following properties are already leased to other tenants: ' . implode(', ', $propertyNames)
                ]);
            }
            
            $tenant->properties()->sync($propertyIds);
        }

        $tenant->update($validated);
        $tenant->load('properties');

        return response()->json($tenant);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return response()->json(['message' => 'Tenant deleted successfully']);
    }
}
