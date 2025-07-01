<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->bigIncrements('id'); // bigint + identity + primary key
            $table->timestampTz('created_at')->useCurrent(); // timestamp with time zone default now()
            
            $table->unsignedBigInteger('session_id')->nullable(); // FK to sessions.id
            $table->string('input_type')->nullable(); // character varying
            $table->string('topic')->nullable(); // character varying
            $table->string('pdf_filename')->nullable(); // character varying

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
        Schema::dropIfExists('requests');
    }
}
