import type { DesignBlock } from './api';

export const DESIGN_GROUPS = ['Navigation', 'Hero', 'Galleries', 'Content & services', 'Social proof', 'Offers & pricing', 'Forms & booking', 'FAQ', 'Calls to action', 'Confirmation', 'Footer'] as const;

export function designGroup(block: Pick<DesignBlock, 'name' | 'key' | 'category'>): string {
    const text = `${block.key} ${block.name}`.toLowerCase();
    if (block.category === 'gallery' || /gallery/.test(text)) return 'Galleries';
    if (/footer/.test(text)) return 'Footer';
    if (/navbar|navigation|header/.test(text)) return 'Navigation';
    if (/hero|video sales letter/.test(text) || block.category === 'hero') return 'Hero';
    if (/faq|frequently/.test(text)) return 'FAQ';
    if (/confirmation|thank.you/.test(text)) return 'Confirmation';
    if (/call.to.action|closing|cta|scarcity/.test(text)) return 'Calls to action';
    if (/form|contact|booking|calendar|opt.in|lead.magnet/.test(text) || block.category === 'scheduling') return 'Forms & booking';
    if (/pricing|engagement|checkout|product|offer|upsell|downsell|bonus/.test(text) || block.category === 'commerce') return 'Offers & pricing';
    if (/testimonial|quote|social.proof|logo.cloud/.test(text) || block.category === 'social-proof') return 'Social proof';
    return 'Content & services';
}

export function designCollection(block: Pick<DesignBlock, 'name'> & Partial<Pick<DesignBlock, 'category'>>): string {
    if (block.category === 'gallery') return 'Galleries';
    if (block.category === 'scaleforge') return 'ScaleForge';
    return block.name.split(/ · | \/ /).length > 1 ? (block.name.split(/ · | \/ /)[0] || 'Essentials') : 'Essentials';
}
