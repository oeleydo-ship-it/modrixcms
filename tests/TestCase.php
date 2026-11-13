<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Pages are drawn by React from Inertia props, so "what the visitor sees" is checked
         * against the props (every string in them, including server-rendered *_html).
         */
        $pageText = function (TestResponse $response): string {
            $page = $response->viewData('page') ?? json_decode($response->getContent(), true);

            return implode("\n", array_map('strval', Arr::flatten(json_decode(json_encode($page['props'] ?? []), true))));
        };

        TestResponse::macro('assertSeeInPage', function (string $text) use ($pageText) {
            Assert::assertStringContainsString($text, $pageText($this), "The page props don't contain \"{$text}\".");

            return $this;
        });

        TestResponse::macro('assertDontSeeInPage', function (string $text) use ($pageText) {
            Assert::assertStringNotContainsString($text, $pageText($this), "The page props contain \"{$text}\".");

            return $this;
        });

        TestResponse::macro('assertBlockAnchors', function (array $present, array $absent = []) {
            $anchors = array_column($this->viewData('page')['props']['blocks'] ?? [], 'anchor');

            foreach ($present as $anchor) {
                Assert::assertContains($anchor, $anchors, "No block with anchor #{$anchor}.");
            }

            foreach ($absent as $anchor) {
                Assert::assertNotContains($anchor, $anchors, "Unexpected block with anchor #{$anchor}.");
            }

            return $this;
        });
    }
}
