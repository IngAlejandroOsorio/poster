<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
			$table->string('product');
			$table->string('dish');			
			$table->string('category');
			$table->string('prepack');
			$table->string('ingredient');
			$table->string('workshop');
			$table->string('ingredients_category');
			$table->string('book_transaction');
			$table->string('cash_shift_transaction');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
