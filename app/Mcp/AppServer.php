<?php

namespace App\Mcp;

use Laravel\Mcp\Server;

class AppServer extends Server
{
    /**
     * Get the name of the MCP server.
     */
    public function name(): string
    {
        return 'app';
    }

    /**
     * Get the descriptive instructions for the server.
     */
    public function instructions(): string
    {
        return 'Interact with the VotaBoilerplate Laravel backend directly via this MCP server. Use tools to query the database, run code, or view logs as needed.';
    }

    /**
     * The tools provided by this server.
     */
    public function tools(): array
    {
        return [
            // Tool::make('example')->description('Example tool'),
        ];
    }
}
