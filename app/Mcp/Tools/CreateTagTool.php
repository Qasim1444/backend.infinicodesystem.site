<?php

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new blog tag. The slug is derived from the name unless one is given.')]
class CreateTagTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->get('slug') ?: $request->get('name'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tags,slug'],
        ], [
            'slug.required' => 'The name must contain at least one letter or number.',
            'slug.unique' => 'A tag with this slug already exists.',
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        return Response::structured([
            'message' => 'Tag created successfully.',
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Tag name.')->required(),
            'slug' => $schema->string()->description('Optional URL slug. Derived from the name when omitted.'),
        ];
    }
}
