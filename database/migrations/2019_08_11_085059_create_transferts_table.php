<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransfertsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transferts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('mail_id');
            $table->unsignedBigInteger('destination_account_id');
            $table->unsignedBigInteger('status')->default(0);
            $table->string('message')->nullable();
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
        Schema::dropIfExists('transferts');
    }
}
