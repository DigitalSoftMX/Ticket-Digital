<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConjuntoMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conjunto_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('membresia');
            $table->string('number_usuario');
            $table->integer('puntos');
            $table->timestamps();

            $table->foreign('number_usuario')->references('username')->on('users')
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
        Schema::dropIfExists('conjunto_memberships');
    }
}
