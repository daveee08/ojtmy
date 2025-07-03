<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParameterInputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parameter_inputs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('input');
            $table->unsignedBigInteger('parameter_id');
            $table->timestamps();
            $table->unsignedBigInteger('agent_id');
            
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
            $table->foreign('parameter_id')->references('id')->on('agent_parameters')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parameter_inputs');
    }
}