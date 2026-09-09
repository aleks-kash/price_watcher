<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'supplier-a',
                'name' => 'Supplier Alpha',
            ],
            [
                'code' => 'supplier-b',
                'name' => 'Supplier Beta',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['code' => $supplier['code']],
                $supplier
            );
        }
    }
}
