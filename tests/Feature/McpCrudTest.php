<?php

namespace Tests\Feature;

use App\Mcp\Servers\BlogServer;
use App\Mcp\Tools\CreateCategoryTool;
use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\CreateTagTool;
use App\Mcp\Tools\DeleteCategoryTool;
use App\Mcp\Tools\DeletePostTool;
use App\Mcp\Tools\DeleteTagTool;
use App\Mcp\Tools\ListCategoriesTool;
use App\Mcp\Tools\ListPostsTool;
use App\Mcp\Tools\ListTagsTool;
use App\Mcp\Tools\ShowCategoryTool;
use App\Mcp\Tools\ShowPostTool;
use App\Mcp\Tools\ShowTagTool;
use App\Mcp\Tools\UpdateCategoryTool;
use App\Mcp\Tools\UpdatePostTool;
use App\Mcp\Tools\UpdateTagTool;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class McpCrudTest extends TestCase
{
    public function test_mcp_endpoint_requires_oauth_and_excludes_browser_csrf(): void
    {
        $route = $this->app['router']->getRoutes()->match(
            Request::create('/mcp/blog', 'POST')
        );

        $middleware = $route->gatherMiddleware();

        $this->assertContains('auth:api', $route->middleware());
        $this->assertContains('scopes:mcp:use', $route->middleware());
        $this->assertNotContains(ValidateCsrfToken::class, $middleware);
    }

    use RefreshDatabase;

    public function test_blog_server_can_list_posts(): void
    {
        $response = BlogServer::tool(ListPostsTool::class, [
            'limit' => 10,
        ]);

        $response->assertOk();
        $response->assertSee('posts');
    }

    public function test_blog_server_can_create_category_tag_and_post(): void
    {
        $categoryResponse = BlogServer::tool(CreateCategoryTool::class, [
            'name' => 'Laravel',
        ]);

        $categoryResponse->assertOk();
        $categoryResponse->assertSee('Laravel');

        $tagResponse = BlogServer::tool(CreateTagTool::class, [
            'name' => 'MCP',
        ]);

        $tagResponse->assertOk();
        $tagResponse->assertSee('MCP');

        $postResponse = BlogServer::tool(CreatePostTool::class, [
            'title' => 'MCP blog post',
            'content' => '<p>Hello from MCP</p>',
            'category_id' => 1,
            'tags' => [1],
            'is_published' => true,
        ]);

        $postResponse->assertOk();
        $postResponse->assertSee('MCP blog post');
    }

    public function test_posts_can_be_crud_via_mcp(): void
    {
        $category = Category::forceCreate([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        $tag = Tag::forceCreate([
            'name' => 'MCP',
            'slug' => 'mcp',
        ]);

        $post = BlogServer::tool(CreatePostTool::class, [
            'title' => 'MCP Post',
            'content' => '<p>Hello from MCP</p>',
            'category_id' => $category->id,
            'tags' => [$tag->id],
            'is_published' => true,
        ]);

        $post->assertOk()->assertSee('MCP Post');

        $created = Post::firstWhere('slug', 'mcp-post');
        $this->assertNotNull($created);

        BlogServer::tool(ShowPostTool::class, ['id' => $created->id])
            ->assertOk()
            ->assertSee(['MCP Post', 'Laravel', 'mcp']);

        BlogServer::tool(UpdatePostTool::class, [
            'id' => $created->id,
            'title' => 'Updated MCP Post',
            'category_id' => $category->id,
            'tags' => [$tag->id],
            'is_published' => false,
        ])->assertOk()->assertSee('Updated MCP Post');

        $this->assertDatabaseHas('posts', [
            'id' => $created->id,
            'title' => 'Updated MCP Post',
            'slug' => 'updated-mcp-post',
            'is_published' => false,
        ]);

        BlogServer::tool(DeletePostTool::class, ['id' => $created->id])
            ->assertOk()
            ->assertSee('deleted successfully');

        $this->assertDatabaseMissing('posts', ['id' => $created->id]);
    }

    public function test_categories_can_be_crud_via_mcp(): void
    {
        BlogServer::tool(CreateCategoryTool::class, ['name' => 'Laravel'])
            ->assertOk()
            ->assertSee(['Laravel', 'laravel']);

        $category = Category::firstWhere('slug', 'laravel');

        $this->assertNotNull($category);

        BlogServer::tool(ShowCategoryTool::class, ['id' => $category->id])
            ->assertOk()
            ->assertSee(['Laravel', 'posts_count']);

        BlogServer::tool(UpdateCategoryTool::class, [
            'id' => $category->id,
            'name' => 'Laravel 13',
        ])->assertOk()->assertSee('Laravel 13');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Laravel 13',
            'slug' => 'laravel',
        ]);

        BlogServer::tool(ListCategoriesTool::class)
            ->assertOk()
            ->assertSee('Laravel 13');

        BlogServer::tool(DeleteCategoryTool::class, ['id' => $category->id])
            ->assertOk()
            ->assertSee('deleted successfully');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_tags_can_be_crud_via_mcp(): void
    {
        BlogServer::tool(CreateTagTool::class, ['name' => 'Backend'])
            ->assertOk()
            ->assertSee(['Backend', 'backend']);

        $tag = Tag::firstWhere('slug', 'backend');

        $this->assertNotNull($tag);

        BlogServer::tool(ShowTagTool::class, ['id' => $tag->id])
            ->assertOk()
            ->assertSee(['Backend', 'posts_count']);

        BlogServer::tool(UpdateTagTool::class, [
            'id' => $tag->id,
            'name' => 'Backend Engineering',
        ])->assertOk()->assertSee('Backend Engineering');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Backend Engineering',
            'slug' => 'backend',
        ]);

        BlogServer::tool(ListTagsTool::class)
            ->assertOk()
            ->assertSee('Backend Engineering');

        BlogServer::tool(DeleteTagTool::class, ['id' => $tag->id])
            ->assertOk()
            ->assertSee('deleted successfully');

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_slugs_can_be_changed_explicitly_and_stay_unique(): void
    {
        BlogServer::tool(CreateCategoryTool::class, ['name' => 'Laravel'])->assertOk();
        BlogServer::tool(CreateCategoryTool::class, ['name' => 'Testing'])->assertOk();

        BlogServer::tool(CreateCategoryTool::class, ['name' => 'Laravel'])
            ->assertHasErrors(['A category with this slug already exists.']);

        $laravel = Category::firstWhere('slug', 'laravel');

        BlogServer::tool(UpdateCategoryTool::class, [
            'id' => $laravel->id,
            'name' => 'Laravel 13',
            'slug' => 'Laravel 13!',
        ])->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $laravel->id,
            'slug' => 'laravel-13',
        ]);

        BlogServer::tool(UpdateCategoryTool::class, [
            'id' => $laravel->id,
            'name' => 'Laravel 13',
            'slug' => 'testing',
        ])->assertHasErrors(['A category with this slug already exists.']);

        BlogServer::tool(CreateTagTool::class, ['name' => 'MCP'])->assertOk();
        BlogServer::tool(CreateTagTool::class, ['name' => 'mcp'])
            ->assertHasErrors(['A tag with this slug already exists.']);
    }

    public function test_deleting_a_category_or_tag_keeps_the_posts(): void
    {
        BlogServer::tool(CreateCategoryTool::class, ['name' => 'Laravel'])->assertOk();
        BlogServer::tool(CreateTagTool::class, ['name' => 'MCP'])->assertOk();

        $category = Category::firstWhere('slug', 'laravel');
        $tag = Tag::firstWhere('slug', 'mcp');

        BlogServer::tool(CreatePostTool::class, [
            'title' => 'Post with a category and a tag',
            'content' => '<p>Body</p>',
            'category_id' => $category->id,
            'tags' => [$tag->id],
        ])->assertOk();

        $post = Post::firstWhere('slug', 'post-with-a-category-and-a-tag');

        $this->assertSame($category->id, $post->category_id);
        $this->assertCount(1, $post->tags);

        BlogServer::tool(DeleteCategoryTool::class, ['id' => $category->id])
            ->assertOk()
            ->assertSee('"posts_detached":1');

        BlogServer::tool(DeleteTagTool::class, ['id' => $tag->id])
            ->assertOk()
            ->assertSee('"posts_detached":1');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'category_id' => null]);
        $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id, 'tag_id' => $tag->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_write_tools_reject_unknown_ids_and_missing_fields(): void
    {
        BlogServer::tool(ShowCategoryTool::class, ['id' => 999])->assertHasErrors();
        BlogServer::tool(UpdateCategoryTool::class, ['id' => 999, 'name' => 'Nope'])->assertHasErrors();
        BlogServer::tool(DeleteCategoryTool::class, ['id' => 999])->assertHasErrors();

        BlogServer::tool(ShowTagTool::class, ['id' => 999])->assertHasErrors();
        BlogServer::tool(UpdateTagTool::class, ['id' => 999, 'name' => 'Nope'])->assertHasErrors();
        BlogServer::tool(DeleteTagTool::class, ['id' => 999])->assertHasErrors();

        BlogServer::tool(CreateCategoryTool::class, [])->assertHasErrors();
        BlogServer::tool(CreateTagTool::class, [])->assertHasErrors();
        BlogServer::tool(UpdateCategoryTool::class, ['name' => 'No id'])->assertHasErrors();
        BlogServer::tool(UpdateTagTool::class, ['name' => 'No id'])->assertHasErrors();
    }

    public function test_mcp_endpoint_advertises_every_category_and_tag_tool(): void
    {
        $this->withoutMiddleware();

        $response = $this->postJson('/mcp/blog', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ], ['Accept' => 'application/json, text/event-stream']);

        $response->assertOk();

        $tools = collect($response->json('result.tools'))->keyBy('name');

        $this->assertEqualsCanonicalizing([
            'list-posts-tool',
            'show-post-tool',
            'create-post-tool',
            'update-post-tool',
            'delete-post-tool',
            'list-categories-tool',
            'show-category-tool',
            'create-category-tool',
            'update-category-tool',
            'delete-category-tool',
            'list-tags-tool',
            'show-tag-tool',
            'create-tag-tool',
            'update-tag-tool',
            'delete-tag-tool',
        ], $tools->keys()->all());

        $this->assertTrue($tools['list-categories-tool']['annotations']['readOnlyHint']);
        $this->assertTrue($tools['show-tag-tool']['annotations']['readOnlyHint']);
        $this->assertTrue($tools['update-category-tool']['annotations']['idempotentHint']);
        $this->assertTrue($tools['delete-tag-tool']['annotations']['destructiveHint']);

        $this->assertSame(['id'], $tools['delete-category-tool']['inputSchema']['required']);
        $this->assertEqualsCanonicalizing(['id', 'name'], $tools['update-tag-tool']['inputSchema']['required']);
    }
}
