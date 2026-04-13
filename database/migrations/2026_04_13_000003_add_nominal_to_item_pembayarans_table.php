<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_pembayarans') || Schema::hasColumn('item_pembayarans', 'nominal')) {
            return;
        }

        Schema::table('item_pembayarans', function (Blueprint $table) {
            $table->decimal('nominal', 15, 2)->default(0)->after('nama_item');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('item_pembayarans') || !Schema::hasColumn('item_pembayarans', 'nominal')) {
            return;
        }

        Schema::table('item_pembayarans', function (Blueprint $table) {
            $table->dropColumn('nominal');
        });
    }
};
