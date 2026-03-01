<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function show()
    {
        $company = Company::first();
        if (!$company) {
            // Return empty structure if not created yet
            return $this->success([
                'name' => '',
                'logo' => null,
                'address' => '',
                'city' => '',
                'state' => '',
                'pincode' => '',
                'country' => 'IN',
                'phone' => '',
                'email' => '',
                'website' => '',
                'facebook' => '',
                'instagram' => '',
                'youtube' => '',
                'linkedin' => '',
            ]);
        }

        return $this->success($company);
    }

    public function publicInfo()
    {
        $company = Company::first();
        $logoUrl = null;
        if ($company && $company->logo) {
            $logoUrl = asset('storage/' . $company->logo);
        }

        return $this->success([
            'name' => $company->name ?? 'Elements HR',
            'logo_url' => $logoUrl,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
        ]);

        $company = Company::first() ?? new Company();

        $data = $validated;
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $path = $request->file('logo')->store('company', 'public');
            $data['logo'] = $path;
        }

        $company->fill($data);
        $company->save();

        return $this->success($company, 'Company information updated successfully');
    }
}
