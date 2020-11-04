<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChangeMembershipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql')->create('change_memberships', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('qr_membership');
            $table->unsignedBigInteger('id_users');
            $table->string('qr_membership_old');
            $table->dateTime('todate');
            $table->timestamps();

            $table->foreign('id_users')->references('id')->on('users')
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
        Schema::connection('mysql')->dropIfExists('change_memberships');
    }
}
