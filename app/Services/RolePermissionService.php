<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Exception;

class RolePermissionService extends BaseService
{
    public function createRole(array $data, array $permissions = [])
    {
        return $this->transactional(function () use ($data, $permissions) {
            $role = Role::create($data);
            if (!empty($permissions)) {
                $role->permissions()->sync($permissions);
            }
            return $role;
        });
    }

    public function updateRole(Role $role, array $data, array $permissions = null)
    {
        if ($role->is_system && isset($data['name'])) {
            unset($data['name']); // Prevent changing system role names
        }

        return $this->transactional(function () use ($role, $data, $permissions) {
            $role->update($data);
            if ($permissions !== null) {
                $role->permissions()->sync($permissions);
            }
            return $role;
        });
    }

    public function deleteRole(Role $role)
    {
        if ($role->is_system) {
            throw new Exception("System roles cannot be deleted.");
        }

        return $this->transactional(function () use ($role) {
            $role->users()->detach();
            $role->permissions()->detach();
            return $role->delete();
        });
    }
}
