<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpenseCategory;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Salary', 'description' => 'Employee and staff salaries', 'status' => 'active'],
            ['name' => 'Rent', 'description' => 'Office or center rent payments', 'status' => 'active'],
            ['name' => 'Utilities', 'description' => 'Electricity, water, and internet bills', 'status' => 'active'],
            ['name' => 'Marketing', 'description' => 'Ad spends and promotional material', 'status' => 'active'],
            ['name' => 'Maintenance', 'description' => 'Repairs and office upkeep', 'status' => 'active'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::updateOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
