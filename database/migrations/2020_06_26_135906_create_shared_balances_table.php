<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSharedBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::connection('mysql')->create('shared_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('transmitter_id');
            $table->unsignedBigInteger('receiver_id');
            $table->float('balance');
            $table->unsignedBigInteger('station_id');
            $table->integer('status');
            $table->timestamps();

            $table->foreign('transmitter_id')->references('id')->on('clients')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('receiver_id')->references('id')->on('clients')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('station_id')->references('id')->on('stations')
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
        Schema::connection('mysql')->dropIfExists('shared_balances');
    }
}
