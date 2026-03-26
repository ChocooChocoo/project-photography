<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            WeddingPackagesSeeder::class,
            EventPackagesSeeder::class,
            FamilyPortraitPackagesSeeder::class,
            ProductPackagesSeeder::class,
            PetPackagesSeeder::class,
            FashionPackagesSeeder::class,
        ]);
    }
}