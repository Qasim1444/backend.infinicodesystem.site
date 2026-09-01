<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a blog post with an optional category and tag list.')]
class CreatePostTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'is_published' => ['boolean'],
        ]);

        $post = Post::create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']),
            'content' => $validated['content'],
            'category_id' => $validated['category_id'] ?? null,
            'is_published' => $validated['is_published'] ?? true,
        ]);

        if (! empty($validated['tags'] ?? [])) {
            $post->tags()->sync($validated['tags']);
        }

        $post->load(['category', 'tags']);

        return Response::structured([
            'message' => 'Post created successfully.',
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category_id' => $post->category_id,
                'tags' => $post->tags->pluck('id')->toArray(),
                'is_published' => (bool) $post->is_published,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->description('Post title.')->required(),
            'content' => $schema->string()->description('Post HTML or text content.')->required(),
            'category_id' => $schema->integer()->description('Category ID to attach to the post.'),
            'tags' => $schema->array()->description('List of tag IDs to associate with the post.'),
            'is_published' => $schema->boolean()->description('Whether the post should be published immediately.')->default(true),
        ];
    }
}
