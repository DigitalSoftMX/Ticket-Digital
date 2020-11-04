<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacturaEmisorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('factura_emisor', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre');
            $table->string('rfc');
            $table->string('regimenfiscal');
            $table->string('direccionfiscal');
            $table->string('cp');
            $table->string('emailfiscal');
            $table->string('archivocer');
            $table->string('archivokey');
            $table->string('consituacion');
            $table->string('nocertificado');
            $table->string('passcerti')->nullable();
            $table->string('avredescripcion1');
            $table->string('descripcion1')->nullable();
            $table->string('avredescripcion2');
            $table->string('descripcion2')->nullable();
            $table->string('avredescripcion3');
            $table->string('descripcion3')->nullable();
            $table->string('cuenta')->nullable();
            $table->string('pass');
            $table->string('user');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_estacion');
            $table->unsignedBigInteger('id_empresa');
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
        Schema::connection('mysql')->dropIfExists('factura_emisor');
    }
}
