<?php

use App\Mcp\Servers\BlogServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::get('/mcp/blog', function () {
    return response()->json([
        'message' => 'This MCP endpoint accepts JSON-RPC POST requests only.',
        'usage' => 'Send a POST request to /mcp/blog with the MCP JSON-RPC payload.',
        'allowed_methods' => ['POST'],
    ]);
});

Mcp::web('/mcp/blog', BlogServer::class);
