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
            $table->bigIncrements('id'); // bigint + identity + primary key
            $table->unsignedBigInteger('agent_id')->nullable(); // FK to agents.id
            $table->string('parameter_name')->nullable(); // character varying null
            $table->timestampTz('created_at')->useCurrent(); // timestamp with time zone default now()

            // Foreign key constraint
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('set null');
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