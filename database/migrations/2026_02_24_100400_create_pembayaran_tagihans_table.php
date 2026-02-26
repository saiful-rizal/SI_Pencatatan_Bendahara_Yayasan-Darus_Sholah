<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('pembayaran_tagihans')) {
			return;
		}

		Schema::create('pembayaran_tagihans', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tagihan_id')->constrained('tagihans')->cascadeOnDelete();
			$table->date('tanggal_bayar');
			$table->decimal('nominal_bayar', 15, 2);
			$table->string('metode_bayar')->nullable();
			$table->text('catatan')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('pembayaran_tagihans');
	}
};
