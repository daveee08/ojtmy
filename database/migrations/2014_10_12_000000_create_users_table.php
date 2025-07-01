<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id'); // equivalent to bigint + primary key + identity
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('backup_password')->nullable();
            $table->timestampTz('created_at')->useCurrent(); // with timezone and default now()
            $table->boolean('is_admin')->nullable()->default(false);

            // If you want to keep Laravel's default timestamps:
            // $table->timestampsTz(); // creates created_at and updated_at with timezone

            // But if you're using only custom created_at, and no updated_at, skip the above
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
