<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\Page;
use App\Models\User;
use App\Support\ContactPageContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_uses_dedicated_template_and_preserves_url(): void
    {
        Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'body' => 'Legacy body should not drive the Contact template.',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('pages.show', 'contact'));

        $response->assertOk();
        $response->assertSee('data-contact-page', false);
        $response->assertSee('Contact Supplify', false);
        $response->assertSee('Let’s get in touch', false);
        $response->assertSee('images/contact/contact-hero.webp', false);
        $response->assertSee('Send us a message', false);
        $response->assertSee('Contact details', false);
        $response->assertSee('hello@example.com', false);
        $response->assertSee('+92 3XX XXXXXXX', false);
        $response->assertSee('Pakistan', false);
        $response->assertSee('Example contact details. Official details will be updated soon.', false);
        $response->assertSee(route('pages.show', 'privacy'), false);
        $response->assertDontSee('mailto:hello@example.com', false);
        $response->assertDontSee('tel:', false);
        $response->assertDontSee('Legacy body should not drive the Contact template.', false);
        $response->assertDontSee('data-about-page', false);
    }

    public function test_generic_policy_pages_remain_unchanged(): void
    {
        Page::factory()->create([
            'title' => 'Privacy',
            'slug' => 'privacy',
            'body' => 'We do not sell personal information.',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('pages.show', 'privacy'))
            ->assertOk()
            ->assertSee('We do not sell personal information.', false)
            ->assertDontSee('data-contact-page', false)
            ->assertDontSee('data-about-page', false);
    }

    public function test_valid_enquiry_is_stored_without_sending_mail(): void
    {
        Mail::fake();

        Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->post(route('contact.store'), [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.org',
            'phone' => '+92 300 1234567',
            'enquiry_type' => 'order_support',
            'message' => 'I need help locating my latest order status please.',
            'website' => '',
        ]);

        $response->assertRedirect(route('pages.show', 'contact'));
        $response->assertSessionHas('flash_success', 'Thank you. Your message has been received.');
        $response->assertSessionMissing('flash_error');

        $this->assertDatabaseHas('contact_enquiries', [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.org',
            'enquiry_type' => 'order_support',
            'is_read' => false,
        ]);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_validation_errors_preserve_input(): void
    {
        Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->from(route('pages.show', 'contact'))
            ->post(route('contact.store'), [
                'name' => '',
                'email' => 'not-an-email',
                'enquiry_type' => 'other',
                'message' => 'short',
                'website' => '',
            ]);

        $response->assertRedirect(route('pages.show', 'contact'));
        $response->assertSessionHasErrors(['name', 'email', 'message']);
        $response->assertSessionHasInput('enquiry_type', 'other');
        $this->assertDatabaseCount('contact_enquiries', 0);
    }

    public function test_honeypot_rejects_submission(): void
    {
        Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->from(route('pages.show', 'contact'))
            ->post(route('contact.store'), [
                'name' => 'Bot',
                'email' => 'bot@example.org',
                'enquiry_type' => 'other',
                'message' => 'This is a spam message that should be blocked.',
                'website' => 'https://spam.example',
            ]);

        $response->assertRedirect(route('pages.show', 'contact'));
        $this->assertDatabaseCount('contact_enquiries', 0);
    }

    public function test_admin_can_view_and_delete_enquiries(): void
    {
        $admin = User::factory()->admin()->create();
        $enquiry = ContactEnquiry::query()->create([
            'name' => 'Sara Ali',
            'email' => 'sara@example.org',
            'phone' => null,
            'enquiry_type' => 'product_enquiry',
            'message' => 'Do you stock vitamin D3 softgels?',
            'is_read' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-enquiries.index'))
            ->assertOk()
            ->assertSee('Sara Ali', false)
            ->assertSee('Do you stock vitamin D3 softgels?', false);

        $this->actingAs($admin)
            ->get(route('admin.contact-enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee('Do you stock vitamin D3 softgels?', false);

        $enquiry->refresh();
        $this->assertTrue($enquiry->is_read);

        $this->actingAs($admin)
            ->delete(route('admin.contact-enquiries.destroy', $enquiry))
            ->assertRedirect(route('admin.contact-enquiries.index'));

        $this->assertDatabaseMissing('contact_enquiries', ['id' => $enquiry->id]);
    }

    public function test_admin_can_update_contact_sections(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'body' => 'Legacy',
            'is_published' => true,
            'published_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.pages.update', $page), [
                'title' => 'Contact',
                'slug' => 'contact',
                'body' => 'Legacy',
                'is_published' => true,
                'contact' => [
                    'banner' => [
                        'eyebrow' => 'Contact Supplify',
                        'heading' => 'Custom contact heading',
                        'intro' => 'Custom banner intro for contact.',
                    ],
                    'form' => [
                        'heading' => 'Send us a message',
                        'intro' => 'Custom form intro.',
                        'privacy_note' => 'We’ll use your details to respond to your enquiry. Read our Privacy Policy.',
                    ],
                    'details' => [
                        'heading' => 'Contact details',
                        'intro' => 'Prefer to contact us directly? Use the details below.',
                        'email' => 'team@supplify.pk',
                        'phone' => '+92 300 1112233',
                        'location' => 'Karachi, Pakistan',
                        'use_placeholders' => '0',
                        'placeholder_note' => '',
                    ],
                    'mail' => [
                        'notification_recipient' => 'ops@supplify.pk',
                        'notifications_enabled' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.pages.edit', $page));

        $page->refresh();
        $resolved = ContactPageContent::resolve($page);

        $this->assertSame('Custom contact heading', $resolved['banner']['heading']);
        $this->assertSame('Custom form intro.', $resolved['form']['intro']);
        $this->assertSame('team@supplify.pk', $resolved['details']['email']);
        $this->assertFalse($resolved['details']['use_placeholders']);
        $this->assertSame('ops@supplify.pk', $resolved['mail']['notification_recipient']);
        $this->assertFalse($resolved['mail']['notifications_enabled']);

        $this->get(route('pages.show', 'contact'))
            ->assertOk()
            ->assertSee('Custom contact heading', false)
            ->assertSee('mailto:team@supplify.pk', false)
            ->assertDontSee('Example contact details', false);
    }
}
