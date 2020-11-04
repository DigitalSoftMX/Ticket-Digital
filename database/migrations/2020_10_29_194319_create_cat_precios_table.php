<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCatPreciosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('cat_precios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('num_ticket');
            $table->double('costo');
            $table->double('costo_timbre');
            $table->double('costo_admin');
            $table->double('costo_timbre_admin');
            $table->double('ganancia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql')->dropIfExists('cat_precios');
    }
}
