<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();

        $query = Role::withCount('permissions');

        // If not super admin, hide super_admin role
        if (!$currentUser->isSuperAdmin()) {
            $query->where('name', '!=', 'super_admin');
        }

        $roles = $query->get();

        return response()->json([
            'data' => $roles
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $role = Role::create($validated);

        return response()->json($role, 201);
    }

    public function show($id)
    {
        $role = Role::with('permissions')->findOrFail($id);

        // Security check
        if ($role->name === 'super_admin' && !Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'super_admin' && !Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
        ]);

        $role->permissions()->sync($validated['permissions']);

        return response()->json(['message' => 'Permissions updated successfully']);
    }
}
