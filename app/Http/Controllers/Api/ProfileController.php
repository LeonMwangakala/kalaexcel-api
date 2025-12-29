<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        // Profile is typically a single record
        $profile = Profile::first();
        return response()->json($profile);
    }

    public function store(Request $request)
    {
        // Check if profile already exists
        if (Profile::exists()) {
            return response()->json(['message' => 'Profile already exists. Use update instead.'], 400);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string',
            'tax_id' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
        ]);

        $profile = Profile::create($validated);
        return response()->json($profile, 201);
    }

    public function show(Profile $profile)
    {
        return response()->json($profile);
    }

    public function update(Request $request, Profile $profile = null)
    {
        // If no profile is passed (from route model binding), get the first one or create it
        if (!$profile) {
            $profile = Profile::first();
            if (!$profile) {
                // Create profile if it doesn't exist
                $validated = $request->validate([
                    'company_name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255',
                    'phone' => 'required|string|max:255',
                    'address' => 'required|string',
                    'tax_id' => 'nullable|string|max:255',
                    'registration_number' => 'nullable|string|max:255',
                    'logo' => 'nullable|string|max:255',
                ]);
                $profile = Profile::create($validated);
                return response()->json($profile);
            }
        }

        $validated = $request->validate([
            'company_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'tax_id' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:255',
        ]);

        $profile->update($validated);
        return response()->json($profile);
    }

    public function destroy(Profile $profile)
    {
        $profile->delete();
        return response()->json(['message' => 'Profile deleted successfully']);
    }
}
