<?php

namespace Modrix\ContactForm\Http\Admin;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\ContactForm\Message;

class MessagesController
{
    public function index(): Response
    {
        // Drawn by the "Messages" page registered in public/contact-form.js.
        return Inertia::render('plugin:modrix/contact-form/Messages', [
            'messages' => Message::query()->latest()->paginate(20)->through(fn (Message $message) => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'message' => $message->message,
                'received' => $message->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return back()->with('status', 'Message deleted.');
    }
}
