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
    const { props: { meta, site }, url } = usePage();
    const ThemeLayout = themeLayout();
    const title = meta?.title ?? `${site.name} — ${site.tagline ?? ''}`;

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
