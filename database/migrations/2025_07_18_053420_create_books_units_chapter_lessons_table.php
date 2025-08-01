<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksUnitsChapterLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('book', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('subject_name');
        $table->string('grade_level');
        $table->text('description')->nullable();
        $table->timestamps();
    });

     Schema::create('units', function (Blueprint $table) {
        $table->id();
        $table->foreignId('book_id')->constrained('book')->onDelete('cascade');
        $table->string('title');
        $table->integer('unit_number');
        $table->timestamps();
    });

     Schema::create('chapter', function (Blueprint $table) {
        $table->id();
        $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
        $table->string('chapter_title');
        $table->integer('chapter_number');
        $table->timestamps();
    });

     Schema::create('lesson', function (Blueprint $table) {
        $table->id();
        $table->foreignId('chapter_id')->constrained('chapter')->onDelete('cascade');
        $table->string('lesson_title');
        $table->integer('lesson_number');
        $table->text('content')->nullable();
        $table->timestamps();
    });

    Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('book')->onDelete('cascade');
            $table->foreignId('chapter_id')->constrained('chapter')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lesson')->onDelete('cascade');
            $table->integer('global_faiss_id')->nullable();
            $table->longText('text');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('books_units_chapter_lessons');
    }
}