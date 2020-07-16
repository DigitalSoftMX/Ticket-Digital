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
            $table->string('name');/*  */
            $table->string('first_surname')->nullable();/*  */
            $table->string('second_surname')->nullable();/*  */
            /* $table->string('username'); Falta el username*/
            $table->string('email')->unique();/*  */
            $table->char('sex');/*  */
            $table->string('phone')->nullable();/*  */
            $table->integer('active');
            $table->string('password');/*  */
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
