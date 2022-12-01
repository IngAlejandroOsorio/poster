<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InoviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
		DB::table('invoices')->insert([
			'product' => "Burger",
			'dish' => "true",		
			'category' => "Top screen",
			'prepack' => "false",
			'ingredient' => "",
			'workshop' => "",
			'ingredients_category' => "",
			'book_transaction' => "1e48c4420b7073bc11916c6c1de226bb",
			'cash_shift_transaction' => "1966e694bad90686516f99cdf432800fdca39290"
		]);
    }
}
