<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('siswas', 'deleted_at')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('tagihans', 'deleted_at')) {
            Schema::table('tagihans', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('item_pembayarans', 'deleted_at')) {
            Schema::table('item_pembayarans', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('siswas', 'deleted_at')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('tagihans', 'deleted_at')) {
            Schema::table('tagihans', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('item_pembayarans', 'deleted_at')) {
            Schema::table('item_pembayarans', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
