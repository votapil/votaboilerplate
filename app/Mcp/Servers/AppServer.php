<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * The application's own MCP server, mounted at /mcp/app by routes/ai.php.
 *
 * It ships empty on purpose — an MCP server is only worth its place once it exposes
 * something this application knows and a coding agent cannot get from the filesystem
 * (a report, a lookup against real data, a safe operation). Add tools as that need
 * appears, and delete this class and routes/ai.php if it never does: a server that
 * answers nothing still shows up in every agent's tool list.
 */
#[Name('App')]
#[Version('0.0.1')]
#[Instructions('Tools and resources exposed by this application. Empty until the project adds its own.')]
class AppServer extends Server
{
    protected array $tools = [
        //
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
