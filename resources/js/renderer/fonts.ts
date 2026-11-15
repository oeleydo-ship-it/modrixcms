/**
 * The font families the builder offers, and the web-font loading for them.
 *
 * The stored style value is the CSS stack itself (`'Poppins', sans-serif`), not
 * a key, so a document stays readable by the PHP side and by any page saved
 * before this list existed. The renderer finds which Google fonts a page's CSS
 * actually uses and imports only those — a page that picks no web font makes
 * no extra request.
 */

export interface FontFamilyOption {
    /** The CSS `font-family` value stored in the document. */
    value: string;
    label: string;
    group: 'System' | 'Sans serif' | 'Serif' | 'Display' | 'Handwriting' | 'Monospace';
    /** Google Fonts family name, when the font has to be downloaded. */
    google?: string;
    /** Semicolon-separated weights to request from Google Fonts. */
    weights?: string;
}

/** Weights Google serves for most variable families. */
const ALL = '300;400;500;600;700;800';

/**
 * `weights` must list only weights the family really has: the css2 API rejects
 * the whole request — every font on the page — if one weight does not exist.
 */
function google(name: string, weights: string, group: FontFamilyOption['group'], fallback: string): FontFamilyOption {
    return { value: `'${name}', ${fallback}`, label: name, group, google: name, weights };
}

export const FONT_FAMILIES: readonly FontFamilyOption[] = [
    { value: 'Metropolis, Arial, sans-serif', label: 'Metropolis', group: 'Sans serif' },
    { value: 'Sora, sans-serif', label: 'Sora', group: 'Sans serif' },
    { value: 'Parkinsans, sans-serif', label: 'Parkinsans', group: 'Sans serif' },
    { value: '"Montserrat", "Segoe UI", system-ui, sans-serif', label: 'Montserrat', group: 'Sans serif' },
    { value: '"PT Sans", Arial, sans-serif', label: 'PT Sans', group: 'Sans serif' },
    // No download: these match the website design fonts and render instantly.
    { value: "Inter, 'Segoe UI', system-ui, -apple-system, sans-serif", label: 'System sans', group: 'System' },
    { value: "'Trebuchet MS', 'Segoe UI', system-ui, sans-serif", label: 'Friendly sans', group: 'System' },
    { value: "'Century Gothic', Futura, 'Avenir Next', Avenir, system-ui, sans-serif", label: 'Geometric sans', group: 'System' },
    { value: "Georgia, 'Times New Roman', serif", label: 'Classic serif', group: 'System' },
    { value: "'Palatino Linotype', Palatino, 'Book Antiqua', Georgia, serif", label: 'Elegant serif', group: 'System' },
    { value: "'Inter Tight', Arial, sans-serif", label: 'Inter Tight', group: 'System' },
    { value: "'Libre Caslon Text', Georgia, serif", label: 'Libre Caslon Text', group: 'System' },
    { value: "'Geist Mono', monospace", label: 'Geist Mono', group: 'System' },

    google('Inter', ALL, 'Sans serif', 'sans-serif'),
    google('Geist', '400;500;600;700', 'Sans serif', 'sans-serif'),
    google('Be Vietnam Pro', ALL, 'Sans serif', 'sans-serif'),
    google('Roboto', '300;400;500;700', 'Sans serif', 'sans-serif'),
    google('Dosis', '300;400;500;600;700;800', 'Sans serif', 'sans-serif'),
    google('Open Sans', ALL, 'Sans serif', 'sans-serif'),
    google('Lato', '300;400;700', 'Sans serif', 'sans-serif'),
    google('Montserrat', ALL, 'Sans serif', 'sans-serif'),
    google('Poppins', ALL, 'Sans serif', 'sans-serif'),
    google('Raleway', ALL, 'Sans serif', 'sans-serif'),
    google('Nunito', ALL, 'Sans serif', 'sans-serif'),
    google('Nunito Sans', ALL, 'Sans serif', 'sans-serif'),
    google('Work Sans', ALL, 'Sans serif', 'sans-serif'),
    google('DM Sans', ALL, 'Sans serif', 'sans-serif'),
    google('Manrope', '300;400;500;600;700;800', 'Sans serif', 'sans-serif'),
    google('Plus Jakarta Sans', ALL, 'Sans serif', 'sans-serif'),
    google('Outfit', ALL, 'Sans serif', 'sans-serif'),
    google('Rubik', ALL, 'Sans serif', 'sans-serif'),
    google('Mulish', ALL, 'Sans serif', 'sans-serif'),
    google('Source Sans 3', ALL, 'Sans serif', 'sans-serif'),
    google('Barlow', ALL, 'Sans serif', 'sans-serif'),
    google('Figtree', ALL, 'Sans serif', 'sans-serif'),
    google('Instrument Sans', '400;500;600;700', 'Sans serif', 'sans-serif'),
    google('Space Grotesk', '300;400;500;600;700', 'Sans serif', 'sans-serif'),

    google('Playfair Display', '400;500;600;700;800', 'Serif', 'serif'),
    google('Merriweather', '300;400;700', 'Serif', 'serif'),
    google('Lora', '400;500;600;700', 'Serif', 'serif'),
    google('PT Serif', '400;700', 'Serif', 'serif'),
    google('Libre Baskerville', '400;700', 'Serif', 'serif'),
    google('EB Garamond', '400;500;600;700;800', 'Serif', 'serif'),
    google('Cormorant Garamond', '300;400;500;600;700', 'Serif', 'serif'),
    google('DM Serif Display', '400', 'Serif', 'serif'),
    google('Crimson Text', '400;600;700', 'Serif', 'serif'),

    google('Oswald', '300;400;500;600;700', 'Display', 'sans-serif'),
    google('Bebas Neue', '400', 'Display', 'sans-serif'),
    google('Anton', '400', 'Display', 'sans-serif'),
    google('Archivo Black', '400', 'Display', 'sans-serif'),
    google('Abril Fatface', '400', 'Display', 'serif'),

    google('Dancing Script', '400;500;600;700', 'Handwriting', 'cursive'),
    google('Pacifico', '400', 'Handwriting', 'cursive'),
    google('Caveat', '400;500;600;700', 'Handwriting', 'cursive'),
    google('Great Vibes', '400', 'Handwriting', 'cursive'),

    google('JetBrains Mono', '300;400;500;600;700;800', 'Monospace', 'monospace'),
    google('Fira Code', '300;400;500;600;700', 'Monospace', 'monospace'),
    google('Space Mono', '400;700', 'Monospace', 'monospace'),
];

