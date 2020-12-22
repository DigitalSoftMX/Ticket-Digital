<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCanjesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('canjes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('identificador');
            $table->string('conta');
            $table->unsignedBigInteger('id_estacion');
            $table->integer('punto');
            $table->integer('value');
            $table->string('number_usuario');
            $table->string('generado')->nullable();
            $table->unsignedBigInteger('estado');
            $table->string('descrip');
            $table->string('image')->nullable();
            $table->dateTime('estado_uno')->nullable();
            $table->dateTime('estado_dos')->nullable();
            $table->dateTime('estado_tres')->nullable();
            $table->string('ip_user')->nullable();
            $table->timestamps();

            $table->foreign('id_estacion')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('estado')->references('id')->on('cat_state')
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
        Schema::dropIfExists('canjes');
    }
}
