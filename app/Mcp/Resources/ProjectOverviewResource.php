<?php

namespace App\Mcp\Resources;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('project-overview')]
#[Uri('varlet://context/overview')]
#[Description('Memberikan ringkasan struktur database dan route aplikasi Varlet')]
class ProjectOverviewResource extends Resource
{
    public function handle(Request $request): Response
    {
        // 1. Ambil daftar tabel di DB
        $tables = Schema::getTableListing();

        // 2. Ambil ringkasan route utama
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return $route->method() . ' ' . $route->uri();
        })->take(30)->toArray();

        $info = [
            'app_name' => config('app.name'),
            'laravel_version' => app()->version(),
            'database_tables' => $tables,
            'sample_routes' => $routes,
        ];

        return Response::text(json_encode($info, JSON_PRETTY_PRINT));
    }
}
