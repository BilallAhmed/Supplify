<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('pages', 'slug')->ignore($pageId)],
            'body' => ['nullable', 'string'],
            'sections_json' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            // About structured fields (optional; merged into sections when present)
            'about' => ['nullable', 'array'],
            'about.hero' => ['nullable', 'array'],
            'about.purpose' => ['nullable', 'array'],
            'about.principles' => ['nullable', 'array'],
            'about.journey' => ['nullable', 'array'],
            'about.essentials' => ['nullable', 'array'],
            'about.everyday' => ['nullable', 'array'],
            'about.cta' => ['nullable', 'array'],
            'about.team' => ['nullable', 'array'],
            // Contact structured fields
            'contact' => ['nullable', 'array'],
            'contact.banner' => ['nullable', 'array'],
            'contact.form' => ['nullable', 'array'],
            'contact.details' => ['nullable', 'array'],
            'contact.mail' => ['nullable', 'array'],
            'contact.details.email' => ['nullable', 'string', 'max:190'],
            'contact.details.phone' => ['nullable', 'string', 'max:40'],
            'contact.details.location' => ['nullable', 'string', 'max:120'],
            'contact.details.use_placeholders' => ['sometimes', 'boolean'],
            'contact.mail.notification_recipient' => ['nullable', 'email:filter', 'max:190'],
            'contact.mail.notifications_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'is_published' => $this->boolean('is_published'),
        ];

        if ($this->has('contact')) {
            $contact = $this->input('contact', []);
            if (is_array($contact)) {
                if (isset($contact['details']) && is_array($contact['details'])) {
                    $contact['details']['use_placeholders'] = $this->boolean('contact.details.use_placeholders');
                }
                if (isset($contact['mail']) && is_array($contact['mail'])) {
                    $contact['mail']['notifications_enabled'] = $this->boolean('contact.mail.notifications_enabled');
                    $recipient = trim((string) ($contact['mail']['notification_recipient'] ?? ''));
                    $contact['mail']['notification_recipient'] = $recipient !== '' ? $recipient : null;
                }
                $merge['contact'] = $contact;
            }
        }

        $this->merge($merge);
    }
}
