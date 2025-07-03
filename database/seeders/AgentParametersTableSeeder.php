<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentParametersTableSeeder extends Seeder
{
    public function run()
    {
        $tutorId = DB::table('agents')->where('agent', 'tutor')->value('id');
        $stepTutorId = DB::table('agents')->where('agent', 'step-tutor')->value('id');
        $summarizerId = DB::table('agents')->where('agent', 'summarizer')->value('id');


        DB::table('agent_parameters')->insert([
            [
                'agent_id' => $tutorId,
                'parameter' => 'language',
                'parameter_value' => 'English',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $stepTutorId,
                'parameter' => 'mode',
                'parameter_value' => 'step-by-step',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $summarizerId,
                'parameter' => 'grade_level',
                'parameter_value' => '1st Grade',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
