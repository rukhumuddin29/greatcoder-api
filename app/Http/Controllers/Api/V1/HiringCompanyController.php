<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HiringCompany;
use Illuminate\Http\Request;

class HiringCompanyController extends Controller
{
    public function index()
    {
        return $this->success(HiringCompany::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $company = HiringCompany::create($validated);
        return $this->success($company, 'Company added successfully', 201);
    }

    public function show(HiringCompany $hiringCompany)
    {
        return $this->success($hiringCompany);
    }

    public function update(Request $request, HiringCompany $hiringCompany)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'industry' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $hiringCompany->update($validated);
        return $this->success($hiringCompany, 'Company updated successfully');
    }

    public function destroy(HiringCompany $hiringCompany)
    {
        $hiringCompany->delete();
        return $this->success(null, 'Company deleted successfully');
    }
}
