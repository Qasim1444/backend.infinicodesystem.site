<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateCategoryTool;
use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\CreateTagTool;
use App\Mcp\Tools\DeleteCategoryTool;
use App\Mcp\Tools\DeletePostTool;
use App\Mcp\Tools\DeleteTagTool;
use App\Mcp\Tools\ListCategoriesTool;
use App\Mcp\Tools\ListPostsTool;
use App\Mcp\Tools\ListTagsTool;
use App\Mcp\Tools\ShowCategoryTool;
use App\Mcp\Tools\ShowPostTool;
use App\Mcp\Tools\ShowTagTool;
use App\Mcp\Tools\UpdateCategoryTool;
use App\Mcp\Tools\UpdatePostTool;
use App\Mcp\Tools\UpdateTagTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Blog Server')]
#[Version('0.0.1')]
#[Instructions('This server exposes blog CRUD capabilities for posts, categories, and tags. Categories and tags support the full cycle: list, show, create, update, and delete. Tools that change or delete a record take the numeric ID returned by the matching list tool, so list first when you only know a name.')]
class BlogServer extends Server
{
    protected array $tools = [
        ListPostsTool::class,
        ShowPostTool::class,
        CreatePostTool::class,
        UpdatePostTool::class,
        DeletePostTool::class,

        ListCategoriesTool::class,
        ShowCategoryTool::class,
        CreateCategoryTool::class,
        UpdateCategoryTool::class,
        DeleteCategoryTool::class,

        ListTagsTool::class,
        ShowTagTool::class,
        CreateTagTool::class,
        UpdateTagTool::class,
        DeleteTagTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
