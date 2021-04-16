<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('station', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('address');
            $table->string('phone');
            $table->string('email');
            $table->integer('total_timbres')->nullable();
            $table->integer('total_facturas')->nullable();
            $table->unsignedBigInteger('id_empresa');
            $table->unsignedBigInteger('id_type')->nullable();
            $table->string('number_station', 5);
            $table->integer('active')->nullable();
            $table->boolean('lealtad');
            $table->string('dns')->nullable();
            $table->timestamp('fail')->nullable();
            $table->string('ip')->nullable();
            $table->string('image');
            $table->timestamps();

            $table->foreign('id_empresa')->references('id')->on('empresas')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_type')->references('id')->on('cat_type')
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
        Schema::dropIfExists('station');
    }
}
