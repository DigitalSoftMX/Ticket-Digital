<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('serie')->nullable();
            $table->timestamp('fecha')->nullable();
            $table->longText('sello')->nullable();
            $table->string('formapago', 10)->nullable();
            $table->longText('nocertificado')->nullable();
            $table->longText('certificado')->nullable();
            $table->longText('folio')->nullable();
            $table->longText('uuid')->nullable();
            $table->timestamp('fechatimbrado')->nullable();
            $table->longText('sellocfd')->nullable();
            $table->double('subtotal', 8, 2)->nullable();
            $table->double('descuento', 8, 2)->nullable();
            $table->string('moneda', 10)->nullable();
            $table->double('tipocambio', 2, 1)->nullable();
            $table->double('total', 8, 2)->nullable();
            $table->string('tipocombrobante', 10)->nullable();
            $table->string('metodopago', 10)->nullable();
            $table->integer('lugarexpedicion')->nullable();
            $table->unsignedBigInteger('id_emisor')->nullable();
            $table->unsignedBigInteger('id_receptor')->nullable();
            $table->string('usocfdi')->nullable();
            $table->integer('Claveproserv')->nullable();
            $table->double('cantidad', 8, 2)->nullable();
            $table->string('claveunidad', 10)->nullable();
            $table->string('descripcion', 50)->nullable();
            $table->string('noidentificacion')->nullable();
            $table->double('valorunitario', 8, 6)->nullable();
            $table->double('importe', 8, 2)->nullable();
            $table->double('base', 8, 2)->nullable();
            $table->double('iva', 8, 2)->nullable();
            $table->double('descuentoD', 8, 2)->nullable();
            $table->unsignedBigInteger('id_estacion');
            $table->unsignedBigInteger('id_bomba')->nullable();
            $table->string('archivoxml')->nullable();
            $table->string('archivopdf')->nullable();
            $table->string('estatus')->nullable();
            $table->timestamps();

            $table->foreign('id_emisor')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_receptor')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_estacion')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_bomba')->references('id')->on('cat_bombas')
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
        Schema::dropIfExists('facturas');
    }
}
