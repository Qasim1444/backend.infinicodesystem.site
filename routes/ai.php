<?php

use App\Mcp\Servers\BlogServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp/blog', BlogServer::class);
