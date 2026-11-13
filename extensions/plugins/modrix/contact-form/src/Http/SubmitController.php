<?php

namespace Modrix\ContactForm\Http;

use App\Cms\Cms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modrix\ContactForm\Message;
use Throwable;

class SubmitController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $back = redirect()->to(url()->previous().'#contact');

        // Bots fill in every field; people never see this one.
        if ($request->filled('website')) {
            return $back->with('contact_form.sent', true);
        }

        $data = $request->validateWithBag('contactForm', [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = Message::query()->create([
            ...$data,
            'page_url' => mb_substr(url()->previous(), 0, 500),
            'ip_address' => $request->ip(),
        ]);

        if ($recipient = Cms::setting('modrix/contact-form', 'recipient')) {
            try {
                Mail::raw("From: {$message->name} <{$message->email}>\n\n{$message->message}", function ($mail) use ($recipient, $message) {
                    $mail->to($recipient)->replyTo($message->email, $message->name)->subject('New message from '.$message->name);
                });
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $back->with('contact_form.sent', true);
    }
}
