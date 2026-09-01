<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a blog post and remove its tag associations.')] 
#[IsDestructive]
class DeletePostTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:posts,id'],
        ]);

        $post = Post::findOrFail($validated['id']);
        $post->delete();

        return Response::structured([
            'message' => 'Post deleted successfully.',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID of the post to delete.')->required(),
        ];
    }
}
