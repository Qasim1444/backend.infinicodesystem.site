<?php

namespace App\Mcp\Tools;

use App\Models\Category;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new blog category. The slug is derived from the name unless one is given.')]
class CreateCategoryTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $request->merge([
            'slug' => Str::slug((string) ($request->get('slug') ?: $request->get('name'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
        ], [
            'slug.required' => 'The name must contain at least one letter or number.',
            'slug.unique' => 'A category with this slug already exists.',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        return Response::structured([
            'message' => 'Category created successfully.',
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
            'name' => $schema->string()->description('Category name.')->required(),
            'slug' => $schema->string()->description('Optional URL slug. Derived from the name when omitted.'),
        ];
    }
}
