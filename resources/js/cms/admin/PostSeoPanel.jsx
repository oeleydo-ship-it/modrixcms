import { useMemo } from 'react';
import { Field } from '@/cms/admin/forms';

const TITLE_RANGE = [30, 60];
const DESCRIPTION_RANGE = [120, 160];

const words = (text) => (text.toLowerCase().match(/[\p{L}\p{N}'’-]+/gu) ?? []);

/** Reads the post body the way a search engine would: plain text, headings, links and images. */
function readContent(html) {
    const doc = new DOMParser().parseFromString(html || '', 'text/html');
    const text = doc.body.textContent ?? '';
    const firstParagraph = doc.body.querySelector('p')?.textContent ?? '';
    const headings = [...doc.body.querySelectorAll('h2, h3, h4')].map((node) => node.textContent ?? '');
    const images = [...doc.body.querySelectorAll('img')];

    return {
        text,
        wordCount: words(text).length,
        firstWords: words(text).slice(0, 120).join(' '),
        firstParagraph,
        headings,
        images: images.length,
        imagesWithoutAlt: images.filter((image) => !(image.getAttribute('alt') ?? '').trim()).length,
        links: doc.body.querySelectorAll('a[href]').length,
    };
}

export function analyse({ title, slug, metaTitle, metaDescription, excerpt, keyword, content }) {
    const body = readContent(content);
    const key = keyword.trim().toLowerCase();
    const seoTitle = (metaTitle || title || '').trim();
    const description = (metaDescription || excerpt || '').trim();
    const has = (haystack) => key !== '' && haystack.toLowerCase().includes(key);
    const occurrences = key === '' ? 0 : body.text.toLowerCase().split(key).length - 1;
    const keyWords = Math.max(1, words(key).length);
    const density = body.wordCount > 0 ? (occurrences * keyWords * 100) / body.wordCount : 0;
    const checks = [];
    const add = (id, status, label, hint) => checks.push({ id, status, label, hint });

    if (key === '') {
        add('keyword', 'bad', 'Set a focus keyword', 'The phrase you want this post to be found for.');
    } else {
        add('keyword', 'good', 'Focus keyword is set');
        add('title-key', has(seoTitle) ? 'good' : 'bad', has(seoTitle) ? 'Keyword is in the SEO title' : 'Add the keyword to the SEO title');
        add('desc-key', has(description) ? 'good' : 'warn', has(description) ? 'Keyword is in the meta description' : 'Add the keyword to the meta description');
        add('slug-key', slug.toLowerCase().includes(key.replace(/\s+/g, '-')) ? 'good' : 'warn', slug.toLowerCase().includes(key.replace(/\s+/g, '-')) ? 'Keyword is in the URL' : 'Use the keyword in the URL');
        add('intro-key', has(body.firstWords) ? 'good' : 'warn', has(body.firstWords) ? 'Keyword is in the introduction' : 'Mention the keyword in the first paragraph');
        add('heading-key', body.headings.some(has) ? 'good' : 'warn', body.headings.some(has) ? 'Keyword is in a subheading' : 'Use the keyword in at least one subheading');
        add('density', density >= 0.4 && density <= 3 ? 'good' : 'warn', `Keyword used ${occurrences}× (${density.toFixed(1)}%)`, density < 0.4 ? 'Use it a little more, naturally.' : density > 3 ? 'Too often: it reads as keyword stuffing.' : undefined);
    }

    const titleLength = seoTitle.length;
    add('title-length', titleLength >= TITLE_RANGE[0] && titleLength <= TITLE_RANGE[1] ? 'good' : 'warn', `SEO title is ${titleLength} characters`, `Aim for ${TITLE_RANGE[0]}–${TITLE_RANGE[1]}.`);
    const descriptionLength = description.length;
    add('desc-length', descriptionLength >= DESCRIPTION_RANGE[0] && descriptionLength <= DESCRIPTION_RANGE[1] ? 'good' : descriptionLength === 0 ? 'bad' : 'warn', `Meta description is ${descriptionLength} characters`, `Aim for ${DESCRIPTION_RANGE[0]}–${DESCRIPTION_RANGE[1]}.`);
    add('length', body.wordCount >= 600 ? 'good' : body.wordCount >= 300 ? 'warn' : 'bad', `${body.wordCount} words`, body.wordCount >= 600 ? undefined : 'Longer, useful posts (600+ words) tend to rank better.');
    add('headings', body.headings.length >= 2 ? 'good' : 'warn', body.headings.length >= 2 ? 'Content has subheadings' : 'Break the text up with subheadings');
    add('links', body.links > 0 ? 'good' : 'warn', body.links > 0 ? `${body.links} link${body.links === 1 ? '' : 's'} in the text` : 'Add a link to a related page or source');
    if (body.images > 0) {
        add('alt', body.imagesWithoutAlt === 0 ? 'good' : 'warn', body.imagesWithoutAlt === 0 ? 'All images have alt text' : `${body.imagesWithoutAlt} image${body.imagesWithoutAlt === 1 ? '' : 's'} without alt text`);
    }

    const points = { good: 1, warn: 0.5, bad: 0 };
    const score = Math.round((checks.reduce((sum, check) => sum + points[check.status], 0) / checks.length) * 100);

    return { checks, score, wordCount: body.wordCount };
}

const Counter = ({ value, range }) => {
    const length = (value ?? '').length;
    const tone = length === 0 ? 'idle' : length >= range[0] && length <= range[1] ? 'good' : 'warn';

    return <span className={`seo-count ${tone}`}>{length} / {range[1]}</span>;
};

export default function PostSeoPanel({ form, post, blogUrl, siteName }) {
    const data = form.data;
    const result = useMemo(
        () => analyse({ title: data.title, slug: data.slug, metaTitle: data.meta_title, metaDescription: data.meta_description, excerpt: data.excerpt, keyword: data.focus_keyword, content: data.content }),
        [data.title, data.slug, data.meta_title, data.meta_description, data.excerpt, data.focus_keyword, data.content],
    );
    const tone = result.score >= 75 ? 'good' : result.score >= 45 ? 'warn' : 'bad';
    const previewTitle = (data.meta_title || data.title || 'Post title').trim();
    const previewDescription = (data.meta_description || data.excerpt || 'The meta description appears here. Write 120–160 characters that make people want to click.').trim();
    const previewUrl = data.canonical_url || `${blogUrl}/${data.slug || 'post-url'}`;

    return (
        <div className="panel seo-panel">
            <div className="seo-head">
                <h2>SEO</h2>
                <span className={`seo-score ${tone}`} title="How well this post is optimised">{result.score}/100</span>
            </div>

            <div className="seo-preview" aria-label="Search result preview">
                <div className="seo-preview-site">{siteName} <span>› {previewUrl.replace(/^https?:\/\//, '')}</span></div>
                <div className="seo-preview-title">{previewTitle.length > 62 ? `${previewTitle.slice(0, 60)}…` : previewTitle}</div>
                <div className="seo-preview-desc">{previewDescription.length > 165 ? `${previewDescription.slice(0, 162)}…` : previewDescription}</div>
            </div>

            <Field form={form} name="focus_keyword" label="Focus keyword" maxLength={120} placeholder="e.g. ai workflow automation" help="The phrase you want this post to rank for." />
            <Field form={form} name="meta_title" label={<>SEO title <Counter value={data.meta_title || data.title} range={TITLE_RANGE} /></>} maxLength={200} placeholder={data.title} help="Defaults to the post title." />
            <Field form={form} name="meta_description" label={<>Meta description <Counter value={data.meta_description || data.excerpt} range={DESCRIPTION_RANGE} /></>} type="textarea" rows={3} maxLength={300} help="Defaults to the excerpt." />

            <details className="seo-more">
                <summary>Social sharing</summary>
                <Field form={form} name="og_title" label="Share title" maxLength={200} placeholder={data.meta_title || data.title} help="Shown when the post is shared on social networks." />
                <Field form={form} name="og_description" label="Share description" type="textarea" rows={2} maxLength={300} placeholder={data.meta_description || data.excerpt} />
                <Field form={form} name="og_image" label="Share image address" type="url" placeholder="https://… (defaults to the cover image)" help="1200 × 630 pixels works best." />
            </details>

            <details className="seo-more">
                <summary>Advanced</summary>
                <Field form={form} name="canonical_url" label="Canonical URL" type="url" placeholder={`${blogUrl}/${data.slug || 'post-url'}`} help="Only set this when the same article is also published somewhere else." />
                <label className="check seo-noindex">
                    <input type="checkbox" checked={!!data.noindex} onChange={(event) => form.setData('noindex', event.target.checked)} />
                    Hide this post from search engines (noindex)
                </label>
            </details>

            <div className="seo-checks">
                <h3>Analysis</h3>
                <ul>
                    {result.checks.map((check) => (
                        <li key={check.id} className={check.status} title={check.hint ?? ''}>
                            <span className="seo-dot" aria-hidden="true" />
                            <span>{check.label}{check.hint && check.status !== 'good' ? <em> — {check.hint}</em> : null}</span>
                        </li>
                    ))}
                </ul>
            </div>
        </div>
    );
}
