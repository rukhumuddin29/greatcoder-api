<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SalaryStructure;
use App\Models\User;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    /**
     * List all employees with their active salary structures.
     */
    public function index(Request $request)
    {
        $query = User::where('status', 'active')
            ->with(['salaryStructure', 'roles']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->paginate(20);
        return $this->success($employees);
    }

    /**
     * Get salary structure for a specific employee.
     */
    public function show(User $user)
    {
        $user->load(['salaryStructure', 'salaryStructures']);
        return $this->success($user);
    }

    /**
     * Create or update salary structure for an employee.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ctc_annual' => 'required|numeric|min:0',
            'basic_salary' => 'required|numeric|min:0',
            'hra' => 'required|numeric|min:0',
            'da' => 'nullable|numeric|min:0',
            'special_allowance' => 'nullable|numeric|min:0',
            'pf_employer' => 'nullable|numeric|min:0',
            'esi_employer' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
        ]);

        // Deactivate any existing active structure for this user
        SalaryStructure::where('user_id', $validated['user_id'])
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $structure = SalaryStructure::create(array_merge($validated, [
            'da' => $validated['da'] ?? 0,
            'special_allowance' => $validated['special_allowance'] ?? 0,
            'pf_employer' => $validated['pf_employer'] ?? 0,
            'esi_employer' => $validated['esi_employer'] ?? 0,
            'other_allowances' => $validated['other_allowances'] ?? 0,
            'is_active' => true,
        ]));

        // Also update the salary field on users table for backward compatibility
        $monthlyGross = $structure->monthly_gross;
        User::where('id', $validated['user_id'])->update(['salary' => $monthlyGross]);

        return $this->success($structure->load('user'), 'Salary structure saved successfully', 201);
    }
}
