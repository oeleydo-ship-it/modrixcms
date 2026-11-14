/**
 * Which style groups are worth promoting for each block type.
 *
 * The rule used to build this map: a group is listed when changing it is a
 * normal part of designing that block, and left off when it either does nothing
 * or is actively misleading. A spacer renders no text, so typography is noise.
 * A divider's thickness is its own prop and its colour is a border, so a font
 * size on it is a trap. An image cares about size and corners but has no line
 * height to speak of.
 *
 * Omission is never a lock: `BlockSettings` renders every group that is not
 * listed here inside a collapsed "Advanced styles" section, so a document that
 * already carries an unusual property stays editable.
 */

import type { BlockType } from '@renderer';

export type StyleGroupId =
    | 'typography'
    | 'background'
    | 'border'
    | 'spacing'
    | 'sizing'
    | 'layout'
    | 'effects';

/** Render order, so panels never disagree about where a group appears. */
export const STYLE_GROUP_ORDER: readonly StyleGroupId[] = [
    'typography',
    'background',
    'border',
    'spacing',
    'sizing',
    'layout',
    'effects',
];

const TEXTUAL: readonly StyleGroupId[] = ['typography', 'background', 'spacing', 'sizing', 'effects'];
const BOXED: readonly StyleGroupId[] = ['background', 'border', 'spacing', 'sizing', 'effects'];
const RICH: readonly StyleGroupId[] = [
    'typography',
    'background',
    'border',
    'spacing',
    'sizing',
    'effects',
];

export const BLOCK_STYLE_GROUPS: Record<BlockType, readonly StyleGroupId[]> = {
    // Layout
    container: ['background', 'border', 'spacing', 'sizing', 'layout', 'effects'],

    // Content
    heading: ['typography', 'background', 'spacing', 'sizing', 'effects'],
    paragraph: TEXTUAL,
    image: ['border', 'spacing', 'sizing', 'effects'],
    video: ['border', 'spacing', 'sizing', 'effects'],
    button: RICH,
    // An icon is coloured and scaled, never line-heighted.
    icon: ['typography', 'spacing', 'sizing', 'effects'],
    // Thickness and style are props; the line colour is the block's text
    // colour, which lives in Typography. A border would box the block instead.
    divider: ['typography', 'spacing', 'sizing'],
    // A spacer is nothing but space.
    spacer: ['spacing', 'sizing'],
    html: ['typography', 'background', 'border', 'spacing', 'sizing'],
    custom_code: ['typography', 'background', 'border', 'spacing', 'sizing'],

    // Capture
    form: RICH,
    email_optin: RICH,
    phone_input: RICH,

    // Commerce
    checkout: RICH,
    product: RICH,
    pricing_table: ['typography', 'background', 'border', 'spacing', 'sizing', 'layout', 'effects'],

    // Persuasion
    countdown: RICH,
    testimonial: RICH,
    faq: RICH,
    accordion: RICH,
    tabs: RICH,
    // A bar's fill and track are colours and corners, not type.
    progress_bar: ['background', 'border', 'spacing', 'sizing'],
    feature_cards: ['typography', 'background', 'border', 'spacing', 'sizing', 'layout', 'effects'],

    // Media and navigation
    carousel: ['background', 'border', 'spacing', 'sizing', 'layout', 'effects'],
    gallery: ['border', 'spacing', 'sizing', 'layout', 'effects'],
    social_icons: ['typography', 'spacing', 'sizing', 'layout'],
    navigation: ['typography', 'background', 'spacing', 'sizing', 'layout'],
    logo: ['border', 'spacing', 'sizing'],
    map: ['border', 'spacing', 'sizing', 'effects'],

    // Scheduling
    calendar: BOXED,
    booking: RICH,

    // Engagement
    popup_trigger: RICH,
};

export function styleGroupsFor(type: BlockType): readonly StyleGroupId[] {
    return BLOCK_STYLE_GROUPS[type] ?? RICH;
}

/** Everything the curated list left out, in render order. */
export function remainingStyleGroups(type: BlockType): readonly StyleGroupId[] {
    const promoted = new Set(styleGroupsFor(type));
    return STYLE_GROUP_ORDER.filter((group) => !promoted.has(group));
}
