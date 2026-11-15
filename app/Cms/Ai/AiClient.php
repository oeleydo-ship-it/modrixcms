<?php

namespace App\Cms\Ai;

use App\Support\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Lightweight multi-provider AI client configured from General Settings.
 */
class AiClient
{
    public const PROVIDERS = [
        'openai' => [
            'label' => 'OpenAI',
            'default_model' => 'gpt-4o-mini',
            'base_url_placeholder' => 'https://api.openai.com/v1',
            'models' => ['gpt-4o-mini', 'gpt-4.1-mini', 'gpt-4.1'],
        ],
        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'default_model' => 'claude-sonnet-4-20250514',
            'base_url_placeholder' => 'https://api.anthropic.com',
            'models' => ['claude-sonnet-4-20250514', 'claude-3-7-sonnet-latest'],
        ],
        'gemini' => [
            'label' => 'Google Gemini',
            'default_model' => 'gemini-2.5-flash',
            'base_url_placeholder' => 'https://generativelanguage.googleapis.com/v1beta',
            'models' => ['gemini-2.5-flash', 'gemini-2.5-pro'],
        ],
        'deepseek' => [
            'label' => 'DeepSeek',
            'default_model' => 'deepseek-flash',
            'base_url_placeholder' => 'https://api.deepseek.com',
            'models' => ['deepseek-flash', 'deepseek-v4-pro'],
        ],
        'openai_compatible' => [
            'label' => 'OpenAI-compatible (OpenRouter, Groq, Ollama…)',
            'default_model' => 'gpt-4o-mini',
            'base_url_placeholder' => 'https://openrouter.ai/api/v1',
            'models' => [],
        ],
    ];

    public function __construct(private Settings $settings) {}

    public function isConfigured(): bool
    {
        $config = $this->config();

        if ($config['provider'] === 'openai_compatible') {
            return filled($config['base_url']) && (filled($config['api_key']) || str_contains((string) $config['base_url'], 'localhost') || str_contains((string) $config['base_url'], '127.0.0.1'));
        }

        return filled($config['api_key']);
    }

    /**
     * @param  array{provider?: ?string, api_key?: ?string, model?: ?string, base_url?: ?string}  $overrides
     * @return array{provider: string, api_key: string, model: string, base_url: string}
     */
    public function config(array $overrides = []): array
    {
        $provider = (string) ($overrides['provider'] ?? $this->settings->get('ai_provider', 'openai'));

        if (! array_key_exists($provider, self::PROVIDERS)) {
            $provider = 'openai';
        }

        $defaultModel = self::PROVIDERS[$provider]['default_model'];
        $rawModel = trim((string) ($overrides['model'] ?? $this->settings->get('ai_model', $defaultModel)));
        $rawKey = trim((string) ($overrides['api_key'] ?? $this->settings->get('ai_api_key', '')));
        $rawBaseUrl = trim((string) ($overrides['base_url'] ?? $this->settings->get('ai_base_url', '')));

        return [
            'provider' => $provider,
            'api_key' => $rawKey,
            'model' => $rawModel !== '' ? $rawModel : $defaultModel,
            'base_url' => rtrim($rawBaseUrl, '/'),
        ];
    }

    /**
     * Sends a prompt expecting a JSON object response and returns the decoded array.
     *
     * @param  array{provider?: ?string, api_key?: ?string, model?: ?string, base_url?: ?string}  $overrides
     * @return array<string, mixed>
     */
    public function completeJson(string $systemPrompt, string $userPrompt, array $overrides = [], int $timeoutSeconds = 150): array
    {
        $config = $this->config($overrides);

        if ($config['api_key'] === '' && $config['provider'] !== 'openai_compatible') {
            throw new RuntimeException('AI API key is not configured. Add it in Settings → AI API.');
        }

        if ($config['provider'] === 'openai_compatible' && $config['base_url'] === '') {
            throw new RuntimeException('An API Base URL is required for OpenAI-compatible providers in Settings → AI API.');
        }

        $text = match ($config['provider']) {
            'anthropic' => $this->callAnthropic($config, $systemPrompt, $userPrompt, $timeoutSeconds),
            'gemini' => $this->callGemini($config, $systemPrompt, $userPrompt, $timeoutSeconds),
            default => $this->callOpenAi($config, $systemPrompt, $userPrompt, $timeoutSeconds),
        };

        return $this->decodeJson($text);
    }

    /**
     * Sends a lightweight verification prompt to confirm the API key and model respond.
     *
     * @param  array{provider?: ?string, api_key?: ?string, model?: ?string, base_url?: ?string}  $overrides
     * @return array{provider: string, model: string, reply: array<string, mixed>}
     */
    public function testConnection(array $overrides = []): array
    {
        $config = $this->config($overrides);

        if ($config['api_key'] === '' && $config['provider'] !== 'openai_compatible') {
            throw new RuntimeException('Enter an API key first to test the connection.');
        }

        if ($config['provider'] === 'openai_compatible' && $config['base_url'] === '') {
            throw new RuntimeException('Enter a Base URL for your OpenAI-compatible gateway.');
        }

        $text = match ($config['provider']) {
            'anthropic' => $this->callAnthropic($config, 'Reply concisely.', 'Say OK', 30),
            'gemini' => $this->callGemini($config, 'Return JSON.', 'Reply with {"ok":true}', 30),
            default => $this->callOpenAi($config, 'Reply concisely.', 'Say OK', 30, expectJson: false),
        };

        return [
            'provider' => self::PROVIDERS[$config['provider']]['label'],
            'model' => $config['model'],
            'reply' => ['text' => trim($text)],
        ];
    }

    /**
     * @param  array{provider: string, api_key: string, model: string, base_url: string}  $config
     */
    private function callOpenAi(
        array $config,
        string $systemPrompt,
        string $userPrompt,
        int $timeoutSeconds,
        bool $expectJson = true,
    ): string {
        $defaultBase = self::PROVIDERS[$config['provider']]['base_url_placeholder'] ?? 'https://api.openai.com/v1';
        $base = $config['base_url'] !== '' ? $config['base_url'] : $defaultBase;

        if (str_ends_with($base, '/chat/completions')) {
            $endpoint = $base;
        } elseif ($config['provider'] === 'openai' && ! str_ends_with($base, '/v1')) {
            $endpoint = $base.'/v1/chat/completions';
        } else {
            $endpoint = $base.'/chat/completions';
        }

        $headers = ['Accept' => 'application/json'];

        if ($config['api_key'] !== '') {
            $headers['Authorization'] = 'Bearer '.$config['api_key'];
        }

        $isReasoning = (bool) preg_match('/^(o1|o3|o4|gpt-5)/i', $config['model']);
        // "Thinking" models (DeepSeek v4 / reasoner, R1…) spend the token budget on reasoning first. With a
        // small cap they can finish with the reasoning only and an empty answer, so they get a larger one.
        $thinks = $config['provider'] === 'deepseek' && (bool) preg_match('/(pro|reason|r1|v4|think)/i', $config['model']);

        $payload = [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];

        if (! $isReasoning) {
            $payload['temperature'] = 0.4;
        }

        if ($expectJson) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        if ($config['provider'] === 'deepseek') {
            $payload['max_tokens'] = $thinks ? 32768 : 8192;
        }

        $providerLabel = self::PROVIDERS[$config['provider']]['label'] ?? 'OpenAI';
        $lastReasoning = '';
        $finish = null;

        // Up to three tries: as built; then without JSON mode and with more room; then once more with
        // the most room the provider allows. Each failure is explained rather than just "empty".
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $response = Http::timeout($timeoutSeconds)->withHeaders($headers)->post($endpoint, $payload);

            // Some models or gateways reject response_format, temperature or a large max_tokens: retry leaner.
            if ($response->status() === 400) {
                $lean = $payload;
                unset($lean['response_format'], $lean['temperature']);
                if (isset($lean['max_tokens'])) {
                    $lean['max_tokens'] = min((int) $lean['max_tokens'], 8192);
                }
                if ($lean !== $payload) {
                    $payload = $lean;
                    $response = Http::timeout($timeoutSeconds)->withHeaders($headers)->post($endpoint, $payload);
                }
            }

            $this->assertOk($response, $providerLabel);

            $content = $response->json('choices.0.message.content');
            $finish = $response->json('choices.0.finish_reason');
            $reasoning = $response->json('choices.0.message.reasoning_content') ?? $response->json('choices.0.message.reasoning');
            $lastReasoning = is_string($reasoning) ? $reasoning : $lastReasoning;

            if (is_string($content) && trim($content) !== '') {
                return $content;
            }

            if ($attempt === 3) {
                break;
            }

            // Ran out of tokens, or the model answered only inside its reasoning: give it more room and no JSON mode.
            unset($payload['response_format']);
            if ($config['provider'] === 'deepseek') {
                $payload['max_tokens'] = $attempt === 1 ? 32768 : 65536;
            } elseif (isset($payload['max_tokens'])) {
                $payload['max_tokens'] = min(((int) $payload['max_tokens']) * 2, 65536);
            }
        }

        // Last resort: some thinking models leave the JSON in their reasoning text.
        if ($expectJson && $lastReasoning !== '' && str_contains($lastReasoning, '{')) {
            $start = strpos($lastReasoning, '{');
            $end = strrpos($lastReasoning, '}');
            if ($end !== false && $end > $start && is_array(json_decode(substr($lastReasoning, $start, $end - $start + 1), true))) {
                return substr($lastReasoning, $start, $end - $start + 1);
            }
        }

        throw new RuntimeException(
            'The AI model returned an empty response'.($finish === 'length' ? ' (it ran out of output space)' : ($finish ? " (finish reason: {$finish})" : '')).'. '
            .($thinks ? 'Thinking models can be slow and long-winded: try a faster model (for example deepseek-flash) in Settings → AI API.' : 'Try again, or pick a different model in Settings → AI API.')
        );
    }

    /**
     * @param  array{provider: string, api_key: string, model: string, base_url: string}  $config
     */
    private function callAnthropic(array $config, string $systemPrompt, string $userPrompt, int $timeoutSeconds): string
    {
        $base = $config['base_url'] !== '' ? $config['base_url'] : 'https://api.anthropic.com';
        $endpoint = str_ends_with($base, '/messages') ? $base : $base.'/v1/messages';

        $response = Http::timeout($timeoutSeconds)->withHeaders([
            'x-api-key' => $config['api_key'],
            'anthropic-version' => '2023-06-01',
            'Accept' => 'application/json',
        ])->post($endpoint, [
            'model' => $config['model'],
            'max_tokens' => 8192,
            'temperature' => 0.4,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $this->assertOk($response, 'Anthropic');

        $blocks = $response->json('content');
        $text = '';

        if (is_array($blocks)) {
            foreach ($blocks as $block) {
                if (($block['type'] ?? null) === 'text' && is_string($block['text'] ?? null)) {
                    $text .= $block['text'];
                }
            }
        }

        if (trim($text) === '') {
            throw new RuntimeException('Anthropic returned an empty response.');
        }

        return $text;
    }

    /**
     * @param  array{provider: string, api_key: string, model: string, base_url: string}  $config
     */
    private function callGemini(array $config, string $systemPrompt, string $userPrompt, int $timeoutSeconds): string
    {
        $base = $config['base_url'] !== '' ? $config['base_url'] : 'https://generativelanguage.googleapis.com/v1beta';
        $endpoint = str_contains($base, ':generateContent')
            ? $base
            : "{$base}/models/".rawurlencode($config['model']).':generateContent';

        $response = Http::timeout($timeoutSeconds)
            ->withHeaders(['x-goog-api-key' => $config['api_key'], 'Accept' => 'application/json'])
            ->post($endpoint, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $userPrompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        $this->assertOk($response, 'Gemini');

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return $text;
    }

    private function assertOk(Response $response, string $providerName): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message')
            ?? $response->json('message')
            ?? $response->body();

        $trimmed = mb_substr(trim((string) $message), 0, 240);

        throw new RuntimeException("{$providerName} API error ({$response->status()}): ".($trimmed !== '' ? $trimmed : 'Request failed.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $raw): array
    {
        $cleaned = trim($raw);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $cleaned, $matches) === 1) {
            $cleaned = trim($matches[1]);
        }

        $decoded = json_decode($cleaned, true);

        if (! is_array($decoded)) {
            $start = strpos($cleaned, '{');
            $end = strrpos($cleaned, '}');

            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($cleaned, $start, $end - $start + 1), true);
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('AI response was not valid JSON. Try generating again or switching models.');
        }

        return $decoded;
    }
}
