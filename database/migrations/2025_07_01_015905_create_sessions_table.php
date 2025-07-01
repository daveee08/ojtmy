<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->bigIncrements('id'); // Primary key with bigint + identity
            $table->timestampTz('created_at')->useCurrent(); // Timestamp with time zone default now()
            
            $table->unsignedBigInteger('user_id')->nullable(); // FK to users.id
            $table->unsignedBigInteger('agent_id')->nullable(); // FK to agents.id

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
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
        Schema::dropIfExists('sessions');
    }
}
// This migration creates the sessions table with foreign keys to users and agents.