<?php

namespace App\Mcp\Tools;

use App\Models\Tag;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Rename an existing blog tag. The slug is only changed when a new one is passed explicitly, so existing links keep working.')]
#[IsIdempotent]
class UpdateTagTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        if ($request->get('slug') !== null) {
            $request->merge(['slug' => Str::slug((string) $request->get('slug'))]);
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:tags,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('tags', 'slug')->ignore((int) $request->get('id')),
            ],
        ], [
            'slug.unique' => 'A tag with this slug already exists.',
        ]);

        $tag = Tag::findOrFail($validated['id']);

        $tag->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $tag->slug,
        ]);

        return Response::structured([
            'message' => 'Tag updated successfully.',
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
            'id' => $schema->integer()->description('ID of the tag to update.')->required(),
            'name' => $schema->string()->description('New tag name.')->required(),
            'slug' => $schema->string()->description('Optional new slug. Leave empty to keep the current slug.'),
        ];
    }
}
