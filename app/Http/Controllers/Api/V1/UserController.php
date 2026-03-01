<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();

        $query = User::with('roles');

        // If not super admin, hide super admins
        if (!$currentUser->isSuperAdmin()) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'super_admin');
            });
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    public function getBdes()
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'bde');
        })->where('status', 'active')->get();

        return $this->success($users);
    }

    public function store(Request $request)
    {
        // Basic implementation for now
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Auto-generate employee_id if not provided
        $lastUser = User::where('employee_id', 'LIKE', 'EMP%')
            ->orderByRaw('CAST(SUBSTRING(employee_id, 4) AS UNSIGNED) DESC')
            ->first();

        $nextId = 1;
        if ($lastUser) {
            $lastId = (int)substr($lastUser->employee_id, 3);
            $nextId = $lastId + 1;
        }
        $employeeId = 'EMP' . str_pad((string)$nextId, 3, '0', STR_PAD_LEFT);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'employee_id' => $employeeId,
            'password' => bcrypt($validated['password']),
            'status' => 'active',
        ]);

        $user->roles()->attach($validated['role_id']);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'department' => 'nullable|string|max:100',
            'designation' => 'nullable|string|max:100',
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive,on_leave'
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->department = $validated['department'];
        $user->designation = $validated['designation'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        // Sync roles
        $user->roles()->sync([$validated['role_id']]);

        return response()->json($user->load('roles'));
    }
}
