<?php

namespace Modrix\Newsletter\Http\Admin;

use App\Cms\Cms;
use App\Support\Markdown;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modrix\Newsletter\Models\Campaign;
use Modrix\Newsletter\Models\Subscriber;
use Throwable;

class CampaignsController
{
    public function index(): Response
    {
        $campaigns = Campaign::query()
            ->latest()
            ->paginate(20)
            ->through(fn (Campaign $c): array => [
                'id' => $c->id,
                'subject' => $c->subject,
                'target_tag' => $c->target_tag ?: 'All active subscribers',
                'status' => $c->status,
                'recipients_count' => $c->recipients_count,
                'failed_count' => $c->failed_count,
                'sent_at' => $c->sent_at?->diffForHumans(),
                'updated' => $c->updated_at?->diffForHumans(),
            ]);

        return Inertia::render('plugin:modrix/newsletter/CampaignsIndex', [
            'campaigns' => $campaigns,
            'activeSubscribers' => Subscriber::query()->subscribed()->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('plugin:modrix/newsletter/CampaignEditor', [
            'campaign' => [
                'id' => null,
                'subject' => '',
                'preview_text' => '',
                'body_markdown' => "## Hello!\n\nWrite your newsletter update here.\n\n- Highlight 1\n- Highlight 2",
                'target_tag' => '',
                'status' => 'draft',
            ],
            'previewHtml' => Markdown::toHtml("## Hello!\n\nWrite your newsletter update here."),
            'activeSubscribers' => Subscriber::query()->subscribed()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $campaign = Campaign::query()->create($this->validated($request));

        return redirect()->route('plugin.modrix.newsletter.campaigns.edit', $campaign)
            ->with('status', 'Campaign draft saved.');
    }

    public function edit(Campaign $campaign): Response
    {
        return Inertia::render('plugin:modrix/newsletter/CampaignEditor', [
            'campaign' => [
                'id' => $campaign->id,
                'subject' => $campaign->subject,
                'preview_text' => $campaign->preview_text ?? '',
                'body_markdown' => $campaign->body_markdown,
                'target_tag' => $campaign->target_tag ?? '',
                'status' => $campaign->status,
                'recipients_count' => $campaign->recipients_count,
                'sent_at' => $campaign->sent_at?->toDayDateTimeString(),
            ],
            'previewHtml' => Markdown::toHtml($campaign->body_markdown),
            'activeSubscribers' => Subscriber::query()->subscribed()
                ->when($campaign->target_tag, fn ($q, $tag) => $q->whereJsonContains('tags', $tag))
                ->count(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_if($campaign->status === 'sent', 422, 'Sent campaigns cannot be edited.');

        $campaign->update($this->validated($request));

        return back()->with('status', 'Campaign saved.');
    }

    public function sendTest(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $request->validate(['test_email' => ['required', 'email', 'max:200']]);
        $html = $this->renderEmailHtml($campaign, '#');

        try {
            $this->deliver($data['test_email'], '[TEST] '.$campaign->subject, $html);
        } catch (Throwable $e) {
            return back()->withErrors(['test_email' => 'Could not send test email: '.$e->getMessage()]);
        }

        return back()->with('status', "Test email sent to {$data['test_email']}.");
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status === 'sent') {
            return back()->with('status', 'This campaign has already been sent.');
        }

        $subscribers = Subscriber::query()
            ->subscribed()
            ->when($campaign->target_tag, fn ($q, $tag) => $q->whereJsonContains('tags', $tag))
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            $unsubUrl = route('plugin.modrix.newsletter.unsubscribe', $subscriber->token);
            $html = $this->renderEmailHtml($campaign, $unsubUrl);

            try {
                $this->deliver($subscriber->email, $campaign->subject, $html);
                $sent++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
            }
        }

        $campaign->update([
            'status' => 'sent',
            'recipients_count' => $sent,
            'failed_count' => $failed,
            'sent_at' => now(),
        ]);

        return back()->with('status', "Campaign sent to {$sent} subscriber(s)".($failed ? " ({$failed} failed)." : '.'));
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('plugin.modrix.newsletter.campaigns.index')
            ->with('status', 'Campaign deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'preview_text' => ['nullable', 'string', 'max:300'],
            'body_markdown' => ['required', 'string', 'max:100000'],
            'target_tag' => ['nullable', 'string', 'max:80'],
        ]);

        $data['target_tag'] = ! empty($data['target_tag']) ? Str::slug($data['target_tag']) : '';

        return $data;
    }

    private function renderEmailHtml(Campaign $campaign, string $unsubscribeUrl): string
    {
        $siteName = e((string) app(Settings::class)->get('site_name', 'Modrix'));
        $body = Markdown::toHtml($campaign->body_markdown);
        $preheader = $campaign->preview_text ? '<div style="display:none;max-height:0;overflow:hidden;">'.e($campaign->preview_text).'</div>' : '';

        return $preheader
            .'<div style="font-family:Inter,system-ui,sans-serif;max-width:600px;margin:0 auto;padding:24px;color:#0d1220;line-height:1.6;">'
            .$body
            .'<hr style="border:0;border-top:1px solid #e4e6ee;margin:28px 0 16px;">'
            .'<p style="font-size:12px;color:#5b6478;">Sent by '.$siteName.' · <a href="'.e($unsubscribeUrl).'" style="color:#5b3df5;">Unsubscribe</a></p>'
            .'</div>';
    }

    private function deliver(string $to, string $subject, string $html): void
    {
        $replyTo = (string) Cms::setting('modrix/newsletter', 'reply_to', '');

        Mail::html($html, function ($mail) use ($to, $subject, $replyTo): void {
            $mail->to($to)->subject($subject);

            if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->replyTo($replyTo);
            }
        });
    }
}
