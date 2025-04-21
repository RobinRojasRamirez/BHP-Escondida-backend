<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('roles')->insert([
            [
                'name' => 'L5',
                'description' => 'L5',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'name' => 'L4',
                'description' => 'L4',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'name' => 'L3',
                'description' => 'L3',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'name' => 'HSS',
                'description' => 'HSS',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ]);
    }
}