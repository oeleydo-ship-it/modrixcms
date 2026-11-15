import { useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { themeLayout } from '../runtime';
import ErrorBoundary from '../ErrorBoundary';
import Layout from './Layout';
import PreviewBar from './PreviewBar';

/**
 * Wraps every public page: the theme preview bar, then the theme's layout
 * (or the core layout if the theme has none, or if the theme's layout crashes).
 */
export default function SiteShell({ children }) {
    const { props: { meta, site, embed }, url } = usePage();
    const ThemeLayout = themeLayout();
    const title = meta?.title ?? `${site.name} — ${site.tagline ?? ''}`;

    // Embedded in another page (an iframe): just the content, without the theme's header and footer.
    useEffect(() => {
        if (!embed || window.parent === window) {
            return undefined;
        }
        // Tell the page that frames this one how tall the form is, so it can show all of it.
        const slug = String(url).split('?')[0].split('/').filter(Boolean).pop() ?? '';
        const shell = document.querySelector('.embed-shell');
        if (!shell) {
            return undefined;
        }
        const report = () => window.parent.postMessage({ type: 'modrix-form-height', slug: decodeURIComponent(slug), height: shell.getBoundingClientRect().height + 8 }, window.location.origin);
        report();
        const observer = new ResizeObserver(report);
        observer.observe(shell);

        return () => observer.disconnect();
    }, [embed, url]);

    if (embed) {
        return (
            <>
                <Head title={title}>
                    <meta name="robots" content="noindex" />
                </Head>
                <main className="embed-shell" style={{ padding: '8px 4px' }}>{children}</main>
            </>
        );
    }

    return (
        <>
            <Head title={title}>
                {meta?.description ? <meta name="description" content={meta.description} /> : null}
            </Head>
            <PreviewBar />
            {ThemeLayout ? (
                <ErrorBoundary label="The theme layout" resetKey={url} fallback={<Layout>{children}</Layout>}>
                    <ThemeLayout>{children}</ThemeLayout>
                </ErrorBoundary>
            ) : (
                <Layout>{children}</Layout>
            )}
        </>
    );
}
