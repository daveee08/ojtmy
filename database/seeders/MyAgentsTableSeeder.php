<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MyAgentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define the agents to be added, ensuring they don't already exist to prevent duplicates
        $agents = [
            'teacherjokes',
            'tonguetwister',
            'quizme',
            'coachsportprac',
            'booksuggestion',
            'qotd',
        ];

        foreach ($agents as $agentName) {
            // Check if the agent already exists before inserting
            $exists = DB::table('agents')->where('agent', $agentName)->exists();

            if (!$exists) {
                DB::table('agents')->insert([
                    'agent' => $agentName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $this->command->info("Agent '{$agentName}' already exists. Skipping insertion.");
            }
        }
    }
} 