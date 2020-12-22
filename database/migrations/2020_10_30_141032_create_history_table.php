<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('folio');
            $table->string('folio_exchange');
            $table->string('numero');
            $table->dateTime('todate_cerficado')->nullable();
            $table->unsignedBigInteger('id_admin');
            $table->string('number_usuario');
            $table->integer('id_product')->nullable();
            $table->integer('id_award')->nullable();
            $table->unsignedBigInteger('id_station');
            $table->integer('id_exchange')->nullable();
            $table->integer('points');
            $table->integer('value');
            $table->dateTime('todate');
            $table->timestamps();

            $table->foreign('id_admin')->references('id')->on('users')
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
        Schema::dropIfExists('history');
    }
}
