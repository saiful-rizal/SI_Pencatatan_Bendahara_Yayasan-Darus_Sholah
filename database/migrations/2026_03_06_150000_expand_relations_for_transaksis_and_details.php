<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transaksis')) {
            Schema::table('transaksis', function (Blueprint $table) {
                if (!Schema::hasColumn('transaksis', 'siswa_id')) {
                    $table->foreignId('siswa_id')->nullable()->after('kategori')->constrained('siswas')->nullOnDelete();
                }

                if (!Schema::hasColumn('transaksis', 'pembayaran_tagihan_id')) {
                    $table->foreignId('pembayaran_tagihan_id')->nullable()->after('siswa_id')->constrained('pembayaran_tagihans')->nullOnDelete();
                    $table->unique('pembayaran_tagihan_id', 'transaksis_pembayaran_tagihan_id_unique');
                }

                if (!Schema::hasColumn('transaksis', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('catatan')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('transaksis', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('detail_transaksis')) {
            Schema::table('detail_transaksis', function (Blueprint $table) {
                if (!Schema::hasColumn('detail_transaksis', 'item_pembayaran_id')) {
                    $table->foreignId('item_pembayaran_id')->nullable()->after('transaksi_id')->constrained('item_pembayarans')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('detail_transaksis')) {
            Schema::table('detail_transaksis', function (Blueprint $table) {
                if (Schema::hasColumn('detail_transaksis', 'item_pembayaran_id')) {
                    $table->dropConstrainedForeignId('item_pembayaran_id');
                }
            });
        }

        if (Schema::hasTable('transaksis')) {
            Schema::table('transaksis', function (Blueprint $table) {
                if (Schema::hasColumn('transaksis', 'updated_by')) {
                    $table->dropConstrainedForeignId('updated_by');
                }

                if (Schema::hasColumn('transaksis', 'created_by')) {
                    $table->dropConstrainedForeignId('created_by');
                }

                if (Schema::hasColumn('transaksis', 'pembayaran_tagihan_id')) {
                    $table->dropUnique('transaksis_pembayaran_tagihan_id_unique');
                    $table->dropConstrainedForeignId('pembayaran_tagihan_id');
                }

                if (Schema::hasColumn('transaksis', 'siswa_id')) {
                    $table->dropConstrainedForeignId('siswa_id');
                }
            });
        }
    }
};
