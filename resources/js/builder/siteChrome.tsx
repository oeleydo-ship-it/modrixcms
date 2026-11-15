/**
 * The site header and footer (special pages edited in this builder) shown locked above and
 * below the page on the canvas, so the page is edited in context. They come from
 * resources/views/builder.blade.php; editing them happens on their own builder screen.
 */
import type { ReactNode } from 'react';
import { Lock } from 'lucide-react';
import { PageRenderer, normaliseSection, type PageDocument, type Breakpoint } from '@renderer';

interface ChromePart {
    sections: unknown[];
    editUrl: string;
    shown: boolean;
}

interface SiteChromeData {
    header: ChromePart | null;
    footer: ChromePart | null;
}

let cache: SiteChromeData | null = null;

export function siteChrome(): SiteChromeData {
    if (cache !== null) return cache;

    try {
        const node = document.getElementById('site-chrome');
        const parsed = node?.textContent ? (JSON.parse(node.textContent) as Partial<SiteChromeData>) : {};
        cache = { header: parsed.header ?? null, footer: parsed.footer ?? null };
    } catch {
        cache = { header: null, footer: null };
    }

    return cache;
}

/** One locked band: the published header or footer, drawn like the page, with an edit link. */
export function SiteChromeBand({ part, label, document, breakpoint, placeholder }: {
    part: ChromePart | null;
    label: 'header' | 'footer';
    document: PageDocument;
    breakpoint: Breakpoint;
    placeholder?: ReactNode;
}) {
    if (!part) return null;

    const title = label === 'header' ? 'Site header' : 'Site footer';
    const note = !part.shown
        ? `${title} is switched off for this page (page settings).`
        : part.sections.length === 0
          ? `No ${label} yet: build and publish the site ${label} once and it appears on every page.`
          : null;

    return (
        <div className="fnl-site-chrome" data-chrome={label} aria-label={`${title} (shared by all pages)`}>
            <div className="fnl-site-chrome-bar">
                <span><Lock aria-hidden size={12} /> {title} · shared by every page</span>
                <a href={part.editUrl}>Edit global {label}</a>
            </div>
            {note ? (
                <div className="fnl-site-chrome-empty">{note}{placeholder}</div>
            ) : (
                <div className="fnl-site-chrome-content" inert>
                    <PageRenderer
                        document={{ ...document, sections: part.sections.map(normaliseSection) }}
                        mode="static"
                        breakpoint={breakpoint}
                    />
                </div>
            )}
        </div>
    );
}
