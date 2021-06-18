<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesQrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_qrs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sale');
            $table->unsignedBigInteger('gasoline_id');
            $table->double('liters');
            $table->integer('points');
            $table->double('payment');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('client_id');
            $table->integer('no_bomb');
            $table->unsignedBigInteger('main_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('gasoline_id')->references('id')->on('gasolines')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('station_id')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('client_id')->references('id')->on('clients')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('main_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('sale_id')->references('id')->on('users')
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
        Schema::dropIfExists('sales_qrs');
    }
}
