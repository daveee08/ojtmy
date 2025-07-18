<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChunksTable extends Migration
{
    public function up()
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('book')->onDelete('cascade');
            $table->foreignId('chapter_number')->constrained('chapter')->onDelete('cascade');
            $table->integer('global_faiss_id')->nullable();
            $table->longText('text');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chunks');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('books');
    }
}
