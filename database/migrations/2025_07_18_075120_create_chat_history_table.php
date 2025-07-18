<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatHistoryTable extends Migration
{
    public function up(): void
    {
        Schema::create('chat_rag_history', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100);
            $table->integer('turn');
            $table->enum('role', ['user', 'ai']);
            $table->longText('message');
            $table->timestamps(); // Includes created_at and updated_at
            $table->index('session_id');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_history');
    }
}

