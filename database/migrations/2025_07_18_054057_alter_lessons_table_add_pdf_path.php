<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterLessonsTableAddPdfPath extends Migration
{
    public function up()
    {
        Schema::table('lesson', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('lesson_number');
            $table->dropColumn('content');
        });
    }

    public function down()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->text('content')->nullable();
            $table->dropColumn('pdf_path');
        });
    }
}