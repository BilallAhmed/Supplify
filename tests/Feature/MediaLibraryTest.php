<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\User;
use App\Services\MediaLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_index_existing_public_images_without_duplicating_files(): void
    {
        $admin = User::factory()->admin()->create();
        $hero = public_path('images/contact/contact-hero.webp');
        $this->assertFileExists($hero);

        $this->actingAs($admin)
            ->postJson(route('admin.media.reindex'))
            ->assertOk()
            ->assertJsonPath('result.created', fn ($v) => $v >= 1);

        $asset = MediaAsset::query()->where('path', 'images/contact/contact-hero.webp')->first();
        $this->assertNotNull($asset);
        $this->assertTrue($asset->is_locked);
        $this->assertFileExists($hero);

        $before = filesize($hero);
        app(MediaLibraryService::class)->indexExistingAssets();
        $this->assertSame(1, MediaAsset::query()->where('path', 'images/contact/contact-hero.webp')->count());
        $this->assertSame($before, filesize($hero));
    }

    public function test_admin_can_list_upload_and_select_media(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        MediaAsset::query()->create([
            'disk' => 'public_path',
            'path' => 'images/contact/contact-hero.webp',
            'original_filename' => 'contact-hero.webp',
            'title' => 'contact-hero',
            'mime_type' => 'image/webp',
            'extension' => 'webp',
            'width' => 1905,
            'height' => 440,
            'size_bytes' => 1000,
            'source' => 'imported',
            'is_locked' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.media.index', ['json' => 1, 'q' => 'contact-hero']))
            ->assertOk()
            ->assertJsonPath('data.0.path', 'images/contact/contact-hero.webp');

        $file = UploadedFile::fake()->image('picker-upload.jpg', 400, 300);

        $upload = $this->actingAs($admin)
            ->postJson(route('admin.media.store'), ['file' => $file]);

        $upload->assertCreated();
        $path = $upload->json('data.path');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_removing_page_image_association_keeps_media_file(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'is_published' => true,
            'published_at' => now(),
            'sections' => [
                'banner' => [
                    'image' => 'images/contact/contact-hero.webp',
                    'heading' => 'Let’s get in touch',
                ],
            ],
        ]);

        MediaAsset::query()->create([
            'disk' => 'public_path',
            'path' => 'images/contact/contact-hero.webp',
            'original_filename' => 'contact-hero.webp',
            'mime_type' => 'image/webp',
            'extension' => 'webp',
            'size_bytes' => 1000,
            'source' => 'imported',
            'is_locked' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'Contact',
                'slug' => 'contact',
                'is_published' => true,
                'contact' => [
                    'banner' => [
                        'eyebrow' => 'Contact Supplify',
                        'heading' => 'Let’s get in touch',
                        'intro' => 'Intro',
                        'image' => '',
                        'image_alt' => '',
                    ],
                    'form' => ['heading' => 'Send us a message', 'intro' => 'Intro'],
                    'details' => [
                        'heading' => 'Contact details',
                        'intro' => 'Intro',
                        'email' => 'hello@example.com',
                        'phone' => '+92 3XX XXXXXXX',
                        'location' => 'Pakistan',
                        'use_placeholders' => '1',
                    ],
                    'mail' => ['notification_recipient' => '', 'notifications_enabled' => '0'],
                ],
            ])
            ->assertRedirect();

        $this->assertFileExists(public_path('images/contact/contact-hero.webp'));
        $this->assertDatabaseHas('media_assets', ['path' => 'images/contact/contact-hero.webp']);
    }

    public function test_guests_cannot_access_media_api(): void
    {
        $this->getJson(route('admin.media.index', ['json' => 1]))->assertUnauthorized();
        $this->postJson(route('admin.media.store'), [])->assertUnauthorized();
    }
}
