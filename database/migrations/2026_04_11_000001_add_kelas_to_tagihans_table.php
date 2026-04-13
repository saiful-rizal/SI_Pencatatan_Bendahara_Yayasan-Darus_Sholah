<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('tagihans', function (Blueprint $table) {
			if (!Schema::hasColumn('tagihans', 'kelas')) {
				$table->string('kelas')->nullable()->after('item_pembayaran_id')->index();
			}
		});
	}

	public function down(): void
	{
		Schema::table('tagihans', function (Blueprint $table) {
			if (Schema::hasColumn('tagihans', 'kelas')) {
				$table->dropColumn('kelas');
			}
		});
	}
};
