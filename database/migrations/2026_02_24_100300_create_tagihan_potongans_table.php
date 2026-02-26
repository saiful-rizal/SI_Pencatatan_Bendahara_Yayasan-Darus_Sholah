<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('tagihan_potongans')) {
			return;
		}

		Schema::create('tagihan_potongans', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tagihan_id')->constrained('tagihans')->cascadeOnDelete();
			$table->date('tanggal_potongan');
			$table->string('keterangan');
			$table->decimal('nominal_potongan', 15, 2);
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tagihan_potongans');
	}
};
