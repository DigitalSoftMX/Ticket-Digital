<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('pagos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('pago');
            $table->integer('num_timbres')->nullable();
            $table->text('archivo')->nullable();
            $table->integer('autorizado')->nullable();
            $table->unsignedBigInteger('id_estacion')->nullable();
            $table->unsignedBigInteger('id_empresa')->nullable();
            $table->timestamps();

            $table->foreign('id_estacion')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_empresa')->references('id')->on('empresas')
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
        Schema::connection('mysql')->dropIfExists('pagos');
    }
}
