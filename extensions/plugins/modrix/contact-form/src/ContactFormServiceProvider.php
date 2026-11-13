<?php

namespace Modrix\ContactForm;

use App\Cms\Cms;
use App\Cms\PluginServiceProvider;
use Inertia\Inertia;

class ContactFormServiceProvider extends PluginServiceProvider
{
    protected function bootPlugin(): void
    {
        // Fields and default content of the block. public/contact-form.js draws it.
        $this->block('form', [
            'label' => 'Contact form',
            'description' => 'Name, email and message. Messages arrive in Admin → Messages.',
            'fields' => [
                ['name' => 'title', 'label' => 'Heading', 'type' => 'text'],
                ['name' => 'text', 'label' => 'Intro text', 'type' => 'textarea'],
                ['name' => 'button_label', 'label' => 'Button text', 'type' => 'text'],
            ],
            'defaults' => [
                'anchor' => 'contact',
                'title' => 'Get in touch',
                'text' => 'Questions, ideas or feedback? Send us a message.',
                'button_label' => 'Send message',
            ],
        ]);

        // What the block needs on the page: where to send, and whether a message was just sent.
        Inertia::share('contactForm', fn () => [
            'sendUrl' => route('plugin.modrix.contact-form.send'),
            'sent' => (bool) session('contact_form.sent'),
            'successMessage' => Cms::setting($this->name(), 'success_message'),
        ]);

        $this->hook('head', fn () => '<link rel="stylesheet" href="'.e(Cms::asset($this->name(), 'contact-form.css')).'">');
    }
}
