<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmployeeDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            // Address
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            // Contact
            'alternate_number' => 'nullable|string|max:20',
            'emergency_contact_number' => 'nullable|string|max:20',
            // Bank Details
            'account_holder_name' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
        ]);

        // 1. Update User basic info
        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->save();

        // 2. Update or Create Employee Details
        $details = $user->employeeDetail ?: new EmployeeDetail(['user_id' => $user->id]);

        // Rule: Bank details can only be entered once
        $bankFields = ['account_holder_name', 'bank_name', 'account_number', 'ifsc_code'];

        // If bank details already exist, prevent updating them
        $hasBankDetails = $details->exists && $details->account_number;

        foreach ($validated as $key => $value) {
            // Skip basic user fields
            if (in_array($key, ['name', 'phone']))
                continue;

            // Handle bank field restriction
            if (in_array($key, $bankFields)) {
                if ($hasBankDetails)
                    continue; // Skip if already set
            }

            $details->$key = $value;
        }

        $details->save();

        return $this->success($user->load(['roles.permissions', 'employeeDetail']), 'Profile updated successfully');
    }

    /**
     * Update the authenticated user's avatar.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Increased size and added webp
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');

            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Generate custom filename: username_id_uniqueid
            $username = strtolower(str_replace(' ', '_', $user->name));
            $uniqueId = uniqid();
            $extension = $file->getClientOriginalExtension();
            $filename = "{$username}_id_{$uniqueId}.{$extension}";

            $path = $file->storeAs('avatars', $filename, 'public');
            $user->avatar = $path;
            $user->save();

            return $this->success([
                'avatar_url' => asset('storage/' . $path),
                'user' => $user->load('roles.permissions')
            ], 'Avatar updated successfully');
        }

        return $this->error('No avatar file provided');
    }
}
