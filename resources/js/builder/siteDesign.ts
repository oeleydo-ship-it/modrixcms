/**
 * The site's theme colours (Appearance → Theme settings), embedded in the builder page by
 * resources/views/builder.blade.php. Picking one stores its `value`, a linked reference such
 * as `var(--site-primary, #4f46e5)`, so the block follows later changes to the theme.
 */
export interface SiteDesignColor {
    name: string;
    label: string;
    /** The theme's current value, for the swatch. */
    color: string;
    /** What gets stored on the block. */
    value: string;
}

let cache: SiteDesignColor[] | null = null;

export function siteDesignColors(): SiteDesignColor[] {
    if (cache !== null) return cache;

    try {
        const node = typeof document === 'undefined' ? null : document.getElementById('site-design-colors');
        const parsed: unknown = node?.textContent ? JSON.parse(node.textContent) : [];
        cache = Array.isArray(parsed)
            ? parsed.filter((row): row is SiteDesignColor =>
                typeof row === 'object' && row !== null
                && typeof (row as SiteDesignColor).value === 'string'
                && typeof (row as SiteDesignColor).color === 'string')
            : [];
    } catch {
        cache = [];
    }

    return cache;
}
