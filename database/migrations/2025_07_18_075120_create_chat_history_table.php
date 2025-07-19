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
            $table->integer('session_id')->constrained('sessions')->onDelete('cascade');
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

