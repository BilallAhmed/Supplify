<?php

namespace Tests\Feature\Assistant;

use App\Models\User;
use App\Services\StoreSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantSourceReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_review_requires_admin(): void
    {
        $this->get('/admin/assistant/sources')->assertRedirect();
        $this->actingAs(User::factory()->create())->get('/admin/assistant/sources')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/assistant/sources')->assertOk()->assertSee('Knowledge sources');
    }

    public function test_source_review_rejects_unknown_type(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/assistant/sources?type=users')->assertNotFound();
    }

    public function test_history_is_private_and_resettable(): void
    {
        config(['assistant.enabled' => true]);
        app(StoreSettings::class)->putMany(['assistant.enabled' => true]);
        $this->postJson('/assistant/message', ['message' => 'unknown product'])->assertOk();
        $this->getJson('/assistant/history')->assertOk()->assertJsonCount(2, 'messages');
        $this->postJson('/assistant/reset')->assertOk();
        $this->getJson('/assistant/history')->assertJsonCount(0, 'messages');
    }
}
