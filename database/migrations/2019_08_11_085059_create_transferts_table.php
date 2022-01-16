<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
			$table->tinyInteger('status')->default(0);
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
