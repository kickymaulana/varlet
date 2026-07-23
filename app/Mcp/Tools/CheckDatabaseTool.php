<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tool;

#[Description('Memeriksa koneksi database dan menampilkan daftar tabel beserta jumlah record-nya')]
#[IsReadOnly]
class CheckDatabaseTool extends Tool
{
    public function handle(Request $request): Response
    {
        $tableFilter = $request->string('table');

        try {
            DB::connection()->getPdo();
            $connectionName = DB::connection()->getName();
            $databaseName = DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            return Response::error("Gagal koneksi database: " . $e->getMessage());
        }

        $tables = Schema::getTableListing();
        $tableDetails = [];

        foreach ($tables as $table) {
            if ($tableFilter && !str_contains($table, $tableFilter)) {
                continue;
            }
            $count = DB::table($table)->count();
            $tableDetails[] = [
                'table' => $table,
                'records' => $count,
            ];
        }

        $info = [
            'connection' => $connectionName,
            'database' => $databaseName,
            'connected' => true,
            'total_tables' => count($tables),
            'tables' => $tableFilter ? $tableDetails : $tableDetails,
        ];

        return Response::text(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()
                ->description('Nama tabel yang ingin dicek (opsional). Kosongkan untuk semua tabel.')
                ->default(''),
        ];
    }
}
