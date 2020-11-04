<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('first_surname')->nullable();
            $table->string('second_surname')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->char('sex')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->integer('active')->nullable();
            $table->string('password');
            $table->text('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->dropIfExists('users');
    }
}
