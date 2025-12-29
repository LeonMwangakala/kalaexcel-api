<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterSupplyReading;
use Illuminate\Http\Request;

class WaterSupplyReadingController extends Controller
{
    public function index(Request $request)
    {
        $query = WaterSupplyReading::with(['customer', 'payments']);
        
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        $readings = $query->orderBy('reading_date', 'desc')->paginate($perPage, ['*'], 'page', $page);
        return response()->json($readings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:water_supply_customers,id',
            'reading_date' => 'required|date',
            'meter_reading' => 'required|numeric|min:0',
            'previous_reading' => 'required|numeric|min:0',
            'units_consumed' => 'required|numeric|min:0',
            'amount_due' => 'required|numeric|min:0',
            'payment_status' => 'sometimes|in:paid,pending,overdue',
            'payment_date' => 'nullable|date',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $reading = WaterSupplyReading::create($validated);
        $reading->load('customer');
        return response()->json($reading, 201);
    }

    public function show(WaterSupplyReading $waterSupplyReading)
    {
        $waterSupplyReading->load('customer', 'payments');
        return response()->json($waterSupplyReading);
    }

    public function update(Request $request, WaterSupplyReading $waterSupplyReading)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:water_supply_customers,id',
            'reading_date' => 'sometimes|required|date',
            'meter_reading' => 'sometimes|required|numeric|min:0',
            'previous_reading' => 'sometimes|required|numeric|min:0',
            'units_consumed' => 'sometimes|required|numeric|min:0',
            'amount_due' => 'sometimes|required|numeric|min:0',
            'payment_status' => 'sometimes|in:paid,pending,overdue',
            'payment_date' => 'nullable|date',
            'month' => 'sometimes|required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $waterSupplyReading->update($validated);
        $waterSupplyReading->load('customer');
        return response()->json($waterSupplyReading);
    }

    public function destroy(WaterSupplyReading $waterSupplyReading)
    {
        $waterSupplyReading->delete();
        return response()->json(['message' => 'Water supply reading deleted successfully']);
    }
}
