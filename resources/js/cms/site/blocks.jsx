import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import Html, { Icon } from './Html';
import PostCard from './PostCard';

/*
 * The core page-builder blocks. Each receives { data, anchor }. Themes can replace any of
 * them by registering a component under the same type (e.g. blocks: { hero: MyHero }).
 */

const lines = (text) => String(text ?? '').split(/\r?\n/);

export function Heading({ data }) {
    if (!data.eyebrow && !data.title && !data.text) {
        return null;
    }

    return (
        <div className="section-head">
            {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
            {data.title ? <h2>{data.title}</h2> : null}
            {data.text ? <Html as="p" html={data.text_html} /> : null}
        </div>
    );
}

export function Hero({ data, anchor }) {
    return (
        <section className="hero" id={anchor}>
            <div className={`wrap ${data.show_mockup ? 'hero-grid' : 'hero-solo'}`}>
                <div className="hero-copy">
                    {data.badge ? <p className="pill"><span className="dot"></span> {data.badge}</p> : null}
                    <h1>
                        {lines(data.title).filter((line) => line.trim() !== '').map((line, i) => <span key={i}>{line}<br /></span>)}
                        {data.highlight ? <em>{data.highlight}</em> : null}
                    </h1>
                    {data.text ? <p className="lead">{data.text}</p> : null}
                    {data.primary_label || data.secondary_label ? (
                        <div className="cta-row">
                            {data.primary_label ? <a className="btn" href={data.primary_url}>{data.primary_label}</a> : null}
                            {data.secondary_label ? <a className="btn btn-ghost" href={data.secondary_url}>{data.secondary_label}</a> : null}
                        </div>
                    ) : null}
                    {data.facts?.length ? (
                        <ul className="hero-facts">
                            {data.facts.map((fact, i) => <li key={i}><b>{fact.value}</b> {fact.label}</li>)}
                        </ul>
                    ) : null}
                </div>
                {data.show_mockup ? <HeroMockup /> : null}
            </div>
        </section>
    );
}

function HeroMockup() {
    return (
        <div className="hero-visual" aria-hidden="true">
            <div className="window">
                <div className="window-bar"><i></i><i></i><i></i><span>yourstore.com/admin</span></div>
                <div className="window-body">
                    <aside className="mock-side">
                        <div className="mock-logo"></div>
                        {['Dashboard', 'Orders', 'Products', 'Pages', 'Themes', 'Extensions', 'Settings'].map((item, i) => (
                            <span key={item} className={i === 0 ? 'on' : undefined}>{item}</span>
                        ))}
                    </aside>
                    <div className="mock-main">
                        <div className="mock-stats">
                            <div><small>Revenue</small><b>$48,210</b><em>+18%</em></div>
                            <div><small>Orders</small><b>1,284</b><em>+9%</em></div>
                            <div><small>Customers</small><b>932</b><em>+12%</em></div>
                        </div>
                        <div className="mock-chart">
                            <svg viewBox="0 0 300 90" preserveAspectRatio="none">
                                <path d="M0 72 C30 66 45 50 70 54 S115 30 140 38 S190 14 215 22 S265 10 300 4" fill="none" stroke="currentColor" strokeWidth="3" />
                                <path d="M0 72 C30 66 45 50 70 54 S115 30 140 38 S190 14 215 22 S265 10 300 4 V90 H0Z" fill="currentColor" opacity=".12" />
                            </svg>
                        </div>
                        <div className="mock-rows">
                            <p><span>#1042 · Maya R.</span><b className="tag paid">Paid</b></p>
                            <p><span>#1041 · Jon K.</span><b className="tag ship">Shipped</b></p>
                            <p><span>#1040 · Ana L.</span><b className="tag paid">Paid</b></p>
                        </div>
                    </div>
                </div>
            </div>
            <div className="float-card fc-1"><b>AI assistant</b><span>Product description drafted ✓</span></div>
            <div className="float-card fc-2"><b>Module installed</b><span>Wishlist · free</span></div>
        </div>
    );
}

export function PageHeader({ data, anchor }) {
    return (
        <section className="page-hero" id={anchor}>
            <div className="wrap narrow">
                {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
                <h1>{data.title}</h1>
                {data.subtitle ? <p className="page-subtitle">{data.subtitle}</p> : null}
            </div>
        </section>
    );
}

export function Text({ data, anchor }) {
    return (
        <section className="block-text" id={anchor}>
            <Html className={`wrap ${data.width === 'narrow' ? 'narrow' : ''} prose`} html={data.content_html} />
        </section>
    );
}

export function Image({ data, anchor }) {
    if (!data.image_url) {
        return null;
    }

    return (
        <figure className={`block-image wrap ${data.width === 'narrow' ? 'narrow' : ''}`} id={anchor}>
            <img src={data.image_url} alt={data.alt} loading="lazy" />
            {data.caption ? <figcaption>{data.caption}</figcaption> : null}
        </figure>
    );
}

export function Stack({ data, anchor }) {
    return (
        <section className="stack" aria-label={data.label || 'Logos'} id={anchor}>
            <div className="wrap stack-row">
                {data.label ? <span className="stack-label">{data.label}</span> : null}
                <ul>{(data.items ?? []).map((item, i) => <li key={i}>{item}</li>)}</ul>
            </div>
        </section>
    );
}

export function Features({ data, anchor }) {
    return (
        <section className="section" id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className="bento">
                    {(data.items ?? []).map((card, i) => (
                        <article key={i} className={['card', card.size === 'wide' && 'card-lg', card.style === 'dark' && 'accent-a', card.style === 'gradient' && 'accent-b'].filter(Boolean).join(' ')}>
                            <div className="ic"><Icon svg={card.icon_svg} /></div>
                            <h3>
                                {card.title}
                                {card.badge ? <small className="soon">{card.badge}</small> : null}
                            </h3>
                            {card.text ? <p>{card.text}</p> : null}
                            {card.chips?.length ? <div className="chips">{card.chips.map((chip, j) => <span key={j}>{chip}</span>)}</div> : null}
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function Marketplace({ data, anchor }) {
    return (
        <section className="section market" id={anchor}>
            <div className="wrap market-grid">
                <div className="market-copy">
                    {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
                    {data.title ? <h2>{data.title}</h2> : null}
                    {data.text ? <Html as="p" html={data.text_html} /> : null}
                    {data.points?.length ? <ul className="checks">{data.points.map((point, i) => <li key={i}>{point}</li>)}</ul> : null}
                    {data.button_label ? <a className="btn" href={data.button_url}>{data.button_label}</a> : null}
                </div>
                <div className="market-tiles">
                    {data.modules?.length ? (
                        <div className="tile-group">
                            <h4>Modules</h4>
                            <div className="tiles">
                                {data.modules.map((module, i) => <span key={i}>{module}</span>)}
                                <span className="more">+ many more</span>
                            </div>
                        </div>
                    ) : null}
                    {data.themes?.length ? (
                        <div className="tile-group">
                            <h4>Themes</h4>
                            <div className="themes">
                                {data.themes.map((theme, i) => <div key={i} className={`theme t${(i % 6) + 1}`}><i></i><span>{theme}</span></div>)}
                            </div>
                        </div>
                    ) : null}
                </div>
            </div>
        </section>
    );
}

export function Steps({ data, anchor }) {
    const [copyLabel, setCopyLabel] = useState('Copy');
    const code = String(data.code ?? '');

    const copy = async () => {
        const text = lines(code).filter((line) => !line.trim().startsWith('#')).join('\n').trim();
        try {
            await navigator.clipboard.writeText(text);
            setCopyLabel('Copied');
        } catch {
            setCopyLabel('Select & copy');
        }
        setTimeout(() => setCopyLabel('Copy'), 1800);
    };

    return (
        <section className="section" id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                {data.items?.length ? (
                    <div className="deploy-grid">
                        {data.items.map((step, i) => (
                            <article key={i} className="deploy-card">
                                <span className="num">{String(i + 1).padStart(2, '0')}</span>
                                <h3>{step.title}</h3>
                                <Html as="p" html={step.text_html} />
                            </article>
                        ))}
                    </div>
                ) : null}
                {code.trim() !== '' ? (
                    <div className="terminal">
                        <div className="term-bar"><i></i><i></i><i></i><span>{data.code_title}</span><button className="copy" type="button" onClick={copy}>{copyLabel}</button></div>
                        <pre><code>{lines(code).map((line, i, all) => (
                            <span key={i}>{line.trim().startsWith('#') ? <span className="c">{line}</span> : line}{i < all.length - 1 ? '\n' : ''}</span>
                        ))}</code></pre>
                    </div>
                ) : null}
            </div>
        </section>
    );
}

export function Comparison({ data, anchor }) {
    return (
        <section className="section oss" id={anchor}>
            <div className="wrap oss-grid">
                <div>
                    {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
                    {data.title ? <h2>{data.title}</h2> : null}
                    {data.text ? <p>{data.text}</p> : null}
                </div>
                {data.rows?.length ? (
                    <dl className="compare">
                        {data.rows.map((row, i) => <div key={i}><dt>{row.label}</dt><dd><b>{row.us}</b>{row.them ? <s>{row.them}</s> : null}</dd></div>)}
                    </dl>
                ) : null}
            </div>
        </section>
    );
}

export function LatestPosts({ data, anchor }) {
    const { links } = usePage().props;

    if (!data.posts?.length) {
        return null;
    }

    return (
        <section className="section" id={anchor}>
            <div className="wrap">
                <div className="section-head section-head-row">
                    <div>
                        {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
                        {data.title ? <h2>{data.title}</h2> : null}
                    </div>
                    <Link className="btn btn-ghost" href={links.blog}>All posts →</Link>
                </div>
                <div className="post-grid">{data.posts.map((post) => <PostCard key={post.id} post={post} />)}</div>
            </div>
        </section>
    );
}

export function Cta({ data, anchor }) {
    return (
        <section className="final" id={anchor}>
            <div className="wrap final-inner">
                {data.title ? <h2>{data.title}</h2> : null}
                {data.text ? <p>{data.text}</p> : null}
                <div className="cta-row center">
                    {data.primary_label ? <a className="btn btn-light" href={data.primary_url}>{data.primary_label}</a> : null}
                    {data.secondary_label ? <a className="btn btn-ghost-light" href={data.secondary_url}>{data.secondary_label}</a> : null}
                </div>
            </div>
        </section>
    );
}

export function BannerSplit({ data, anchor }) {
    const isLeft = data.position === 'left';
    const themeClass = data.theme === 'dark' ? 'split-dark' : data.theme === 'white' ? 'split-white' : 'split-plain';

    return (
        <section className={`section split-section ${themeClass}`} id={anchor}>
            <div className={`wrap split-grid ${isLeft ? 'split-reverse' : ''}`}>
                <div className="split-copy">
                    {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
                    {data.title ? <h2>{data.title}</h2> : null}
                    {data.text ? <Html as="p" className="split-lead" html={data.text_html} /> : null}
                    {data.points?.length ? (
                        <ul className="split-checks">
                            {data.points.map((point, i) => <li key={i}>{point}</li>)}
                        </ul>
                    ) : null}
                    {data.primary_label || data.secondary_label ? (
                        <div className="cta-row">
                            {data.primary_label ? <a className={`btn ${data.theme === 'dark' ? 'btn-light' : ''}`} href={data.primary_url}>{data.primary_label}</a> : null}
                            {data.secondary_label ? <a className={`btn ${data.theme === 'dark' ? 'btn-ghost-light' : 'btn-ghost'}`} href={data.secondary_url}>{data.secondary_label}</a> : null}
                        </div>
                    ) : null}
                </div>
                <div className="split-visual">
                    {data.image_url ? (
                        <div className="split-image-wrap">
                            <img src={data.image_url} alt={data.alt || data.title || ''} loading="lazy" />
                        </div>
                    ) : (
                        <div className="split-showcase-card" aria-hidden="true">
                            <div className="split-showcase-head">
                                <span><i></i><i></i><i></i></span>
                                <small>modrix.config.php</small>
                            </div>
                            <div className="split-showcase-body">
                                <div className="split-showcase-bars">
                                    <div><span>Edge Cache</span><b>99.4% hit</b></div>
                                    <div><span>TTFB</span><b>38ms</b></div>
                                    <div><span>Fee Rate</span><b>0.00%</b></div>
                                </div>
                            </div>
                        </div>
                    )}
                    {data.stat_value || data.stat_label ? (
                        <div className="split-stat-badge">
                            {data.stat_value ? <b>{data.stat_value}</b> : null}
                            {data.stat_label ? <span>{data.stat_label}</span> : null}
                        </div>
                    ) : null}
                </div>
            </div>
        </section>
    );
}

export function Stats({ data, anchor }) {
    const theme = data.theme || 'dark';
    const cols = data.columns === '3' ? 'cols-3' : 'cols-4';

    return (
        <section className={`section stats-block stats-${theme}`} id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className={`stats-grid ${cols}`}>
                    {(data.items ?? []).map((item, i) => (
                        <article key={i} className="stat-card">
                            {item.icon_svg ? <div className="stat-ic"><Icon svg={item.icon_svg} /></div> : null}
                            <strong className="stat-val">{item.value}</strong>
                            <span className="stat-lbl">{item.label}</span>
                            {item.detail ? <small className="stat-dtl">{item.detail}</small> : null}
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function Pricing({ data, anchor }) {
    return (
        <section className="section pricing-section" id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className="pricing-grid">
                    {(data.plans ?? []).map((plan, i) => (
                        <article key={i} className={`pricing-card ${plan.featured ? 'is-featured' : ''}`}>
                            <div className="pricing-top">
                                <div>
                                    <h3>{plan.name}</h3>
                                    {plan.badge ? <span className="pricing-badge">{plan.badge}</span> : null}
                                </div>
                                <div className="pricing-price">
                                    <b>{plan.price}</b>
                                    {plan.period ? <span>{plan.period}</span> : null}
                                </div>
                                {plan.description ? <Html as="p" className="pricing-desc" html={plan.description_html} /> : null}
                            </div>
                            {plan.features?.length ? (
                                <ul className="pricing-features">
                                    {plan.features.map((feat, j) => <li key={j}>{feat}</li>)}
                                </ul>
                            ) : null}
                            {plan.button_label ? (
                                <a className={`btn ${plan.featured ? 'btn-light' : 'btn-ghost'} pricing-btn`} href={plan.button_url}>
                                    {plan.button_label}
                                </a>
                            ) : null}
                        </article>
                    ))}
                </div>
                {data.note ? <p className="pricing-note">{data.note}</p> : null}
            </div>
        </section>
    );
}

export function Testimonials({ data, anchor }) {
    const items = data.items ?? [];
    const layout = data.layout || 'grid';
    const theme = data.theme === 'dark' ? 'testi-dark' : 'testi-plain';

    return (
        <section className={`section testi-section ${theme}`} id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className={`testi-grid layout-${layout}`}>
                    {items.map((item, i) => {
                        const stars = Math.max(1, Math.min(5, Number(item.rating) || 5));
                        return (
                            <blockquote key={i} className={`testi-card ${layout === 'spotlight' && i === 0 ? 'testi-featured' : ''}`}>
                                <div className="testi-head">
                                    <span className="testi-stars" aria-label={`${stars} out of 5 stars`}>{'★'.repeat(stars)}</span>
                                    {item.badge ? <span className="testi-badge">{item.badge}</span> : null}
                                </div>
                                <Html as="p" className="testi-quote" html={item.quote_html} />
                                <footer className="testi-author">
                                    <span className="testi-avatar">{String(item.author || 'M').slice(0, 1).toUpperCase()}</span>
                                    <div>
                                        <b>{item.author}</b>
                                        {item.role ? <small>{item.role}</small> : null}
                                    </div>
                                </footer>
                            </blockquote>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export function Faq({ data, anchor }) {
    const [openIndex, setOpenIndex] = useState(0);
    const layout = data.layout || 'split';

    return (
        <section className="section faq-section" id={anchor}>
            <div className={`wrap faq-wrap faq-${layout}`}>
                <div className="faq-intro">
                    {data.eyebrow ? <p className="eyebrow">{data.eyebrow}</p> : null}
                    {data.title ? <h2>{data.title}</h2> : null}
                    {data.text ? <Html as="p" html={data.text_html} /> : null}
                    {data.contact_label ? (
                        <div style={{ marginTop: '1.5rem' }}>
                            <a className="btn btn-ghost btn-sm" href={data.contact_url}>{data.contact_label}</a>
                        </div>
                    ) : null}
                </div>
                <div className="faq-list">
                    {(data.items ?? []).map((item, i) => {
                        const isOpen = openIndex === i;
                        return (
                            <div key={i} className={`faq-item ${isOpen ? 'is-open' : ''}`}>
                                <button
                                    type="button"
                                    className="faq-q"
                                    aria-expanded={isOpen}
                                    onClick={() => setOpenIndex(isOpen ? -1 : i)}
                                >
                                    <span>{item.question}</span>
                                    <i aria-hidden="true">{isOpen ? '−' : '+'}</i>
                                </button>
                                {isOpen ? (
                                    <div className="faq-a">
                                        <Html as="p" html={item.answer_html} />
                                    </div>
                                ) : null}
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

export function Gallery({ data, anchor }) {
    const cols = data.columns === '2' ? 'cols-2' : data.columns === '4' ? 'cols-4' : 'cols-3';

    return (
        <section className="section gallery-section" id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className={`gallery-bento ${cols}`}>
                    {(data.items ?? []).map((item, i) => {
                        const Tag = item.url && item.url !== '#' ? 'a' : 'article';
                        const linkProps = Tag === 'a' ? { href: item.url } : {};
                        return (
                            <Tag key={i} className={`gallery-card accent-${item.accent || 'violet'}`} {...linkProps}>
                                <div className="gallery-media">
                                    {item.image_url ? (
                                        <img src={item.image_url} alt={item.title || ''} loading="lazy" />
                                    ) : (
                                        <div className="gallery-art" aria-hidden="true">
                                            <span>{String(i + 1).padStart(2, '0')}</span>
                                        </div>
                                    )}
                                    {item.tag ? <span className="gallery-tag">{item.tag}</span> : null}
                                </div>
                                <div className="gallery-body">
                                    {item.title ? <h3>{item.title}</h3> : null}
                                    {item.caption ? <Html as="p" html={item.caption_html} /> : null}
                                </div>
                            </Tag>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function resolveVideoEmbed(url) {
    if (!url || url === '#') return null;
    const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]{6,})/);
    if (yt) return { type: 'iframe', src: `https://www.youtube.com/embed/${yt[1]}` };
    const vimeo = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    if (vimeo) return { type: 'iframe', src: `https://player.vimeo.com/video/${vimeo[1]}` };
    return { type: 'video', src: url };
}

export function VideoShowcase({ data, anchor }) {
    const embed = resolveVideoEmbed(data.video_url);
    const isDark = (data.theme || 'dark') === 'dark';

    return (
        <section className={`section video-section ${isDark ? 'video-dark' : 'video-light'}`} id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className="video-stage">
                    {embed ? (
                        embed.type === 'iframe' ? (
                            <div className="video-ratio">
                                <iframe src={embed.src} title={data.title || 'Video'} allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowFullScreen />
                            </div>
                        ) : (
                            <div className="video-ratio">
                                <video src={embed.src} controls preload="metadata" />
                            </div>
                        )
                    ) : (
                        <div className="video-placeholder">
                            <div className="video-play-badge" aria-hidden="true">▶</div>
                            <strong>{data.title || 'Interactive Product Walkthrough'}</strong>
                            {data.caption ? <span>{data.caption}</span> : null}
                        </div>
                    )}
                </div>
                {data.highlights?.length || data.primary_label ? (
                    <div className="video-footer">
                        {data.highlights?.length ? (
                            <ul className="video-highlights">
                                {data.highlights.map((h, i) => <li key={i}>{h}</li>)}
                            </ul>
                        ) : null}
                        {data.primary_label ? (
                            <a className={`btn ${isDark ? 'btn-light' : ''}`} href={data.primary_url}>{data.primary_label}</a>
                        ) : null}
                    </div>
                ) : null}
            </div>
        </section>
    );
}

export function Timeline({ data, anchor }) {
    return (
        <section className="section timeline-section" id={anchor}>
            <div className="wrap">
                <Heading data={data} />
                <div className="timeline-track">
                    {(data.items ?? []).map((item, i) => (
                        <article key={i} className={`timeline-card status-${item.status || 'done'}`}>
                            <div className="timeline-meta">
                                {item.phase ? <span className="timeline-phase">{item.phase}</span> : null}
                                <span className={`timeline-status badge-${item.status || 'done'}`}>
                                    {item.status === 'active' ? 'In progress' : item.status === 'upcoming' ? 'Planned' : 'Completed'}
                                </span>
                            </div>
                            <h3>{item.title}</h3>
                            {item.text ? <Html as="p" html={item.text_html} /> : null}
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function NewsletterCta({ data, anchor }) {
    const theme = data.theme || 'dark';

    return (
        <section className="section newsletter-section" id={anchor}>
            <div className={`wrap newsletter-box newsletter-${theme}`}>
                <div className="newsletter-copy">
                    {data.badge ? <span className="newsletter-pill">{data.badge}</span> : null}
                    {data.title ? <h2>{data.title}</h2> : null}
                    {data.text ? <Html as="p" html={data.text_html} /> : null}
                    {data.perks?.length ? (
                        <ul className="newsletter-perks">
                            {data.perks.map((perk, i) => <li key={i}>{perk}</li>)}
                        </ul>
                    ) : null}
                </div>
                <form className="newsletter-form" onSubmit={(e) => { e.preventDefault(); if (data.button_url && data.button_url !== '#') window.location.href = data.button_url; }}>
                    <input type="email" placeholder={data.placeholder || 'you@example.com'} aria-label={data.placeholder || 'Email address'} required />
                    <button type="submit" className={`btn ${theme === 'lime' ? '' : 'btn-light'}`}>
                        {data.button_label || 'Subscribe'}
                    </button>
                </form>
            </div>
        </section>
    );
}

export const coreBlocks = {
    hero: Hero,
    page_header: PageHeader,
    text: Text,
    image: Image,
    stack: Stack,
    features: Features,
    banner_split: BannerSplit,
    stats: Stats,
    marketplace: Marketplace,
    pricing: Pricing,
    testimonials: Testimonials,
    faq: Faq,
    gallery: Gallery,
    video_showcase: VideoShowcase,
    timeline: Timeline,
    steps: Steps,
    comparison: Comparison,
    latest_posts: LatestPosts,
    newsletter_cta: NewsletterCta,
    cta: Cta,
};
