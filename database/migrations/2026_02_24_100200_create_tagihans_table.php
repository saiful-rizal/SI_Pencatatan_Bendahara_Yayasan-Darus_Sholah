<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		if (Schema::hasTable('tagihans')) {
			return;
		}

		Schema::create('tagihans', function (Blueprint $table) {
			$table->id();
			$table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
			$table->foreignId('item_pembayaran_id')->constrained('item_pembayarans')->cascadeOnDelete();
			$table->unsignedTinyInteger('periode_bulan')->nullable();
			$table->unsignedSmallInteger('periode_tahun')->nullable();
			$table->decimal('nominal_awal', 15, 2);
			$table->date('jatuh_tempo')->nullable();
			$table->enum('status', ['belum_lunas', 'sebagian', 'lunas'])->default('belum_lunas');
			$table->text('catatan')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('tagihans');
	}
};
