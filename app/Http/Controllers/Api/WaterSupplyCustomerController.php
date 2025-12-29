<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterSupplyCustomer;
use Illuminate\Http\Request;

class WaterSupplyCustomerController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $customers = WaterSupplyCustomer::with(['readings', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'meter_number' => 'required|string|max:255|unique:water_supply_customers',
            'starting_reading' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'date_registered' => 'required|date',
        ]);

        $customer = WaterSupplyCustomer::create($validated);
        return response()->json($customer, 201);
    }

    public function show(WaterSupplyCustomer $waterSupplyCustomer)
    {
        $waterSupplyCustomer->load('readings', 'payments');
        return response()->json($waterSupplyCustomer);
    }

    public function update(Request $request, WaterSupplyCustomer $waterSupplyCustomer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'meter_number' => 'sometimes|required|string|max:255|unique:water_supply_customers,meter_number,' . $waterSupplyCustomer->id,
            'starting_reading' => 'sometimes|required|numeric|min:0',
            'unit_price' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|in:active,inactive',
            'date_registered' => 'sometimes|required|date',
        ]);

        $waterSupplyCustomer->update($validated);
        return response()->json($waterSupplyCustomer);
    }

    public function destroy(WaterSupplyCustomer $waterSupplyCustomer)
    {
        $waterSupplyCustomer->delete();
        return response()->json(['message' => 'Water supply customer deleted successfully']);
    }
}
