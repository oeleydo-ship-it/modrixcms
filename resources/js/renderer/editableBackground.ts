import type { StyleData, Breakpoint } from './types';

/** Promote the early template's combined CSS layer into editable section controls. */
export function editableSectionBackground(style: StyleData): StyleData {
    const legacy = style.custom?.['background-image'];
    if (!legacy) return style;
    const next: StyleData = { ...style, custom: { ...style.custom } };
    const remaining = { ...legacy };
    for (const breakpoint of ['desktop', 'tablet', 'mobile'] as const) {
        const value = legacy[breakpoint];
        if (typeof value !== 'string') continue;
        const match = value.match(/^(linear-gradient\(.+\)),\s*url\(["']?([^"')]+)["']?\)$/i);
        if (!match) continue;
        next.backgroundImage = { ...next.backgroundImage, [breakpoint]: next.backgroundImage?.[breakpoint] ?? match[2] };
        next.overlayColor = { ...next.overlayColor, [breakpoint]: next.overlayColor?.[breakpoint] ?? match[1] };
        delete remaining[breakpoint as Breakpoint];
    }
    if (Object.keys(remaining).length) next.custom!['background-image'] = remaining;
    else delete next.custom!['background-image'];
    return next;
}
