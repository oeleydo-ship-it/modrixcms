<?php

namespace App\Cms\Ai;

use App\Models\Category;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\Settings;
use App\Support\Slugs;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writes blog posts and topic ideas with the AI provider configured in Settings → AI API.
 * The result is a set of post fields (rich text body plus SEO), never saved here.
 */
class BlogGenerator
{
    /** @var array<string, int> target words per length */
    private const LENGTHS = ['short' => 500, 'medium' => 900, 'long' => 1500];

    public function __construct(private AiClient $ai, private Settings $settings) {}

    /**
     * @param  array{topic: string, keywords?: ?string, tone?: ?string, audience?: ?string, length?: ?string, language?: ?string}  $brief
     * @return array<string, mixed>
     */
    public function write(array $brief): array
    {
        $targetWords = self::LENGTHS[$brief['length'] ?? 'medium'] ?? self::LENGTHS['medium'];
        $length = 'about '.$targetWords.' words';
        $categories = Category::query()->orderBy('name')->pluck('name')->all();

        $system = <<<'PROMPT'
You are an experienced content writer and SEO editor. You write a complete, original, useful blog post and return ONLY a JSON object, no commentary.

JSON keys:
- title: a clear, specific headline of at most 60 characters that contains the focus keyword.
- slug: short lowercase url slug with hyphens.
- excerpt: 1-2 sentence summary, at most 200 characters.
- content: the article body as clean HTML. Use only <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <blockquote>, <a>. Do NOT include the title as <h1>. Structure:
  1. An introduction (2 short paragraphs) that states the reader's problem and uses the focus keyword in the first sentence or two.
  2. A "Key takeaways" <h2> with a <ul> of 3-5 one-line points.
  3. 4-7 <h2> sections that each answer one question, with <h3> sub-points, short paragraphs (2-4 sentences), and at least one <ul> or <ol> of concrete steps or examples. Put the focus keyword in at least one <h2>.
  4. A "Frequently asked questions" <h2> with 3 <h3> questions, each followed by a 2-3 sentence answer <p>.
  5. A short conclusion with a clear next step for the reader.
  When related posts from the same site are listed, link to 1-3 of them where they genuinely help, as <a href="URL">natural anchor text</a>; use only those URLs.
- meta_title: at most 60 characters, includes the focus keyword.
- meta_description: 140-158 characters, includes the focus keyword, written to earn the click.
- focus_keyword: the main search phrase (2-4 words).
- og_title: a social share headline.
- og_description: a social share summary of at most 200 characters.
- category: the best matching name from the list given, or "" if none fits.

Rules: factual and practical, no filler, no invented statistics, studies, quotes or outside links. Write like a knowledgeable human: varied sentences, specific examples, second person where natural, and no cliches such as "in today's fast-paced world" or "unlock the power".
PROMPT;

        $user = 'Topic: '.$brief['topic']."\n"
            .'Target length: '.$length."\n"
            .(filled($brief['tone'] ?? null) ? 'Tone: '.$brief['tone']."\n" : '')
            .(filled($brief['audience'] ?? null) ? 'Audience: '.$brief['audience']."\n" : '')
            .(filled($brief['keywords'] ?? null) ? 'Keywords to include naturally: '.$brief['keywords']."\n" : '')
            .(filled($brief['language'] ?? null) ? 'Write in: '.$brief['language']."\n" : '')
            .'Site: '.$this->settings->get('site_name', '')."\n"
            .'Available categories: '.($categories === [] ? '(none)' : implode(', ', $categories))."\n"
            .$this->relatedPosts();

        $raw = $this->ai->completeJson($system, $user, [], 240);
        $post = $this->normalise($raw, $brief['topic']);

        // A model that stops well short of the length asked for gets one chance to expand its draft.
        if ($post['words'] < $targetWords * 0.55) {
            try {
                $longer = $this->ai->completeJson(
                    $system,
                    $user."\n\nYour previous draft was only {$post['words']} words. Rewrite it as a complete article of {$length}, keeping the same title and keyword. Previous draft:\n".$post['content'],
                    [],
                    240,
                );
                $expanded = $this->normalise($longer, $brief['topic']);

                if ($expanded['words'] > $post['words']) {
                    $post = $expanded;
                }
            } catch (RuntimeException) {
                // Keep the shorter draft rather than failing the whole post.
            }
        }

        return $post;
    }

    /**
     * @return list<string>
     */
    public function topics(string $niche, int $count): array
    {
        $count = max(1, min(20, $count));

        $raw = $this->ai->completeJson(
            'You plan blog content calendars. Return ONLY a JSON object {"topics": ["...", ...]} with distinct, specific, search-friendly blog post ideas (each under 90 characters), no numbering.',
            "Site: {$this->settings->get('site_name', '')}\nSubject: {$niche}\nNumber of ideas: {$count}",
        );

        $topics = array_values(array_filter(array_map(
            fn ($topic) => is_string($topic) ? trim($topic) : '',
            (array) ($raw['topics'] ?? []),
        )));

        if ($topics === []) {
            throw new RuntimeException('The AI did not return any topic ideas. Try again.');
        }

        return array_slice($topics, 0, $count);
    }

    /** Recent published posts, so the article can link to them. */
    private function relatedPosts(): string
    {
        $posts = Post::query()->published()->latest('published_at')->take(15)->get(['id', 'title', 'slug']);

        if ($posts->isEmpty()) {
            return '';
        }

        return "Related posts on this site (title | URL):\n".$posts
            ->map(fn (Post $post): string => '- '.$post->title.' | '.route('blog.show', $post))
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalise(array $raw, string $topic): array
    {
        $content = HtmlSanitizer::clean((string) ($raw['content'] ?? ''));

        if (trim(strip_tags($content)) === '') {
            throw new RuntimeException('The AI returned an empty article. Try again or pick a different model in Settings → AI API.');
        }

        $title = Str::limit(trim((string) ($raw['title'] ?? '')) ?: $topic, 200, '');
        $category = trim((string) ($raw['category'] ?? ''));
        $categoryId = $category !== '' ? Category::query()->whereRaw('lower(name) = ?', [mb_strtolower($category)])->value('id') : null;

        return [
            'title' => $title,
            'slug' => Slugs::from((string) ($raw['slug'] ?? '') ?: $title),
            'excerpt' => Str::limit(trim((string) ($raw['excerpt'] ?? '')), 500, ''),
            'content' => $content,
            'meta_title' => Str::limit(trim((string) ($raw['meta_title'] ?? '')) ?: $title, 200, ''),
            'meta_description' => Str::limit(trim((string) ($raw['meta_description'] ?? '')), 300, ''),
            'focus_keyword' => Str::limit(trim((string) ($raw['focus_keyword'] ?? '')), 120, ''),
            'og_title' => Str::limit(trim((string) ($raw['og_title'] ?? '')), 200, ''),
            'og_description' => Str::limit(trim((string) ($raw['og_description'] ?? '')), 300, ''),
            'category_id' => $categoryId,
            'words' => self::wordCount($content),
        ];
    }

    private static function wordCount(string $html): int
    {
        return str_word_count(strip_tags(str_replace('<', ' <', $html)));
    }
}
