<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDispatcherTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('dispatcher', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('qr_dispatcher');
            $table->integer('active');
            $table->dateTime('todate');
            $table->unsignedBigInteger('id_users');
            $table->unsignedBigInteger('id_station');
            $table->timestamps();

            $table->foreign('id_users')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_station')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->dropIfExists('dispatcher');
    }
}