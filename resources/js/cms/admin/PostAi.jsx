import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { enqueuePosts, notify, postJson, updateNote, useAiJobs } from './aiJobs';

export { postJson };

const TONES = ['Friendly and clear', 'Professional', 'Conversational', 'Authoritative', 'Inspiring', 'Playful'];
const LENGTHS = { short: 'Short (~500 words)', medium: 'Medium (~900 words)', long: 'Long (~1500 words)' };

function NotConfigured() {
    return (
        <p className="hint">
            The AI is not set up yet. <Link href={route('admin.settings.ai')}>Add your API key in Settings → AI API</Link> and it will work here.
        </p>
    );
}

/** In the editor: describe a post, and the AI fills the title, text and SEO fields for review. */
export function PostAiWriter({ form, aiConfigured, hasContent }) {
    const [open, setOpen] = useState(false);
    const [brief, setBrief] = useState({ topic: '', keywords: '', tone: TONES[0], length: 'medium', audience: '' });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [done, setDone] = useState(false);
    const set = (name) => (event) => setBrief((current) => ({ ...current, [name]: event.target.value }));

    const write = async () => {
        if (hasContent && !window.confirm('Replace the current title, content and SEO fields with the AI draft?')) {
            return;
        }
        setBusy(true);
        setError('');
        setDone(false);
        const note = notify({ state: 'active', topic: brief.topic, kind: 'writer', startedAt: Date.now() });
        try {
            const result = await postJson('admin.posts.ai.write', brief);
            form.setData((data) => ({
                ...data,
                title: result.title,
                slug: result.slug,
                excerpt: result.excerpt,
                content: result.content,
                meta_title: result.meta_title,
                meta_description: result.meta_description,
                focus_keyword: result.focus_keyword,
                og_title: result.og_title,
                og_description: result.og_description,
                category_id: result.category_id ?? data.category_id,
            }));
            setDone(true);
            updateNote(note, { state: 'done', title: result.title, words: result.words });
        } catch (err) {
            setError(err.message);
            updateNote(note, { state: 'error', message: err.message });
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="panel ai-writer">
            <button type="button" className="ai-writer-toggle" onClick={() => setOpen((value) => !value)} aria-expanded={open}>
                <span className="ai-spark" aria-hidden="true">✦</span>
                <strong>Write with AI</strong>
                <span className="sub">Draft the post and its SEO from a topic</span>
                <span className="ai-chevron">{open ? '▴' : '▾'}</span>
            </button>
            {open ? (
                <div className="ai-writer-body">
                    {!aiConfigured ? <NotConfigured /> : null}
                    <div className="field">
                        <label htmlFor="ai-topic">What is the post about?</label>
                        <textarea id="ai-topic" rows={2} value={brief.topic} onChange={set('topic')} placeholder="e.g. Five signs your operations are ready for AI automation" />
                    </div>
                    <div className="ai-row">
                        <div className="field"><label htmlFor="ai-keywords">Keywords to include</label><input id="ai-keywords" type="text" value={brief.keywords} onChange={set('keywords')} placeholder="ai automation, workflow" /></div>
                        <div className="field"><label htmlFor="ai-audience">Audience</label><input id="ai-audience" type="text" value={brief.audience} onChange={set('audience')} placeholder="Operations managers" /></div>
                    </div>
                    <div className="ai-row">
                        <div className="field"><label htmlFor="ai-tone">Tone</label><select id="ai-tone" value={brief.tone} onChange={set('tone')}>{TONES.map((tone) => <option key={tone}>{tone}</option>)}</select></div>
                        <div className="field"><label htmlFor="ai-length">Length</label><select id="ai-length" value={brief.length} onChange={set('length')}>{Object.entries(LENGTHS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div>
                    </div>
                    {error ? <div className="error">{error}</div> : null}
                    {done ? <p className="hint ai-done">Draft added below. Read it through, edit what you like and press Save.</p> : null}
                    <button type="button" className="btn" disabled={busy || !aiConfigured || brief.topic.trim().length < 4} onClick={write}>
                        {busy ? 'Writing… this can take a minute' : 'Write the post'}
                    </button>
                </div>
            ) : null}
        </div>
    );
}

/** On the posts list: the auto blog writes and saves several posts, one request per topic. */
export function AutoBlogPanel({ categories, aiConfigured, onClose }) {
    const [niche, setNiche] = useState('');
    const [topicsText, setTopicsText] = useState('');
    const [count, setCount] = useState(5);
    const [options, setOptions] = useState({ tone: TONES[0], length: 'medium', audience: '', category_id: '', publish: 'draft', start: '', every: 2 });
    const [suggesting, setSuggesting] = useState(false);
    const { jobs } = useAiJobs();
    const running = jobs.some((job) => job.state === 'queued' || job.state === 'active');
    const [error, setError] = useState('');
    const [started, setStarted] = useState(false);
    const set = (name) => (event) => setOptions((current) => ({ ...current, [name]: event.target.value }));
    const topics = topicsText.split('\n').map((line) => line.trim()).filter((line) => line.length >= 4);

    const suggest = async () => {
        setSuggesting(true);
        setError('');
        try {
            const result = await postJson('admin.posts.ai.topics', { niche, count });
            setTopicsText(result.topics.join('\n'));
        } catch (err) {
            setError(err.message);
        } finally {
            setSuggesting(false);
        }
    };

    const run = () => {
        setError('');
        setStarted(true);
        const base = options.start ? new Date(options.start) : new Date();
        enqueuePosts(topics, (topic, index) => ({
            topic,
            tone: options.tone,
            length: options.length,
            audience: options.audience || undefined,
            category_id: options.category_id || undefined,
            publish: options.publish,
            publish_at: options.publish === 'schedule'
                ? new Date(base.getTime() + index * Number(options.every || 1) * 86400000 - base.getTimezoneOffset() * 60000).toISOString().slice(0, 16)
                : undefined,
        }));
        setTopicsText('');
    };

    return (
        <div className="panel auto-blog">
            <div className="auto-blog-head">
                <div>
                    <h2><span className="ai-spark" aria-hidden="true">✦</span> AI auto blog</h2>
                    <p className="hint">Give it topics (or let it suggest some) and it writes full posts with SEO, using the AI set up in Settings → AI API.</p>
                </div>
                <button type="button" className="btn btn-light btn-sm" onClick={onClose}>Close</button>
            </div>

            {!aiConfigured ? <NotConfigured /> : null}

            <div className="ai-row">
                <div className="field" style={{ flex: 2 }}>
                    <label htmlFor="ab-niche">Subject of your blog</label>
                    <input id="ab-niche" type="text" value={niche} onChange={(event) => setNiche(event.target.value)} placeholder="e.g. AI automation for small businesses"  />
                </div>
                <div className="field" style={{ maxWidth: 110 }}>
                    <label htmlFor="ab-count">Ideas</label>
                    <input id="ab-count" type="number" min={1} max={20} value={count} onChange={(event) => setCount(event.target.value)}  />
                </div>
                <div className="field" style={{ alignSelf: 'flex-end' }}>
                    <button type="button" className="btn btn-light" onClick={suggest} disabled={!aiConfigured || suggesting || running || niche.trim().length < 3}>{suggesting ? 'Thinking…' : 'Suggest topics'}</button>
                </div>
            </div>

            <div className="field">
                <label htmlFor="ab-topics">Topics (one per line)</label>
                <textarea id="ab-topics" rows={5} value={topicsText} onChange={(event) => setTopicsText(event.target.value)} placeholder={'Five signs your operations are ready for AI\nHow to measure the ROI of an AI assistant'}  />
            </div>

            <div className="ai-row">
                <div className="field"><label htmlFor="ab-tone">Tone</label><select id="ab-tone" value={options.tone} onChange={set('tone')} >{TONES.map((tone) => <option key={tone}>{tone}</option>)}</select></div>
                <div className="field"><label htmlFor="ab-length">Length</label><select id="ab-length" value={options.length} onChange={set('length')} >{Object.entries(LENGTHS).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></div>
                <div className="field"><label htmlFor="ab-cat">Category</label>
                    <select id="ab-cat" value={options.category_id} onChange={set('category_id')} >
                        <option value="">Let the AI choose</option>
                        {categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                    </select>
                </div>
            </div>
            <div className="ai-row">
                <div className="field"><label htmlFor="ab-publish">Save as</label>
                    <select id="ab-publish" value={options.publish} onChange={set('publish')} >
                        <option value="draft">Drafts (review first)</option>
                        <option value="schedule">Scheduled over several days</option>
                        <option value="now">Published right away</option>
                    </select>
                </div>
                {options.publish === 'schedule' ? (
                    <>
                        <div className="field"><label htmlFor="ab-start">First post goes live</label><input id="ab-start" type="datetime-local" value={options.start} onChange={set('start')}  /></div>
                        <div className="field"><label htmlFor="ab-every">Then every (days)</label><input id="ab-every" type="number" min={1} max={30} value={options.every} onChange={set('every')}  /></div>
                    </>
                ) : <div className="field"><label htmlFor="ab-aud">Audience</label><input id="ab-aud" type="text" value={options.audience} onChange={set('audience')} placeholder="Optional"  /></div>}
            </div>

            {error ? <div className="error">{error}</div> : null}

            <button type="button" className="btn" onClick={run} disabled={!aiConfigured || topics.length === 0 || (options.publish === 'schedule' && !options.start)}>
                {topics.length > 0 ? `Write ${topics.length} post${topics.length === 1 ? '' : 's'}` : 'Write posts'}{running ? ' (adds to the queue)' : ''}
            </button>

            {started ? (
                <p className="hint ai-done">
                    The AI agent is writing your posts. You can keep working: each one shows in the notifications
                    (bottom right) and in the list below as soon as it is ready to publish.
                </p>
            ) : null}
        </div>
    );
}
