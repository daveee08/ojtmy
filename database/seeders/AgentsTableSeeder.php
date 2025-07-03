<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('agents')->insert([
            ['agent' => 'tutor', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'step-tutor', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'summarizer', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
