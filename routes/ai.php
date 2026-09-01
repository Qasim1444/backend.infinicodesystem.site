<?php

use App\Mcp\Servers\BlogServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/blog', BlogServer::class);
