<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentPromptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_prompts', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('agent_id'); // FK to agents
            $table->longText('prompt');             // Long agent prompt content

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agent_prompt');
    }
}
