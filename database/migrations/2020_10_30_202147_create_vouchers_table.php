<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVouchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->integer('points');
            $table->integer('value');
            $table->unsignedBigInteger('id_status');
            $table->unsignedBigInteger('id_station');
            $table->unsignedBigInteger('id_count_voucher')->nullable();
            $table->integer('total_voucher');
            $table->timestamps();

            $table->foreign('id_status')->references('id')->on('cat_status')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_station')->references('id')->on('station')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_count_voucher')->references('id')->on('count_vouchers')
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
        Schema::dropIfExists('vouchers');
    }
}
