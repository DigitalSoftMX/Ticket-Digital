<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAwardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('awards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('points');
            $table->integer('value');
            $table->string('img');
            $table->unsignedBigInteger('id_status');
            $table->integer('id_station')->nullable();
            $table->integer('active');
            $table->timestamps();

            $table->foreign('id_status')->references('id')->on('cat_status')
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
        Schema::connection('mysql')->dropIfExists('awards');
    }
}
