<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MyAgentParametersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define agent parameters. Each inner array represents a set of parameters for a specific agent.
        // The 'agent_name' key is used to look up the agent's ID.
        $agentParameters = [
            'teacherjokes' => [
                ['parameter' => 'grade_level', 'parameter_value' => '1st Grade'],
                ['parameter' => 'additional_customization', 'parameter_value' => 'none'],
            ],
            'tonguetwister' => [
                ['parameter' => 'topic', 'parameter_value' => 'animals'],
                ['parameter' => 'grade_level', 'parameter_value' => '1st Grade'],
            ],
            'quizme' => [
                ['parameter' => 'topic', 'parameter_value' => 'science'],
                ['parameter' => 'grade_level', 'parameter_value' => '5th Grade'],
                ['parameter' => 'num_questions', 'parameter_value' => '10'],
            ],
            'coachsportprac' => [
                ['parameter' => 'grade_level', 'parameter_value' => 'High School'],
                ['parameter' => 'length_of_practice', 'parameter_value' => '1 hour'],
                ['parameter' => 'sport', 'parameter_value' => 'Basketball'],
                ['parameter' => 'additional_customization', 'parameter_value' => 'focus on passing drills'],
            ],
            'booksuggestion' => [
                ['parameter' => 'interests', 'parameter_value' => 'fantasy'],
                ['parameter' => 'grade_level', 'parameter_value' => 'Adult'],
            ],
            'qotd' => [
                ['parameter' => 'topic', 'parameter_value' => 'inspiration'],
                ['parameter' => 'grade_level', 'parameter_value' => 'University'],
            ],
        ];

        foreach ($agentParameters as $agentName => $parameters) {
            $agentId = DB::table('agents')->where('agent', $agentName)->value('id');

            if ($agentId) {
                foreach ($parameters as $param) {
                    // Check if the parameter for this agent already exists before inserting
                    $exists = DB::table('agent_parameters')
                                ->where('agent_id', $agentId)
                                ->where('parameter', $param['parameter'])
                                ->exists();

                    if (!$exists) {
                        DB::table('agent_parameters')->insert([
                            'agent_id' => $agentId,
                            'parameter' => $param['parameter'],
                            'parameter_value' => $param['parameter_value'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $this->command->info("Parameter '{$param['parameter']}' for agent '{$agentName}' already exists. Skipping insertion.");
                    }
                }
            } else {
                $this->command->error("Agent '{$agentName}' not found. Skipping parameter insertion.");
            }
        }
    }
} 