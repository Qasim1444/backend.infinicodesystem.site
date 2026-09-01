<?php

namespace App\Mcp\Tools;

use App\Models\Post;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Update an existing blog post, including its category, tags, publication state, and slug.')]
#[IsIdempotent]
class UpdatePostTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->merge([
            'slug' => $request->has('slug') ? Str::slug((string) $request->get('slug')) : null,
        ]);

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:posts,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore((int) $request->get('id')),
            ],
            'is_published' => ['sometimes', 'boolean'],
        ], [
            'slug.unique' => 'A post with this slug already exists.',
        ]);

        $post = Post::findOrFail($validated['id']);

        $data = [];

        if ($request->has('title')) {
            $data['title'] = $validated['title'];
            $data['slug'] = Str::slug($validated['title']);
        }

        if ($request->has('content')) {
            $data['content'] = $validated['content'];
        }

        if ($request->has('category_id')) {
            $data['category_id'] = $validated['category_id'];
        }

        if ($request->has('is_published')) {
            $data['is_published'] = $validated['is_published'];
        }

        if ($request->has('slug') && $validated['slug'] !== null) {
            $data['slug'] = $validated['slug'];
        }

        if ($data !== []) {
            $post->update($data);
        }

        if ($request->has('tags')) {
            $post->tags()->sync($validated['tags']);
        }

        $post->load(['category', 'tags']);

        return Response::structured([
            'message' => 'Post updated successfully.',
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
            'id' => $schema->integer()->description('ID of the post to update.')->required(),
            'title' => $schema->string()->description('New post title.'),
            'content' => $schema->string()->description('New post content.'),
            'category_id' => $schema->integer()->description('Category ID to attach to the post.'),
            'tags' => $schema->array()->description('List of tag IDs to associate with the post.'),
            'slug' => $schema->string()->description('Optional new slug. Leave empty to keep the current slug.'),
            'is_published' => $schema->boolean()->description('Whether the post should be published.'),
        ];
    }
}
