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
        $informationalTextId = DB::table('agents')->where('agent', 'informational')->value('id');
        $chatWithDocsId = DB::table('agents')->where('agent', 'chatwithdocs')->value('id');
        $mathReviewId = DB::table('agents')->where('agent', 'mathreview')->value('id');
        $makeitRelevantId = DB::table('agents')->where('agent', 'makeitrelevant')->value('id');
        $rewriterId = DB::table('agents')->where('agent', 'rewriter')->value('id');
        $scaffolderId = DB::table('agents')->where('agent', 'scaffolder')->value('id');
        $explanationsId = DB::table('agents')->where('agent', 'explanations')->value('id');
        $responderId = DB::table('agents')->where('agent', 'responder')->value('id');
        $emailWriterId = DB::table('agents')->where('agent', 'emailwriter')->value('id');
        $textLevelerId = DB::table('agents')->where('agent', 'leveler')->value('id');
        $thankYouId = DB::table('agents')->where('agent', 'thankyou')->value('id');
        $ideaGeneratorId = DB::table('agents')->where('agent', 'ideagenerator')->value('id');
        $contentCreatorId = DB::table('agents')->where('agent', 'contentcreator')->value('id');
        $fivequestionId = DB::table('agents')->where('agent', 'five-question')->value('id');
        $proofreaderId = DB::table('agents')->where('agent', 'proofreader')->value('id');
        $realworldId = DB::table('agents')->where('agent', 'realworld')->value('id');
        $sentencestarterId = DB::table('agents')->where('agent', 'sentence-starter')->value('id');
        $studyhabitsID = DB::table('agents')->where('agent', 'study-habits')->value('id');
        $translatorId = DB::table('agents')->where('agent', 'translator')->value('id');
        $socialStoriesId = DB::table('agents')->where('agent', 'social-stories')->value('id');
        $characterBotId =DB::table('agents')->where('agent', 'characterbot')->value('id');
        $ragchatbotId =DB::table('agents')->where('agent', 'ragchatbot')->value('id');

        DB::table('agent_parameters')->insert([
            [
                'agent_id' => $tutorId,
                'parameter' => 'grade_level',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $stepTutorId,
                'parameter' => 'grade_level',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $summarizerId,
                'parameter' => 'conditions',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'agent_id' => $emailWriterId,
                'parameter' => 'content',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'agent_id' => $thankYouId,
                'parameter' => 'reason',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'agent_id' => $ideaGeneratorId,
                'parameter' => 'Grade level',
                'parameter_value' => 'Pre-K,Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6,Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12,University,Professional Staff',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $contentCreatorId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Pre-K,Kindergarten,Grade 1,Grade 2,Grade 3,Grade 4,Grade 5,Grade 6,Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12,University,Professional Staff',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $contentCreatorId,
                'parameter' => 'length',
                'parameter_value' => '1 paragraph,2 paragraphs,3 paragraphs,1 page,2 pages',
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
                'agent_id' => $mathReviewId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Middle School,High School,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $mathReviewId,
                'parameter' => 'number_of_problems',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],            
            [
                'agent_id' => $mathReviewId,
                'parameter' => 'math_content',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ], 
            [
                'agent_id' => $mathReviewId,
                'parameter' => 'additional_criteria',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $makeitRelevantId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Middle School,High School,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $makeitRelevantId,
                'parameter' => 'interests',
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
            [
                'agent_id' => $fivequestionId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Junior High,Senior High,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $proofreaderId,
                'parameter' => 'profile',
                'parameter_value' => 'Academic,Casual,Concise',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $realworldId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Junior High,Senior High,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $sentencestarterId,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Junior High,Senior High,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $studyhabitsID,
                'parameter' => 'grade_level',
                'parameter_value' => 'Kindergarten,Elementary,Junior High,Senior High,College',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $translatorId,
                'parameter' => 'target_language',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $socialStoriesId,
                'parameter' => 'grade_level',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $characterBotId,
                'parameter' => 'grade_level',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $characterBotId,
                'parameter' => 'character',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $stepTutorId,
                'parameter' => 'character',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'agent_id' => $ragchatbotId,
                'parameter' => 'chapter_number',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
             [
                'agent_id' => $ragchatbotId,
                'parameter' => 'book_id',
                'parameter_value' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            



        ]);
    }
}
