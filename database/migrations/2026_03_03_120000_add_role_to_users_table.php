<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('admin_anggota')->after('password');
            $table->boolean('is_super_permanent')->default(false)->after('role');
        });

        $firstUserId = DB::table('users')->min('id');
        if ($firstUserId) {
            DB::table('users')->where('id', $firstUserId)->update([
                'role' => 'super_admin',
                'is_super_permanent' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_super_permanent']);
        });
    }
};
