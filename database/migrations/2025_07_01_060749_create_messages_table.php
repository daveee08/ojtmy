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
            $table->unsignedBigInteger('session_id'); // FK to sessions.id
            $table->unsignedBigInteger('parameter_inputs');
            $table->enum('sender', ['ai', 'human']);
            $table->text('topic');
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('parameter_inputs')->references('id')->on('parameter_inputs')->onDelete('cascade');
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