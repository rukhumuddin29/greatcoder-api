<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all()->groupBy('module');
        return response()->json($permissions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'display_name' => 'required|string',
            'module' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create($validated);

        return $this->success($permission, 'Permission created successfully');
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'display_name' => 'required|string',
            'module' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);

        return $this->success($permission, 'Permission updated successfully');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return $this->success(null, 'Permission deleted successfully');
    }
}
