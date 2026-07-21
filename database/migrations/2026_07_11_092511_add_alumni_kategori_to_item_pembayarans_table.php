<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_pembayarans') || !Schema::hasColumn('item_pembayarans', 'berlaku_untuk')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE item_pembayarans MODIFY berlaku_untuk ENUM('mondok', 'non_mondok', 'alumni', 'non_alumni', 'semua') NOT NULL DEFAULT 'semua'");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('item_pembayarans') || !Schema::hasColumn('item_pembayarans', 'berlaku_untuk')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::table('item_pembayarans')
                ->whereIn('berlaku_untuk', ['alumni', 'non_alumni'])
                ->update(['berlaku_untuk' => 'semua']);

            DB::statement("ALTER TABLE item_pembayarans MODIFY berlaku_untuk ENUM('mondok', 'non_mondok', 'semua') NOT NULL DEFAULT 'semua'");
        }
    }
};
