<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksChaptersChunksTables extends Migration
{
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('source', 500)->nullable();               // File path or URL
            $table->string('original_filename', 255)->nullable();
            $table->string('faiss_index_path', 500)->nullable();
            $table->longText('description')->nullable();             // from ALTER
            $table->string('grade_level', 500)->nullable();          // from ALTER
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->integer('chapter_number');
            $table->string('chapter_title', 255)->nullable();
            $table->integer('start_page')->nullable();
            $table->integer('end_page')->nullable();               // from ALTER
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
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
