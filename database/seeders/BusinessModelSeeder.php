<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class BusinessModelSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [config('enlink.starter_package_name'), 3, 5000, 0, 'starter'],
            ['Pack medio', 10, 10000, 1, 'medium'],
            ['Pack pro', 50, 30000, 2, 'pro'],
        ];

        foreach ($packages as [$name, $credits, $price, $sortOrder, $kind]) {
            CreditPackage::query()->updateOrCreate(
                ['name' => $name],
                [
                    'credits' => $credits,
                    'price' => $price,
                    'currency' => 'ARS',
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                    'metadata' => ['kind' => $kind],
                ],
            );
        }
    }
}
