<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_pembayarans')) {
            return;
        }

        if (!Schema::hasColumn('item_pembayarans', 'berlaku_untuk')) {
            Schema::table('item_pembayarans', function (Blueprint $table) {
                $table->enum('berlaku_untuk', ['mondok', 'non_mondok', 'semua'])
                    ->default('semua')
                    ->after('jenis_item');
            });

            if (Schema::hasColumn('item_pembayarans', 'khusus_mondok')) {
                DB::table('item_pembayarans')
                    ->where('khusus_mondok', 1)
                    ->update(['berlaku_untuk' => 'mondok']);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('item_pembayarans')) {
            return;
        }

        if (Schema::hasColumn('item_pembayarans', 'berlaku_untuk')) {
            Schema::table('item_pembayarans', function (Blueprint $table) {
                $table->dropColumn('berlaku_untuk');
            });
        }
    }
};
