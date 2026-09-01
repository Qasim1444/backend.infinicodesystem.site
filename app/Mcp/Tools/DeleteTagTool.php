<?php

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Delete a blog tag. The tag is removed from every post it was attached to, but the posts themselves are kept.')]
#[IsDestructive]
class DeleteTagTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:tags,id'],
        ]);

        $tag = Tag::findOrFail($validated['id']);

        $detached = $tag->posts()->detach();

        $tag->delete();

        return Response::structured([
            'message' => 'Tag deleted successfully.',
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
            'posts_detached' => $detached,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID of the tag to delete.')->required(),
        ];
    }
}
