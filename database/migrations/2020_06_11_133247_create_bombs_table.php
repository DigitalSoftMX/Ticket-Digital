<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBombsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('bombs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('no_bomb');
            $table->unsignedBigInteger('island_id');
            $table->timestamps();

            $table->foreign('island_id')->references('id')->on('islands')
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
        Schema::connection('mysql')->dropIfExists('bombs');
    }
}
