<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRegisterTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('register_times', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dispatcher_id');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('schedule_id');
            $table->timestamps();

            $table->foreign('dispatcher_id')->references('id')->on('dispatchers')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('station_id')->references('id')->on('stations')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('schedule_id')->references('id')->on('schedules')
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
        Schema::connection('mysql')->dropIfExists('register_times');
    }
}
