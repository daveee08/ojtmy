<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentParametersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_parameters', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('agent_id');
            $table->string('parameter');
            $table->string('parameter_value')-> nullable(); // More flexible than enum
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
        Schema::dropIfExists('agent_parameters');
    }
}
// This migration creates the agent_parameters table with a foreign key to agents.
