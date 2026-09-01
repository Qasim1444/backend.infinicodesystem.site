<?php

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Rename an existing blog category. The slug is only changed when a new one is passed explicitly, so existing links keep working.')]
#[IsIdempotent]
class UpdateCategoryTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        if ($request->get('slug') !== null) {
            $request->merge(['slug' => Str::slug((string) $request->get('slug'))]);
        }

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore((int) $request->get('id')),
            ],
        ], [
            'slug.unique' => 'A category with this slug already exists.',
        ]);

        $category = Category::findOrFail($validated['id']);

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? $category->slug,
        ]);

        return Response::structured([
            'message' => 'Category updated successfully.',
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID of the category to update.')->required(),
            'name' => $schema->string()->description('New category name.')->required(),
            'slug' => $schema->string()->description('Optional new slug. Leave empty to keep the current slug.'),
        ];
    }
}
