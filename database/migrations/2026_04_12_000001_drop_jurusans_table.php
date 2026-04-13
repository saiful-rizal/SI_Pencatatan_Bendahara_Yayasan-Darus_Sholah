<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('jurusans');
    }

    public function down(): void
    {
        // Intentionally left empty because jurusan feature has been removed.
    }
};
