<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Server\Registrar as McpRegistrar;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensCan([
            McpRegistrar::OAUTH_SCOPE => 'Use the blog MCP server',
        ]);
    }
}
