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
            ['agent' => 'emailWriter', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'leveler', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'informational', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'chatwithdocs', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'rewriter', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'scaffolder', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'explanations', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'responder', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'thankyou', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'ideagenerator', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'contentcreator', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'five-question', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'proofreader', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'realworld', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'sentence-starter', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'study-habits', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'translator', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'social-stories', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'mathreview', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'makeitrelevant', 'created_at' => now(), 'updated_at' => now()],
            ['agent' => 'characterbot', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
