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

export const coreBlocks = {
    hero: Hero,
    page_header: PageHeader,
    text: Text,
    image: Image,
    stack: Stack,
    features: Features,
    marketplace: Marketplace,
    steps: Steps,
    comparison: Comparison,
    latest_posts: LatestPosts,
    cta: Cta,
};
