<?php

namespace Modrix\Newsletter\Http;

use App\Cms\Cms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\Newsletter\Models\Subscriber;
use Throwable;

class PublicController
{
    public function subscribe(Request $request): RedirectResponse
    {
        $back = redirect()->to(url()->previous().'#'.($request->input('_anchor') ?: 'newsletter'));

        if ($request->filled('_nl_trap')) {
            return $back->with([
                'newsletter.status' => 'subscribed',
                'newsletter.message' => Cms::setting('modrix/newsletter', 'success_message', 'You are subscribed!'),
            ]);
        }

        $rules = [
            'email' => ['required', 'email', 'max:200'],
            'name' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:80'],
        ];

        if ($request->boolean('require_consent')) {
            $rules['consent'] = ['accepted'];
        }

        $data = $request->validateWithBag('newsletter', $rules);
        $email = strtolower(trim($data['email']));
        $doubleOptIn = (bool) Cms::setting('modrix/newsletter', 'double_opt_in', false);

        $subscriber = Subscriber::query()->where('email', $email)->first();
        $tags = $subscriber?->tags ?? [];

        if (! empty($data['tag'])) {
            $tag = Str::slug($data['tag']);

            if ($tag !== '' && ! in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        $status = $doubleOptIn ? 'pending' : 'subscribed';

        if ($subscriber === null) {
            $subscriber = Subscriber::query()->create([
                'email' => $email,
                'name' => $data['name'] ?? null,
                'status' => $status,
                'source' => mb_substr(url()->previous(), 0, 120),
                'tags' => $tags,
                'token' => Str::random(48),
                'confirmed_at' => $doubleOptIn ? null : now(),
                'ip_address' => $request->ip(),
            ]);
        } else {
            $subscriber->update([
                'name' => $data['name'] ?: $subscriber->name,
                'status' => $subscriber->status === 'subscribed' ? 'subscribed' : $status,
                'tags' => $tags,
                'confirmed_at' => $doubleOptIn ? $subscriber->confirmed_at : ($subscriber->confirmed_at ?? now()),
                'unsubscribed_at' => null,
            ]);
        }

        if ($doubleOptIn && $subscriber->status === 'pending') {
            $confirmUrl = route('plugin.modrix.newsletter.confirm', $subscriber->token);
            $this->tryMail(
                $subscriber->email,
                'Confirm your newsletter subscription',
                'Hi'.($subscriber->name ? " {$subscriber->name}" : '').",\n\nPlease confirm your subscription by opening:\n{$confirmUrl}\n",
            );

            return $back->with([
                'newsletter.status' => 'pending',
                'newsletter.message' => 'Please check your inbox to confirm your subscription.',
            ]);
        }

        if (Cms::setting('modrix/newsletter', 'send_welcome_email', false)) {
            $unsubUrl = route('plugin.modrix.newsletter.unsubscribe', $subscriber->token);
            $subject = (string) Cms::setting('modrix/newsletter', 'welcome_subject', 'Welcome to our newsletter!');
            $this->tryMail(
                $subscriber->email,
                $subject,
                'Hi'.($subscriber->name ? " {$subscriber->name}" : '').",\n\nThanks for subscribing!\n\nUnsubscribe anytime: {$unsubUrl}",
            );
        }

        return $back->with([
            'newsletter.status' => 'subscribed',
            'newsletter.message' => Cms::setting('modrix/newsletter', 'success_message', 'You are subscribed! Thanks for joining.'),
        ]);
    }

    public function confirm(string $token): Response
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();

        if ($subscriber->status !== 'subscribed') {
            $subscriber->update([
                'status' => 'subscribed',
                'confirmed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }

        return Inertia::render('plugin:modrix/newsletter/Status', [
            'heading' => 'Subscription confirmed',
            'message' => "Thanks! {$subscriber->email} is now confirmed and subscribed.",
            'tone' => 'success',
        ]);
    }

    public function unsubscribe(string $token): Response
    {
        $subscriber = Subscriber::query()->where('token', $token)->firstOrFail();

        $subscriber->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        return Inertia::render('plugin:modrix/newsletter/Status', [
            'heading' => 'You are unsubscribed',
            'message' => "{$subscriber->email} has been removed from future newsletter campaigns.",
            'tone' => 'neutral',
        ]);
    }

    private function tryMail(string $to, string $subject, string $text): void
    {
        try {
            Mail::raw($text, function ($mail) use ($to, $subject): void {
                $mail->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            report($e);
        }
    }
}
