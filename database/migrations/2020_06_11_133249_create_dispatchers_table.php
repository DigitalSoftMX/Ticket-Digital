<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDispatchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('dispatchers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('dispatcher_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger("station_id");
            $table->unsignedBigInteger('no_island');
            $table->unsignedBigInteger('no_bomb');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('station_id')->references('id')->on('stations')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('no_island')->references('id')->on('islands')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('no_bomb')->references('id')->on('bombs')
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
        Schema::connection('mysql')->dropIfExists('dispatchers');
    }
}
