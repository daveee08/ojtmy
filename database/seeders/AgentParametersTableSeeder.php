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
        $textLevelerId = DB::table('agents')->where('agent', 'leveler')->value('id');
        $informationalTextId = DB::table('agents')->where('agent', 'informational')->value('id');
        $chatWithDocsId = DB::table('agents')->where('agent', 'chatwithdocs')->value('id');
        $rewriterId = DB::table('agents')->where('agent', 'rewriter')->value('id');
        $scaffolderId = DB::table('agents')->where('agent', 'scaffolder')->value('id');
        $explanationsId = DB::table('agents')->where('agent', 'explanations')->value('id');
        $responderId = DB::table('agents')->where('agent', 'responder')->value('id');

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
            [
                'agent_id' => $textLevelerId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Middle School,High School,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $textLevelerId,
                'parameter' => 'Learning Type',
                'parameter_value' => 'Slow,Average,Fast',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $informationalTextId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Middle School,High School,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $informationalTextId,
                'parameter' => 'text_length',
                'parameter_value' => '1 paragraph,1 page,2 pages,3 pages',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $informationalTextId,
                'parameter' => 'text_type',
                'parameter_value' => 'Literary,Expository,Argumentative or Persuasive,Procedural',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $chatWithDocsId,
                'parameter' => 'custom_instruction',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $rewriterId,
                'parameter' => 'custom_instruction',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $scaffolderId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Middle School,High School,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $scaffolderId,
                'parameter' => 'literal_questions',
                'parameter_value' => '1,2,3,4,5,6,7,8,9,10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $scaffolderId,
                'parameter' => 'vocab_limit',
                'parameter_value' => '1,2,3,4,5,6,7,8,9,10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $explanationsId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6,Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $responderId,
                'parameter' => 'author',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $responderId,
                'parameter' => 'intent',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $responderId,
                'parameter' => 'tone',
                'parameter_value' => 'Formal,Friendly,Concise,Apologetic,Assertive',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
