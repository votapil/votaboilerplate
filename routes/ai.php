<?php

use App\Mcp\Servers\AppServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP routes
|--------------------------------------------------------------------------
|
| laravel/mcp loads this file itself (McpServiceProvider::registerRoutes),
| so it must NOT be listed in bootstrap/app.php — registering the server in
| routes/web.php instead is what produced the duplicate App\Mcp\AppServer
| this template used to ship. One server, registered here, is the whole
| contract: expose the app's own tools and resources to a coding agent.
|
*/

Mcp::web('/mcp/app', AppServer::class);
