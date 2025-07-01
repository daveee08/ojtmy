<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint + identity + primary key
            $table->unsignedBigInteger('session_id')->nullable(); // FK to sessions.id

            $table->string('sender')->nullable()->default('not null'); // matches default
            $table->string('content')->nullable()->default('not null'); // matches default

            $table->timestampTz('created_at')->useCurrent(); // timestamp with time zone default now()

            // Foreign key constraint
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
// This migration creates the messages table with a foreign key to sessions.
// The 'sender' and 'content' fields are nullable with a default value of 'not