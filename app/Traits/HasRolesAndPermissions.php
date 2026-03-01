<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;

trait HasRolesAndPermissions
{
    // ─── Roles ──────────────────────────────────────────────────────────────

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function assignRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    public function removeRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        $this->roles()->detach($role->id);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    // ─── Permissions ────────────────────────────────────────────────────────

    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission')
                    ->withPivot('granted');
    }

    public function hasPermission(string $permission): bool
    {
        // 1. Super admin bypasses all checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 2. Check direct user-level deny (overrides role permissions)
        $direct = $this->directPermissions->firstWhere('name', $permission);
        if ($direct && ! $direct->pivot->granted) {
            return false;
        }

        // 3. Check direct user-level grant
        if ($direct && $direct->pivot->granted) {
            return true;
        }

        // 4. Check via roles
        foreach ($this->roles as $role) {
            if ($role->permissions->contains('name', $permission)) {
                return true;
            }
        }

        return false;
    }

    public function getAllPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return Permission::pluck('name')->toArray();
        }

        $rolePermissions = $this->roles
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique();

        $granted = $this->directPermissions->where('pivot.granted', true)->pluck('name');
        $denied  = $this->directPermissions->where('pivot.granted', false)->pluck('name');

        return $rolePermissions->merge($granted)->diff($denied)->values()->toArray();
    }

    public function loadPermissionsRelations(): static
    {
        return $this->load([
            'roles.permissions',
            'directPermissions',
        ]);
    }
}