export const FONT_FAMILY_GROUPS: readonly FontFamilyOption['group'][] = [
    'System',
    'Sans serif',
    'Serif',
    'Display',
    'Handwriting',
    'Monospace',
];

const GOOGLE_FONTS = FONT_FAMILIES.filter((font) => font.google !== undefined);

/**
 * The option a stored value corresponds to. Matching is on the first family in
 * the stack, ignoring quotes and case, so `"Poppins",sans-serif` typed by hand
 * before this list existed still shows as Poppins.
 */
export function fontOptionFor(value: string): FontFamilyOption | undefined {
    const exact = FONT_FAMILIES.find((font) => font.value === value);
    if (exact) {
        return exact;
    }
    const first = primaryFamily(value);
    return FONT_FAMILIES.find((font) => primaryFamily(font.value) === first);
}

function primaryFamily(stack: string): string {
    return (stack.split(',')[0] ?? '').replace(/["']/g, '').trim().toLowerCase();
}

/**
 * An `@import` for every Google font the CSS references, or an empty string.
 * Must be placed first in the stylesheet: `@import` after any rule is ignored.
 */
export function googleFontsImport(css: string): string {
    const used = GOOGLE_FONTS.filter((font) => {
        const name = font.google as string;
        return css.includes(`'${name}'`) || css.includes(`"${name}"`);
    });

    if (used.length === 0) {
        return '';
    }

    const families = used
        .map((font) => `family=${encodeURIComponent(font.google as string).replace(/%20/g, '+')}${font.weights && font.weights !== '400' ? `:wght@${font.weights}` : ''}`)
        .join('&');

    return `@import url('https://fonts.googleapis.com/css2?${families}&display=swap');`;
}
