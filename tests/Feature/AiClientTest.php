<?php

namespace Tests\Feature;

use App\Cms\Ai\AiClient;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AiClientTest extends TestCase
{
    use RefreshDatabase;

    private function useDeepSeek(string $model = 'deepseek-v4-pro'): void
    {
        app(Settings::class)->set(['ai_provider' => 'deepseek', 'ai_api_key' => 'sk-test', 'ai_model' => $model]);
    }

    /** @param  array<string, mixed>  $message */
    private function reply(array $message, string $finish = 'stop'): array
    {
        return ['choices' => [['message' => $message, 'finish_reason' => $finish]]];
    }

    public function test_a_thinking_model_gets_room_to_answer_and_is_asked_again_when_it_runs_out(): void
    {
        $this->useDeepSeek();
        Http::fake(['api.deepseek.com/*' => Http::sequence()
            ->push($this->reply(['content' => '', 'reasoning_content' => 'Let me think about the plan…'], 'length'))
            ->push($this->reply(['content' => '{"pages":[{"title":"Home"}]}']))]);

        $json = app(AiClient::class)->completeJson('Return JSON.', 'Plan a site.');

        $this->assertSame('Home', $json['pages'][0]['title']);
        Http::assertSentCount(2);

        $requests = Http::recorded();
        $first = $requests[0][0]->data();
        $second = $requests[1][0]->data();
        $this->assertSame(32768, $first['max_tokens'], 'thinking models start with a large budget');
        $this->assertSame(['type' => 'json_object'], $first['response_format']);
        $this->assertArrayNotHasKey('response_format', $second, 'the retry drops JSON mode, which can leave a thinking model silent');
    }

    public function test_a_quick_model_keeps_the_small_budget(): void
    {
        $this->useDeepSeek('deepseek-flash');
        Http::fake(['api.deepseek.com/*' => Http::response($this->reply(['content' => '{"ok":true}']))]);

        app(AiClient::class)->completeJson('Return JSON.', 'Hi');

        Http::assertSent(fn (Request $request) => $request['max_tokens'] === 8192);
    }

    public function test_json_left_in_the_reasoning_text_is_used_as_a_last_resort(): void
    {
        $this->useDeepSeek();
        Http::fake(['api.deepseek.com/*' => Http::response($this->reply(['content' => null, 'reasoning_content' => 'The answer is {"pages":[{"title":"About"}]} done.'], 'length'))]);

        $json = app(AiClient::class)->completeJson('Return JSON.', 'Plan a site.');

        $this->assertSame('About', $json['pages'][0]['title']);
        Http::assertSentCount(3);
    }

    public function test_a_model_that_never_answers_gets_an_explanation_instead_of_a_bare_empty_response(): void
    {
        $this->useDeepSeek();
        Http::fake(['api.deepseek.com/*' => Http::response($this->reply(['content' => ''], 'length'))]);

        try {
            app(AiClient::class)->completeJson('Return JSON.', 'Plan a site.');
            $this->fail('An empty answer must raise an error.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('ran out of output space', $e->getMessage());
            $this->assertStringContainsString('deepseek-flash', $e->getMessage());
        }
    }

    public function test_a_gateway_that_rejects_a_large_token_budget_is_retried_with_a_smaller_one(): void
    {
        $this->useDeepSeek();
        Http::fake(['api.deepseek.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'max_tokens too large']], 400)
            ->push($this->reply(['content' => '{"ok":true}']))]);

        $this->assertSame(['ok' => true], app(AiClient::class)->completeJson('Return JSON.', 'Hi'));
        $this->assertSame(8192, Http::recorded()[1][0]->data()['max_tokens']);
    }
}
