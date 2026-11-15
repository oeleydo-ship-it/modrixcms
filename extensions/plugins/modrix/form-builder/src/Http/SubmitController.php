<?php

namespace Modrix\FormBuilder\Http;

use App\Cms\Cms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modrix\FormBuilder\FormSchema;
use Modrix\FormBuilder\Models\Form;
use Modrix\FormBuilder\Models\Submission;
use Throwable;

class SubmitController
{
    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $form = Form::query()->active()->where('slug', $slug)->firstOrFail();
        $settings = $form->settings ?? [];
        $backUrl = url()->previous().'#'.($request->input('_anchor') ?: 'form');

        if (! empty($settings['honeypot']) && $request->filled('_fb_trap')) {
            return redirect()->to($backUrl)->with("fb_submitted.{$form->slug}", true);
        }

        $fields = $form->fields ?? [];
        $plan = FormSchema::validationPlan($fields, $request->all());
        $bag = 'formBuilder_'.$form->slug;

        $validator = Validator::make($request->all(), $plan['rules'], [], $plan['attributes']);

        if ($validator->fails()) {
            return redirect()->to($backUrl)->withErrors($validator, $bag)->withInput();
        }

        $validated = $validator->validated();
        $payload = [];

        foreach ($plan['visibleKeys'] as $key) {
            if (array_key_exists($key, $validated)) {
                $payload[$key] = $validated[$key];
            }
        }

        $status = $this->isSpam($payload) ? 'spam' : 'new';

        if ($settings['store_submissions'] ?? true) {
            $storeIp = (bool) Cms::setting('modrix/form-builder', 'store_ip', true);

            Submission::query()->create([
                'form_id' => $form->id,
                'data' => $payload,
                'status' => $status,
                'page_url' => mb_substr(url()->previous(), 0, 500),
                'ip_address' => $storeIp ? $request->ip() : null,
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 300),
            ]);

            $form->increment('submissions_count');
        }

        $notify = $settings['notify_email'] ?: Cms::setting('modrix/form-builder', 'default_notify_email');

        if ($status !== 'spam' && is_string($notify) && filter_var($notify, FILTER_VALIDATE_EMAIL)) {
            $this->sendNotification($notify, $form, $fields, $payload);
        }

        $redirectUrl = $settings['redirect_url'] ?? '';

        if (is_string($redirectUrl) && $redirectUrl !== '' && preg_match('~^(https?://|/(?!/))~i', $redirectUrl)) {
            return redirect()->to($redirectUrl);
        }

        return redirect()->to($backUrl)->with("fb_submitted.{$form->slug}", true);
    }

    /** @param array<string, mixed> $payload */
    private function isSpam(array $payload): bool
    {
        $raw = (string) Cms::setting('modrix/form-builder', 'spam_keywords', '');

        if (trim($raw) === '') {
            return false;
        }

        $keywords = array_filter(array_map('trim', explode(',', $raw)));

        if ($keywords === []) {
            return false;
        }

        $haystack = mb_strtolower(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '');

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $payload
     */
    private function sendNotification(string $recipient, Form $form, array $fields, array $payload): void
    {
        $lines = ["New submission for form: {$form->name}", str_repeat('-', 40)];

        foreach ($fields as $field) {
            if (! array_key_exists($field['key'], $payload)) {
                continue;
            }

            $value = $payload[$field['key']];
            $formatted = is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'Yes' : 'No') : (string) $value);
            $lines[] = "{$field['label']}: {$formatted}";
        }

        $replyTo = null;

        foreach ($fields as $field) {
            if ($field['type'] === 'email' && ! empty($payload[$field['key']]) && filter_var($payload[$field['key']], FILTER_VALIDATE_EMAIL)) {
                $replyTo = $payload[$field['key']];
                break;
            }
        }

        try {
            Mail::raw(implode("\n", $lines), function ($mail) use ($recipient, $form, $replyTo): void {
                $mail->to($recipient)->subject("New submission: {$form->name}");

                if ($replyTo !== null) {
                    $mail->replyTo($replyTo);
                }
            });
        } catch (Throwable $e) {
            report($e);
        }
    }
}
