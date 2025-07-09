<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableAddAgentPromptColumninMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_prompt_id')->nullable()->after('agent_id');

            $table->foreign('agent_prompt_id')
                  ->references('id')
                  ->on('agent_prompts')
                  ->onDelete('set null'); // Optional: clean up if prompt deleted
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['agent_prompt_id']);
            $table->dropColumn('agent_prompt_id');
        });
    }
}
