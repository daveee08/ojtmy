<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        $this->call([
            AgentsTableSeeder::class,
            AgentParametersTableSeeder::class,
            MyAgentsTableSeeder::class,
            MyAgentParametersTableSeeder::class,
            UserSeeder::class,
            ParameterReferenceSeeder::class,
        ]);
    }
}
