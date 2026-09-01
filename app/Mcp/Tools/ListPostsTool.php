<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List recent published posts for the blog. Optional limit parameter controls how many posts to return.')]
class ListPostsTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $limit = max(1, min(25, (int) $request->get('limit', 10)));

        $posts = Post::with(['category', 'tags'])
            ->where('is_published', true)
            ->latest()
            ->limit($limit)
            ->get();

        return Response::structured([
            'posts' => $posts->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category?->name,
                'tags' => $post->tags->pluck('name')->toArray(),
                'is_published' => (bool) $post->is_published,
                'created_at' => $post->created_at?->toISOString(),
            ])->toArray(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Number of posts to return. Defaults to 10, max 25.')->default(10),
        ];
    }
}
