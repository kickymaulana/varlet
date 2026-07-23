<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\ProjectOverviewResource;
use App\Mcp\Tools\ArtisanRunnerTool;
use App\Mcp\Tools\CheckDatabaseTool;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server;

#[Name('Varlet Assistant Server')]
#[Version('1.0.0')]
#[Instructions('Server ini membantu OpenCode AI memahami struktur aplikasi Varlet dan membuatkan fitur berbasis Laravel MCP terbaru.')]
class AppServer extends Server
{
    /**
     * Tools yang dapat dieksekusi oleh OpenCode AI
     */
    protected array $tools = [
        CheckDatabaseTool::class,
        ArtisanRunnerTool::class,
    ];

    /**
     * Resource / Context data yang bisa dibaca oleh OpenCode AI
     */
    protected array $resources = [
        ProjectOverviewResource::class,
    ];
}
