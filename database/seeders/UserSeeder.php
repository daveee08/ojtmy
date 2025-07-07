<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       DB::table('users')->insert([
            ['email' => 'admin@gmail.com', 'username' => 'admin', 'password' => Hash::make('your_secure_password'), 'backup_password' => 'admin123', 'is_admin' => 1, 'grade_level' => 'College','created_at' => now(), 'updated_at' => now()],
       ]);
    }
}
