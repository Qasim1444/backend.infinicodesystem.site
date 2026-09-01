<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_routes_are_namespaced_under_api_prefix_only(): void
    {
        $this->getJson('/api/v1/categories')->assertOk();
        $this->getJson('/v1/categories')->assertNotFound();

        $this->getJson('/api/v1/tags')->assertOk();
        $this->getJson('/v1/tags')->assertNotFound();

        $this->getJson('/api/v1/posts')->assertOk();
        $this->getJson('/v1/posts')->assertNotFound();
    }

    public function test_posts_categories_and_tags_can_be_crud_via_api(): void
    {
        $categoryResponse = $this->postJson('/api/v1/categories', [
            'name' => 'Laravel',
        ]);

        $categoryResponse->assertCreated();
        $categoryId = $categoryResponse->json('id');

        $tagResponse = $this->postJson('/api/v1/tags', [
            'name' => 'Backend',
        ]);

        $tagResponse->assertCreated();
        $tagId = $tagResponse->json('id');

        $postResponse = $this->postJson('/api/v1/posts', [
            'title' => 'My first API post',
            'content' => '<p>Hello world</p>',
            'category_id' => $categoryId,
            'tags' => [$tagId],
            'is_published' => true,
        ]);

        $postResponse->assertCreated();
        $postId = $postResponse->json('id');

        $this->getJson('/api/v1/posts')->assertOk()->assertJsonFragment([
            'title' => 'My first API post',
        ]);

        $this->putJson('/api/v1/posts/' . $postId, [
            'title' => 'Updated API post',
            'tags' => [$tagId],
        ])->assertOk()->assertJsonPath('title', 'Updated API post');

        $this->patchJson('/api/v1/categories/' . $categoryId, [
            'name' => 'PHP',
        ])->assertOk()->assertJsonPath('name', 'PHP');

        $this->patchJson('/api/v1/tags/' . $tagId, [
            'name' => 'API',
        ])->assertOk()->assertJsonPath('name', 'API');

        $this->deleteJson('/api/v1/posts/' . $postId)->assertNoContent();
        $this->deleteJson('/api/v1/categories/' . $categoryId)->assertNoContent();
        $this->deleteJson('/api/v1/tags/' . $tagId)->assertNoContent();
    }
}
