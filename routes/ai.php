<?php

use App\Mcp\Servers\AppServer;
use Laravel\Mcp\Facades\Mcp;

// Registrasi untuk akses local (OpenCode/CLI via stdio)
Mcp::local('app-server', AppServer::class);

// Registrasi untuk akses via HTTP Web (opsional)
Mcp::web('/mcp/app', AppServer::class);
