<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Artisan;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tool;

#[Description('Menjalankan perintah Artisan terbatas untuk inspeksi project (misal: route:list, model:show, migrate:status)')]
#[IsReadOnly]
class ArtisanRunnerTool extends Tool
{
    public function handle(Request $request): Response
    {
        $command = $request->string('command');

        // Buka pembatasan hanya untuk command yang aman/read-only
        $allowedCommands = ['route:list', 'migrate:status', 'about', 'config:show'];

        $baseCommand = explode(' ', $command)[0];

        if (!in_array($baseCommand, $allowedCommands)) {
            return Response::error("Command '{$baseCommand}' tidak diizinkan demi keamanan. Hanya diizinkan: " . implode(', ', $allowedCommands));
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();
            return Response::text($output ?: 'Command executed successfully with no output.');
        } catch (\Throwable $e) {
            return Response::error("Gagal menjalankan artisan: " . $e->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'command' => $schema->string()
                ->description('Command artisan yang ingin dijalankan (contoh: "route:list")')
                ->required(),
        ];
    }
}
