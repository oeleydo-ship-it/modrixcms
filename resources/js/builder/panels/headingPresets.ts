import type { StyleData } from '@renderer';

/** Selecting a semantic level also applies an editable, responsive size preset. */
export const HEADING_PRESETS: Record<string, Pick<StyleData, 'fontSize' | 'lineHeight'>> = {
    h1: { fontSize: { desktop: '64px', tablet: '48px', mobile: '40px' }, lineHeight: { desktop: '1.1' } },
    h2: { fontSize: { desktop: '48px', tablet: '40px', mobile: '32px' }, lineHeight: { desktop: '1.15' } },
    h3: { fontSize: { desktop: '36px', tablet: '30px', mobile: '26px' }, lineHeight: { desktop: '1.2' } },
    h4: { fontSize: { desktop: '28px', tablet: '26px', mobile: '22px' }, lineHeight: { desktop: '1.25' } },
    h5: { fontSize: { desktop: '22px', tablet: '20px', mobile: '18px' }, lineHeight: { desktop: '1.3' } },
    h6: { fontSize: { desktop: '18px', tablet: '18px', mobile: '16px' }, lineHeight: { desktop: '1.35' } },
};
