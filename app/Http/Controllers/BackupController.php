<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function downloadJson()
    {
        $tables = ['siswas', 'item_pembayarans', 'tagihans', 'tagihan_potongans', 'pembayaran_tagihans'];

        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $payload['tables'][$table] = DB::table($table)->get();
            }
        }

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT);
        }, 'backup_keuangan_' . now()->format('Ymd_His') . '.json', ['Content-Type' => 'application/json']);
    }
}
