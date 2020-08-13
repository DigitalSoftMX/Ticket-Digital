<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDispatcherHistoryPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('dispatcher_history_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dispatcher_id');
            $table->unsignedBigInteger('gasoline_id');
            $table->double('liters');
            $table->double('payment');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('time_id');
            $table->timestamps();

            $table->foreign('dispatcher_id')->references('id')->on('dispatchers')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('gasoline_id')->references('id')->on('gasolines')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('schedule_id')->references('id')->on('schedules')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('station_id')->references('id')->on('stations')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('client_id')->references('id')->on('clients')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('time_id')->references('id')->on('register_times')
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
        Schema::connection('mysql')->dropIfExists('dispatcher_history_payments');
    }
}
