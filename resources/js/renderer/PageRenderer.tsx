/**
 * The shared page renderer.
 *
 * The same component paints the builder canvas (`mode="editable"`) and the
 * public page (`mode="static"`), which is the whole point: what you see while
 * editing is the thing that ships. It imports nothing from the builder or the
 * dashboard — only React, lucide icons and its own document model.
 */

import {
    Fragment,
    useEffect,
    useMemo,
    useRef,
    type CSSProperties,
    type KeyboardEvent as ReactKeyboardEvent,
    type MouseEvent as ReactMouseEvent,
    type ReactNode,
    type RefObject,
} from 'react';
import { googleFontsImport } from './fonts';
import { METROPOLIS_FONTS } from './metropolisFonts';
import { SCALEFORGE_FONTS } from './scaleforgeFonts';
import { SPAZIO_FONTS } from './spazioFonts';
import { EDEFY_CSS, EdefyVideo } from './edefy';
import { NURSING_CARE_CSS } from './nursingCare';
import { YORK_CITY_CSS, YorkAnnouncement } from './yorkCity';
import { FORMA_BUILD_CSS, FormaFilm, useFormaMotion } from './formaBuild';
import { AURELLE_APARTMENTS_CSS, useAurelleMotion } from './aurelleApartments';
import { CANDID_CSS } from './candid';
import { VISTAHAVEN_CSS } from './vistahaven';
import { SOLENNE_CSS } from './solenne';
import { FLUENT_CSS } from './fluent365';
import { PEBBLEWOOD_CSS } from './pebblewood';
import { LUMIKIN_CSS } from './lumikin';
import { MERIDIAN_CSS } from './meridian';
import { KAIRO_CSS, useKairoMotion } from './kairo';
import { LARKSPUR_CSS, useLarkspurMotion } from './larkspur';
import { VERTEX_CSS, useVertexMotion } from './vertex';
import { SABLE_CSS, useSableMotion } from './sable';
import { columnExtra, nodeCss, valueAt } from './style';
import { responsivePropReset } from './responsiveProps';
import { componentFor, INLINE_EDITABLE } from './registry';
import {
    isContainerBlock,
    type BlockData,
    type BlockType,
    type Breakpoint,
    type ColumnData,
    type NodeRef,
    type PageDocument,
    type RenderMode,
    type RowData,
    type SectionData,
    type StyleData,
} from './types';

/* -------------------------------------------------------------------------- */
/* Structural CSS — the only rules not derived from the document itself        */
/* -------------------------------------------------------------------------- */

/*
 * Every node publishes its styles as `--fnl-*` custom properties, and block
 * components read some of them to paint an inner element (a button's own
 * background and padding, a card's text colour). Custom properties inherit, so
 * without the reset on `.fnl-block` a button with no padding of its own picked
 * up its section's 80px padding and ballooned. Each block starts from nothing;
 * its own rule, emitted later with equal specificity, sets what it declares.
 * `--fnl-text-align` is deliberately not reset: alignment inheriting from the
 * section matches how CSS text-align already behaves.
 */
const BASE_CSS = `
.fnl-page{width:100%;margin-inline:auto}
/* Site theme settings (--site-*, Appearance → Theme settings). Zero-specificity :where() rules, so
   anything set on the page, a section or a block wins and only unstyled content follows the theme. */
:where(.fnl-page){background:var(--site-background,transparent);color:var(--site-body,inherit);font-family:var(--site-body-font,inherit);font-size:var(--site-font-size,inherit);line-height:var(--site-line-height,inherit)}
:where(.fnl-block[data-fnl-type="heading"]){font-family:var(--site-heading-font,inherit);font-weight:var(--site-heading-weight,700);letter-spacing:var(--site-heading-tracking,normal);line-height:var(--site-heading-line-height,1.15);color:var(--site-heading,inherit)}
:where(.fnl-block[data-fnl-type="paragraph"] a,.fnl-block[data-fnl-type="html"] a){color:var(--site-link,inherit);text-decoration:var(--site-link-decoration,underline);text-underline-offset:var(--site-link-underline-offset,3px)}
:where(.fnl-block[data-fnl-type="paragraph"] a:hover,.fnl-block[data-fnl-type="html"] a:hover){color:var(--site-link-hover,var(--site-link,inherit))}
:where(.fnl-block[data-fnl-type="button"] a,.fnl-block[data-fnl-type="button"] button){transition:transform .2s ease,box-shadow .2s ease}
:where(.fnl-block[data-fnl-type="button"] a:hover,.fnl-block[data-fnl-type="button"] button:hover){transform:var(--site-button-hover-transform,none);box-shadow:var(--site-button-hover-shadow,none)}
:where(.fnl-block[data-fnl-type="feature_cards"],.fnl-block[data-fnl-type="testimonial"],.fnl-block[data-fnl-type="pricing_table"]) :where(article,li,figure,blockquote){border-radius:var(--site-card-radius,inherit)}
.fnl-page,.fnl-page *,.fnl-page *::before,.fnl-page *::after{box-sizing:border-box}
/* Section content limits must not inherit the body maximum width. */
:where(.fnl-section){position:relative;width:100%;--fnl-max-width:initial}
.fnl-section[data-width="boxed"]{max-width:var(--fnl-content-width,1140px);margin-inline:auto}
.fnl-section[data-width="narrow"]{max-width:720px;margin-inline:auto}
.fnl-section-inner{position:relative;z-index:1;margin-inline:auto;width:100%;max-width:var(--fnl-content-width,1140px)}
.fnl-section[data-width="full"]>.fnl-section-inner{max-width:none}
.fnl-page[data-fnl-full-width="true"]>.fnl-section[data-width="full"]{width:100%!important;max-width:100%!important;margin-inline:0!important}
.fnl-section[data-width="narrow"]>.fnl-section-inner{max-width:720px}
.fnl-section[data-sticky="true"]{position:sticky;top:0;z-index:20}
/* Beats the rule that lifts a node's children above its background layers: the
   video *is* a background layer, so it stays under the content and under the
   overlay, which is what tints it. */
.fnl-section>.fnl-background-video,.fnl-row>.fnl-background-video,.fnl-column>.fnl-background-video,.fnl-section>.fnl-background-still,.fnl-row>.fnl-background-still,.fnl-column>.fnl-background-still{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;pointer-events:none;border:0;opacity:var(--fnl-background-image-opacity,1);filter:brightness(var(--fnl-background-image-brightness,1)) contrast(var(--fnl-background-image-contrast,1)) saturate(var(--fnl-background-image-saturation,1)) blur(var(--fnl-background-image-blur,0px))}
.fnl-row>.fnl-background-video,.fnl-column>.fnl-background-video,.fnl-row>.fnl-background-still,.fnl-column>.fnl-background-still{border-radius:inherit}
.fnl-background-still{display:none}
@media (prefers-reduced-motion:reduce){.fnl-background-video{display:none}.fnl-background-still{display:block}}
[data-fnl-background-image="true"],[data-fnl-background-video="true"],[data-fnl-overlay="true"]{isolation:isolate;overflow:hidden}
.fnl-section:has(nav details){overflow:visible;position:relative;z-index:30}
[data-fnl-background-image="true"]{background-image:none!important}
[data-fnl-background-image="true"]::before{content:"";position:absolute;z-index:0;inset:0;background-image:var(--fnl-background-image);background-size:var(--fnl-background-size,cover);background-position:var(--fnl-background-position,center);background-repeat:var(--fnl-background-repeat,no-repeat);background-attachment:var(--fnl-background-attachment,scroll);opacity:var(--fnl-background-image-opacity,1);filter:brightness(var(--fnl-background-image-brightness,1)) contrast(var(--fnl-background-image-contrast,1)) saturate(var(--fnl-background-image-saturation,1)) blur(var(--fnl-background-image-blur,0px));pointer-events:none}
[data-fnl-overlay="true"]::after{content:"";position:absolute;z-index:0;inset:0;background:var(--fnl-overlay-color,transparent);background-image:var(--fnl-overlay-image);background-size:var(--fnl-overlay-image-size,cover);background-position:var(--fnl-overlay-image-position,center);background-repeat:no-repeat;opacity:var(--fnl-overlay-image-opacity,1);mix-blend-mode:var(--fnl-overlay-blend-mode,normal);pointer-events:none}
[data-fnl-background-image="true"]>* ,[data-fnl-background-video="true"]>*,[data-fnl-overlay="true"]>*{position:relative;z-index:1}
.fnl-row{margin-inline:auto;position:relative;display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:24px;width:100%}
.fnl-column{max-width:100%;position:relative;display:flex;flex-direction:column;gap:16px;min-width:0}
:where(.fnl-block){position:relative;min-width:0;--fnl-padding:initial;--fnl-background-color:initial;--fnl-background-image:initial;--fnl-color:initial;--fnl-border:initial;--fnl-border-top:initial;--fnl-border-right:initial;--fnl-border-bottom:initial;--fnl-border-left:initial;--fnl-border-radius:initial;--fnl-box-shadow:initial;--fnl-gap:initial;--fnl-height:initial}
.fnl-block[data-fnl-raw]{display:block}
.fnl-page img{max-width:100%}
.fnl-page [data-fnl-effect~="lift"]{transition:transform .45s cubic-bezier(.2,.7,.2,1),box-shadow .45s cubic-bezier(.2,.7,.2,1)}
.fnl-page [data-fnl-effect~="lift"]:hover{transform:translateY(-8px);box-shadow:0 30px 60px -32px rgba(15,23,42,.45)}
.fnl-page [data-fnl-effect~="zoom"]{overflow:hidden}
.fnl-page [data-fnl-effect~="zoom"] img{transition:transform 1.2s cubic-bezier(.2,.7,.2,1)}
.fnl-page [data-fnl-effect~="zoom"]:hover img{transform:scale(1.07)}
.fnl-page [data-fnl-effect~="press"] :is(a,button){transition:transform .25s cubic-bezier(.2,.7,.2,1),filter .25s,box-shadow .25s}
.fnl-page [data-fnl-effect~="press"] :is(a,button):hover{transform:translateY(-3px);filter:brightness(1.07);box-shadow:0 16px 30px -18px rgba(0,0,0,.55)}
.fnl-page [data-fnl-effect~="glow"]{transition:box-shadow .35s cubic-bezier(.16,1,.3,1),transform .35s cubic-bezier(.16,1,.3,1)}
.fnl-page [data-fnl-effect~="glow"]:hover{box-shadow:0 0 0 2px color-mix(in srgb,var(--site-primary,#4f46e5) 45%,transparent),0 20px 40px -16px color-mix(in srgb,var(--site-primary,#4f46e5) 32%,transparent)}
.fnl-page [data-fnl-effect~="tilt"]{transition:transform .4s cubic-bezier(.16,1,.3,1)}
.fnl-page [data-fnl-effect~="tilt"]:hover{transform:perspective(900px) rotateX(2deg) rotateY(-2deg) translateY(-4px)}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="float"]{animation:fnl-float 7s ease-in-out infinite}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="pulse-slow"]{animation:fnl-pulse-slow 4s ease-in-out infinite}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="kenburns"]::before{animation:fnl-kenburns 26s ease-in-out infinite alternate}
.fnl-page [data-fnl-effect~="marquee"]{overflow:hidden}
.fnl-page [data-fnl-effect~="marquee"]>*{display:inline-block;width:max-content;max-width:none;white-space:nowrap}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="marquee"]>*{animation:fnl-marquee 42s linear infinite}
/* shadcn/ui surface variants */
.fnl-page [data-fnl-shadcn="card"]{background:var(--site-surface,#ffffff);border:1px solid color-mix(in srgb,var(--site-heading,#0f172a) 10%,#e2e8f0);border-radius:12px;box-shadow:0 1px 3px 0 rgba(15,23,42,.04),0 1px 2px -1px rgba(15,23,42,.04);padding:var(--fnl-padding,24px)}
.fnl-page [data-fnl-shadcn="muted"]{background:color-mix(in srgb,var(--site-surface,#f8fafc) 92%,#f1f5f9);border:1px solid color-mix(in srgb,var(--site-heading,#0f172a) 8%,#e2e8f0);border-radius:12px;padding:var(--fnl-padding,24px)}
.fnl-page [data-fnl-shadcn="glass"]{background:rgba(255,255,255,.72);backdrop-filter:blur(14px) saturate(160%);-webkit-backdrop-filter:blur(14px) saturate(160%);border:1px solid rgba(255,255,255,.55);border-radius:16px;box-shadow:0 12px 32px -12px rgba(15,23,42,.12);padding:var(--fnl-padding,24px)}
.fnl-page [data-fnl-shadcn="outline"]{background:transparent;border:1px solid color-mix(in srgb,var(--site-heading,#0f172a) 14%,#cbd5e1);border-radius:12px;padding:var(--fnl-padding,20px)}
.fnl-page [data-fnl-shadcn="accent"]{background:color-mix(in srgb,var(--site-primary,#4f46e5) 7%,#ffffff);border:1px solid color-mix(in srgb,var(--site-primary,#4f46e5) 22%,#e2e8f0);border-radius:12px;padding:var(--fnl-padding,20px)}
.fnl-page [data-fnl-shadcn="dark"]{background:#09090b;color:#fafafa;border:1px solid #27272a;border-radius:12px;box-shadow:0 10px 25px -10px rgba(0,0,0,.5);padding:var(--fnl-padding,24px)}
/* Virtualized offscreen section rendering for large pages */
.fnl-section:not([data-selected="true"]):not(:first-of-type){content-visibility:auto;contain-intrinsic-size:auto 420px}
@keyframes fnl-float{50%{transform:translateY(-12px)}}
@keyframes fnl-pulse-slow{50%{opacity:.82;transform:scale(.99)}}
@keyframes fnl-kenburns{from{transform:scale(1)}to{transform:scale(1.09)}}
@keyframes fnl-marquee{to{transform:translateX(-50%)}}
@keyframes fnl-reveal{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:none}}
@media (prefers-reduced-motion:reduce){.fnl-page [data-fnl-effect],.fnl-page [data-fnl-effect]>*,.fnl-page [data-fnl-effect]::before,.fnl-page [data-fnl-effect] img{animation:none!important;transition:none!important}}
.fnl-nested-columns>*{min-width:0}
.fnl-page[data-fnl-breakpoint="mobile"] .fnl-nested-columns:not([data-stack="none"]),.fnl-page[data-fnl-breakpoint="tablet"] .fnl-nested-columns[data-stack="tablet"]{grid-template-columns:minmax(0,1fr)!important}
@media(max-width:1024px){.fnl-page[data-fnl-breakpoint="viewport"] .fnl-nested-columns[data-stack="tablet"]{grid-template-columns:minmax(0,1fr)!important}}
@media(max-width:640px){.fnl-page[data-fnl-breakpoint="viewport"] .fnl-nested-columns:not([data-stack="none"]){grid-template-columns:minmax(0,1fr)!important}}
@media(max-width:767px){.fnl-carousel-track[data-mobile-columns="1"]{grid-auto-columns:100%!important}}
`.trim();

const EDITABLE_CSS = `
.fnl-editable [data-fnl-node]{outline:1px dashed transparent;outline-offset:-1px;transition:outline-color 120ms}
.fnl-editable [data-fnl-node]:hover{outline-color:color-mix(in srgb,var(--color-primary,#4f46e5) 55%,transparent)}
.fnl-editable [data-fnl-node][data-selected="true"]{outline:2px solid var(--color-primary,#4f46e5);outline-offset:-2px}
.fnl-editable [data-fnl-node]:focus-visible{outline:2px solid var(--color-ring,#4f46e5);outline-offset:2px}
.fnl-editable [data-fnl-inline]{cursor:text;border-radius:3px}
.fnl-editable [data-fnl-inline]:hover{box-shadow:0 0 0 1px color-mix(in srgb,var(--color-primary,#4f46e5) 35%,transparent)}
.fnl-editable [data-fnl-inline]:focus{box-shadow:0 0 0 2px color-mix(in srgb,var(--color-primary,#4f46e5) 55%,transparent)}
.fnl-editable [data-fnl-inline]:empty::before{content:attr(data-fnl-placeholder);opacity:.45;pointer-events:none}
.fnl-editable .fnl-column:empty::after,.fnl-editable .fnl-column[data-empty="true"]::after{content:"Drop a block here";display:flex;align-items:center;justify-content:center;min-height:72px;border:1px dashed var(--color-border-strong,#cbd5e1);border-radius:8px;font-size:12px;color:var(--color-text-muted,#64748b)}
`.trim();

/* -------------------------------------------------------------------------- */
/* Public API                                                                  */
/* -------------------------------------------------------------------------- */

export interface RendererSlots {
    /**
     * Lets the builder wrap any node — drop zones, drag handles, toolbars —
     * without the renderer knowing what dnd-kit is.
     */
    wrapNode?: (ref: NodeRef, element: ReactNode) => ReactNode;
    /** Rendered at the end of every column in editable mode. */
    columnAppend?: (column: ColumnData) => ReactNode;
    /** Builder controls following a row's columns; absent on published pages. */
    rowAppend?: (row: RowData) => ReactNode;
    /** Rendered at the end of every stacking `container` block in editable mode. */
    blockAppend?: (block: BlockData) => ReactNode;
    /**
     * Rendered immediately before each section in editable mode. This is where
     * the builder puts the drop line that lets a whole ready-made section land
     * between two existing ones rather than only at the bottom.
     */
    sectionBefore?: (section: SectionData) => ReactNode;
    /** Rendered after the last section in editable mode. */
    pageAppend?: () => ReactNode;
}

export interface PageRendererProps {
    document: PageDocument;
    mode?: RenderMode;
    /**
     * Pin rendering to one breakpoint. The builder canvas sizes itself rather
     * than the viewport, so media queries would report the wrong tier; passing
     * a breakpoint flattens every rule to that tier instead.
     */
    breakpoint?: Breakpoint;
    selected?: NodeRef | null;
    onSelect?: (ref: NodeRef) => void;
    /** Inline text editing on the canvas writes back through this. */
    onBlockPropsChange?: (blockId: string, patch: Record<string, unknown>) => void;
    slots?: RendererSlots;
    className?: string;
}

interface RenderContext {
    mode: RenderMode;
    breakpoint: Breakpoint | undefined;
    selected: NodeRef | null;
    onSelect: ((ref: NodeRef) => void) | undefined;
    onBlockPropsChange: ((blockId: string, patch: Record<string, unknown>) => void) | undefined;
    slots: RendererSlots;
}

/* -------------------------------------------------------------------------- */
/* CSS collection                                                              */
/* -------------------------------------------------------------------------- */

/**
 * Blocks whose visible surface is an inner element rather than the block's box.
 *
 * For these the surface styles are published as custom properties only, and
 * the block component paints them on the element people actually see. Without
 * this a button's "background" painted a coloured band behind the button while
 * the button itself stayed the theme colour.
 */
const BUTTON_SURFACE: ReadonlySet<string> = new Set([
    'background-color',
    'background-image',
    'color',
    'border',
    'border-top',
    'border-right',
    'border-bottom',
    'border-left',
    'border-radius',
    'box-shadow',
    'padding',
]);

const SURFACE_VARS_ONLY: Partial<Record<BlockType, ReadonlySet<string>>> = {
    // Height belongs to the photograph, not the figure plus its caption.
    image: new Set(['height']),
    button: BUTTON_SURFACE,
    popup_trigger: BUTTON_SURFACE,
    // The countdown's tiles are its surface: background and text colour paint
    // the tiles rather than a slab behind all four.
    countdown: new Set(['background-color', 'color']),
    // A progress bar's background colour is its fill colour.
    progress_bar: new Set(['background-color']),
};

function blockCss(block: BlockData, only: Breakpoint | undefined, out: string[]): void {
    const varsOnly = SURFACE_VARS_ONLY[block.type];
    out.push(
        nodeCss(
            {
                id: block.id,
                style: block.style ?? null,
                visibility: block.visibility ?? null,
                extra: responsivePropReset(block.type, block.style),
                ...(varsOnly ? { varsOnly } : {}),
            },
            only,
        ),
    );
    for (const child of block.children ?? []) {
        blockCss(child, only, out);
    }
}

export function documentCss(document: PageDocument, only?: Breakpoint): string {
    const out: string[] = [];

    out.push(
        nodeCss(
            { id: 'page-body', style: document.settings.body ?? null, visibility: null },
            only,
        ).replace('[data-fnl-id="page-body"]', '.fnl-page'),
    );

    for (const section of document.sections) {
        out.push(nodeCss({ id: section.id, style: section.style ?? null, visibility: section.visibility ?? null }, only));
        for (const row of section.rows) {
            out.push(nodeCss({ id: row.id, style: row.style ?? null, visibility: row.visibility ?? null }, only));
            for (const column of row.columns) {
                out.push(
                    nodeCss(
                        {
                            id: column.id,
                            style: column.style ?? null,
                            visibility: column.visibility ?? null,
                            extra: columnExtra(column.span, row.stackBelow),
                        },
                        only,
                    ),
                );
                for (const block of column.blocks) {
                    blockCss(block, only, out);
                }
            }
        }
    }

    return out.filter(Boolean).join('\n');
}

/* -------------------------------------------------------------------------- */
/* Node rendering                                                              */
/* -------------------------------------------------------------------------- */

function nodeAttributes(
    context: RenderContext,
    ref: NodeRef,
): Record<string, unknown> {
    if (context.mode !== 'editable') {
        return { 'data-fnl-id': ref.id };
    }

    const selected = context.selected?.kind === ref.kind && context.selected.id === ref.id;

    return {
        'data-fnl-id': ref.id,
        'data-fnl-node': ref.kind,
        'data-selected': selected ? 'true' : 'false',
        tabIndex: 0,
        role: 'button',
        'aria-pressed': selected,
        'aria-label': `${ref.kind} ${ref.id}`,
        onClick: (event: ReactMouseEvent) => {
            event.stopPropagation();
            context.onSelect?.(ref);
        },
        onKeyDown: (event: ReactKeyboardEvent) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                event.stopPropagation();
                context.onSelect?.(ref);
            }
        },
    };
}

function wrap(context: RenderContext, ref: NodeRef, element: ReactNode): ReactNode {
    return context.slots.wrapNode ? context.slots.wrapNode(ref, element) : element;
}

/** Marks the node that owns an adjustable background, overlay, motion effect, or shadcn/ui surface. */
function backgroundLayerAttributes(style: StyleData | null | undefined, breakpoint?: Breakpoint): Record<string, string> {
    const hasBackgroundImage = style?.backgroundImage !== undefined && style.backgroundImage !== null;
    const hasBackgroundVideo = style?.backgroundVideo !== undefined && style.backgroundVideo !== null;
    const hasOverlay = (style?.overlayColor !== undefined && style.overlayColor !== null)
        || (style?.overlayImage !== undefined && style.overlayImage !== null);
    const effect = effectOf(style);
    const shadcn = shadcnSurfaceOf(style, breakpoint);
    return {
        'data-fnl-background-image': hasBackgroundImage ? 'true' : 'false',
        'data-fnl-background-video': hasBackgroundVideo ? 'true' : 'false',
        'data-fnl-overlay': hasOverlay ? 'true' : 'false',
        ...(effect ? { 'data-fnl-effect': effect } : {}),
        ...(shadcn ? { 'data-fnl-shadcn': shadcn } : {}),
    };
}

/**
 * Resolves Tailwind CSS v4 utility classes stored in `style.custom['--fnl-tw']`.
 */
export function tailwindClassesOf(style: StyleData | null | undefined, breakpoint?: Breakpoint): string {
    const raw = style?.custom?.['--fnl-tw'];
    const resolved = valueAt(raw, breakpoint ?? 'desktop');
    if (typeof resolved !== 'string' || resolved.trim() === '') {
        return '';
    }
    return resolved
        .trim()
        .split(/\s+/)
        .filter((token) => /^[a-zA-Z0-9_:./[\]()%#!-]+$/.test(token))
        .join(' ');
}

function shadcnSurfaceOf(style: StyleData | null | undefined, breakpoint?: Breakpoint): string | null {
    const raw = style?.custom?.['--fnl-shadcn'];
    const resolved = valueAt(raw, breakpoint ?? 'desktop');
    if (typeof resolved !== 'string') {
        return null;
    }
    return /^(card|muted|glass|outline|accent|dark)$/.test(resolved.trim()) ? resolved.trim() : null;
}

/**
 * A background video for a section, row or column.
 *
 * It sits behind the node's content and in front of nothing else: the
 * background colour, image and overlay all still apply, and the background
 * adjustments — visibility, brightness, contrast, saturation, blur — apply to
 * it exactly as they do to a background photograph, because a video is an
 * alternative to that image rather than a separate layer. It is muted, looped
 * and inert — a background, not a player — and the poster frame is what a
 * visitor sees before it loads, on a metered connection, when the file cannot
 * play, and whenever they have asked for reduced motion.
 *
 * The builder canvas shows the poster rather than a playing video, so editing
 * a page is not a moving target.
 */
function BackgroundVideo({ style, breakpoint, editable }: { style: StyleData | null | undefined; breakpoint: Breakpoint | undefined; editable: boolean }) {
    const tier = breakpoint ?? 'desktop';
    const source = asUrl(valueAt(style?.backgroundVideo, tier));
    const poster = asUrl(valueAt(style?.backgroundVideoPoster, tier));

    if (source === null) {
        return null;
    }

    return (
        <>
            <video
                className="fnl-background-video"
                src={source}
                {...(poster !== null ? { poster } : {})}
                autoPlay={!editable}
                muted
                loop
                playsInline
                preload={editable ? 'metadata' : 'auto'}
                tabIndex={-1}
                aria-hidden="true"
            />
            {poster !== null && <img className="fnl-background-still" src={poster} alt="" aria-hidden="true" />}
        </>
    );
}

/**
 * A path, or an http(s) URL — a document can name a file on this site or on a
 * CDN, and nothing else, so a stored style can never point a <video> at a
 * `javascript:` or `data:` source. Local uploads come back as a full
 * `http://127.0.0.1:8001/storage/…` URL in development, so plain http has to
 * be allowed or an uploaded video would silently fail to appear.
 */
function asUrl(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null;
    }
    const trimmed = value.trim().replace(/^url\((['"]?)(.*)\1\)$/i, '$2');

    return trimmed.startsWith('/') || /^https?:\/\//i.test(trimmed) ? trimmed : null;
}

const VALID_EFFECTS = /^(reveal|fade-in|slide-left|slide-right|zoom-in|blur-in|flip-up|spring-pop|exit-fade|exit-up|exit-scale|exit-blur|lift|zoom|press|glow|tilt|float|pulse-slow|kenburns|marquee)$/;
const ENTRANCE_EFFECTS = ['reveal', 'fade-in', 'slide-left', 'slide-right', 'zoom-in', 'blur-in', 'flip-up', 'spring-pop'] as const;
const EXIT_EFFECTS = ['exit-fade', 'exit-up', 'exit-scale', 'exit-blur'] as const;

/**
 * Motion a node asks for through its `--fnl-effect` custom style: entrance presets
 * (`reveal`, `fade-in`, `slide-left`, `slide-right`, `zoom-in`, `blur-in`, `flip-up`,
 * `spring-pop`) and hover/loop effects (`lift`, `zoom`, `press`, `glow`, `tilt`,
 * `float`, `pulse-slow`, `kenburns`, `marquee`), space-separated.
 */
function effectOf(style: StyleData | null | undefined): string | null {
    const raw: unknown = (style?.custom as Record<string, unknown> | undefined)?.['--fnl-effect'];
    const value = typeof raw === 'object' && raw !== null ? (raw as Record<string, unknown>)['desktop'] : raw;
    if (typeof value !== 'string') {
        return null;
    }
    const effects = value.split(/\s+/).filter((name) => VALID_EFFECTS.test(name));
    return effects.length > 0 ? effects.join(' ') : null;
}

function keyframesForEntrance(effect: string): Keyframe[] {
    switch (effect) {
        case 'fade-in':
            return [{ opacity: 0 }, { opacity: 1 }];
        case 'slide-left':
            return [
                { opacity: 0, transform: 'translate3d(-36px, 0, 0)' },
                { opacity: 1, transform: 'translate3d(0, 0, 0)' },
            ];
        case 'slide-right':
            return [
                { opacity: 0, transform: 'translate3d(36px, 0, 0)' },
                { opacity: 1, transform: 'translate3d(0, 0, 0)' },
            ];
        case 'zoom-in':
            return [
                { opacity: 0, transform: 'scale(0.88)' },
                { opacity: 1, transform: 'scale(1)' },
            ];
        case 'blur-in':
            return [
                { opacity: 0, filter: 'blur(12px)', transform: 'translate3d(0, 16px, 0)' },
                { opacity: 1, filter: 'blur(0px)', transform: 'translate3d(0, 0, 0)' },
            ];
        case 'flip-up':
            return [
                { opacity: 0, transform: 'perspective(900px) rotateX(22deg) translate3d(0, 28px, 0)' },
                { opacity: 1, transform: 'perspective(900px) rotateX(0deg) translate3d(0, 0, 0)' },
            ];
        case 'spring-pop':
            return [
                { opacity: 0, transform: 'scale(0.8) translate3d(0, 20px, 0)' },
                { opacity: 1, transform: 'scale(1.03) translate3d(0, -3px, 0)', offset: 0.72 },
                { opacity: 1, transform: 'scale(1) translate3d(0, 0, 0)' },
            ];
        case 'reveal':
        default:
            return [
                { opacity: 0, transform: 'translate3d(0, 32px, 0)' },
                { opacity: 1, transform: 'translate3d(0, 0, 0)' },
            ];
    }
}

function keyframesForExit(effect: string): Keyframe[] {
    switch (effect) {
        case 'exit-up':
            return [
                { opacity: 1, transform: 'translate3d(0, 0, 0)' },
                { opacity: 0.15, transform: 'translate3d(0, -24px, 0)' },
            ];
        case 'exit-scale':
            return [
                { opacity: 1, transform: 'scale(1)' },
                { opacity: 0.18, transform: 'scale(0.94)' },
            ];
        case 'exit-blur':
            return [
                { opacity: 1, filter: 'blur(0px)' },
                { opacity: 0.2, filter: 'blur(8px)' },
            ];
        case 'exit-fade':
        default:
            return [{ opacity: 1 }, { opacity: 0.18 }];
    }
}

function playNodeMotion(element: HTMLElement, phase: 'entrance' | 'exit' = 'entrance'): void {
    if (typeof element.animate !== 'function') {
        return;
    }
    const effects = (element.getAttribute('data-fnl-effect') ?? '').split(/\s+/).filter(Boolean);
    const computed = window.getComputedStyle(element);
    const rawDuration = parseFloat(computed.getPropertyValue('--fnl-anim-duration') || '650');
    const rawDelay = parseFloat(computed.getPropertyValue('--fnl-anim-delay') || '0');
    const rawEasing = computed.getPropertyValue('--fnl-anim-easing').trim() || 'cubic-bezier(0.16, 1, 0.3, 1)';
    const duration = Number.isFinite(rawDuration) && rawDuration > 0 ? Math.min(rawDuration, 4000) : 650;
    const delay = Number.isFinite(rawDelay) && rawDelay >= 0 ? Math.min(rawDelay, 3000) : 0;

    if (phase === 'exit') {
        const exitEffect = effects.find((name) => (EXIT_EFFECTS as readonly string[]).includes(name)) ?? 'exit-fade';
        element.animate(keyframesForExit(exitEffect), {
            duration: Math.max(280, Math.round(duration * 0.75)),
            easing: rawEasing,
            fill: 'forwards',
        });
        return;
    }

    const entrance = effects.find((name) => (ENTRANCE_EFFECTS as readonly string[]).includes(name)) ?? 'reveal';
    element.animate(keyframesForEntrance(entrance), {
        duration,
        delay,
        easing: rawEasing,
        fill: 'both',
    });
}

function useBuilderMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
    useEffect(() => {
        const page = root.current;
        if (!page) {
            return;
        }

        const onReplay = (event: Event): void => {
            const detail = (event as CustomEvent<{ nodeId?: string; phase?: 'entrance' | 'exit' }>).detail;
            const target = detail?.nodeId
                ? page.querySelector<HTMLElement>(`[data-fnl-id="${detail.nodeId}"]`)
                : page.querySelector<HTMLElement>('[data-selected="true"]');
            if (target) {
                playNodeMotion(target, detail?.phase ?? 'entrance');
                if (detail?.phase === 'exit') {
                    window.setTimeout(() => playNodeMotion(target, 'entrance'), 650);
                }
            }
        };

        window.addEventListener('fnl:replay-motion', onReplay);

        if (editable || typeof window === 'undefined' || typeof IntersectionObserver === 'undefined') {
            return () => window.removeEventListener('fnl:replay-motion', onReplay);
        }

        if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
            return () => window.removeEventListener('fnl:replay-motion', onReplay);
        }

        const allMotionEffects = [...ENTRANCE_EFFECTS, ...EXIT_EFFECTS];
        const selector = allMotionEffects.map((name) => `[data-fnl-effect~="${name}"]`).join(', ');
        const nodes = Array.from(page.querySelectorAll<HTMLElement>(selector));
        if (nodes.length === 0) {
            return () => window.removeEventListener('fnl:replay-motion', onReplay);
        }

        const seen = new WeakSet<Element>();
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    const el = entry.target as HTMLElement;
                    const effects = (el.getAttribute('data-fnl-effect') ?? '').split(/\s+/);
                    const hasExit = effects.some((e) => (EXIT_EFFECTS as readonly string[]).includes(e));

                    if (entry.isIntersecting) {
                        seen.add(el);
                        playNodeMotion(el, 'entrance');
                        if (!hasExit) {
                            observer.unobserve(el);
                        }
                    } else if (hasExit && seen.has(el)) {
                        playNodeMotion(el, 'exit');
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -24px 0px' },
        );

        nodes.forEach((node) => observer.observe(node));

        return () => {
            observer.disconnect();
            window.removeEventListener('fnl:replay-motion', onReplay);
        };
    }, [root, editable, revision]);
}

function RenderBlock({ block, context }: { block: BlockData; context: RenderContext }): ReactNode {
    const Component = componentFor(block.type);
    const ref: NodeRef = { kind: 'block', id: block.id };

    const children = isContainerBlock(block.type)
        ? (block.children ?? []).map((child) => (
              <RenderBlock key={child.id} block={child} context={context} />
          ))
        : undefined;

    const editable = context.mode === 'editable' && INLINE_EDITABLE.has(block.type) && context.onBlockPropsChange;
    const append =
        context.mode === 'editable' && block.type === 'container' ? context.slots.blockAppend?.(block) : undefined;
    const tw = tailwindClassesOf(block.style, context.breakpoint);

    const element = (
        <div className={['fnl-block', tw].filter(Boolean).join(' ')} data-fnl-type={block.type} data-aurelle-role={typeof block.props.aurelleRole === 'string' ? block.props.aurelleRole : undefined} data-forma-role={typeof block.props.formaRole === 'string' ? block.props.formaRole : undefined} data-york-role={typeof block.props.yorkRole === 'string' ? block.props.yorkRole : undefined} data-meridian-role={typeof block.props.meridianRole === 'string' ? block.props.meridianRole : undefined} data-kairo-role={typeof block.props.kairoRole === 'string' ? block.props.kairoRole : undefined} data-larkspur-role={typeof block.props.larkspurRole === 'string' ? block.props.larkspurRole : undefined} data-vertex-role={typeof block.props.vertexRole === 'string' ? block.props.vertexRole : undefined} data-vertex-filter={typeof block.props.vertexFilter === 'string' ? block.props.vertexFilter : undefined} data-vertex-module-cat={typeof block.props.vertexModuleCat === 'string' ? block.props.vertexModuleCat : undefined} data-vertex-copy={typeof block.props.vertexCopy === 'string' ? block.props.vertexCopy : undefined} data-sable-role={typeof block.props.sableRole === 'string' ? block.props.sableRole : undefined} style={{ maxWidth: 'min(100%, var(--fnl-max-width, 100%))', overflowWrap: 'anywhere' }} {...backgroundLayerAttributes(block.style, context.breakpoint)} {...nodeAttributes(context, ref)}>
            {block.props.formaRole === 'film' && context.mode !== 'editable' ? <FormaFilm block={block} /> : block.props.yorkRole === 'announcement' && context.mode !== 'editable' ? <YorkAnnouncement text={String(block.props.text ?? '')} /> : <Component
                block={block}
                mode={context.mode}
                {...(children ? { children } : {})}
                {...(append ? { append } : {})}
                {...(editable
                    ? {
                          onPropsChange: (patch: Record<string, unknown>) =>
                              context.onBlockPropsChange?.(block.id, patch),
                      }
                    : {})}
            />}
        </div>
    );

    return wrap(context, ref, element);
}

function RenderColumn({ column, context }: { column: ColumnData; context: RenderContext }): ReactNode {
    const ref: NodeRef = { kind: 'column', id: column.id };
    const tw = tailwindClassesOf(column.style, context.breakpoint);

    const element = (
        <div
            className={['fnl-column', tw].filter(Boolean).join(' ')}
            data-empty={column.blocks.length === 0 ? 'true' : 'false'}
            {...backgroundLayerAttributes(column.style, context.breakpoint)}
            {...nodeAttributes(context, ref)}
        >
            <BackgroundVideo style={column.style} breakpoint={context.breakpoint} editable={context.mode === 'editable'} />
            {column.blocks.map((block) => (
                <RenderBlock key={block.id} block={block} context={context} />
            ))}
            {context.mode === 'editable' ? context.slots.columnAppend?.(column) : null}
        </div>
    );

    return wrap(context, ref, element);
}

function RenderRow({ row, context }: { row: RowData; context: RenderContext }): ReactNode {
    const ref: NodeRef = { kind: 'row', id: row.id };
    const tw = tailwindClassesOf(row.style, context.breakpoint);

    const element = (
        <div className={['fnl-row', tw].filter(Boolean).join(' ')} data-stack={row.stackBelow ?? 'mobile'} {...backgroundLayerAttributes(row.style, context.breakpoint)} {...nodeAttributes(context, ref)}>
            <BackgroundVideo style={row.style} breakpoint={context.breakpoint} editable={context.mode === 'editable'} />
            {row.columns.map((column) => (
                <RenderColumn key={column.id} column={column} context={context} />
            ))}
            {context.mode === 'editable' ? context.slots.rowAppend?.(row) : null}
        </div>
    );

    return wrap(context, ref, element);
}

function RenderSection({ section, context }: { section: SectionData; context: RenderContext }): ReactNode {
    const ref: NodeRef = { kind: 'section', id: section.id };
    const tw = tailwindClassesOf(section.style, context.breakpoint);
    const element = (
        <section
            id={typeof section.settings?.anchor === 'string' && section.settings.anchor !== '' ? section.settings.anchor : undefined}
            data-fluent={section.settings?.designCollection === 'fluent-365' ? String(section.settings.designBlock ?? '') : (typeof section.settings?.fluent === 'string' && section.settings.fluent !== '' ? section.settings.fluent : undefined)}
            className={['fnl-section', tw].filter(Boolean).join(' ')}
            data-vistahaven={section.settings?.designCollection === 'vistahaven' ? String(section.settings.designBlock ?? '') : undefined}
            data-solenne={section.settings?.designCollection === 'solenne-estates' ? String(section.settings.designBlock ?? '') : undefined}
            data-candid={section.settings?.designCollection === 'candid' ? String(section.settings.designBlock ?? '') : undefined}
            data-nursing-care={section.settings?.designCollection === 'nursing-care' ? String(section.settings.designBlock ?? '') : undefined}
            data-york-city={section.settings?.designCollection === 'york-city' ? String(section.settings.designBlock ?? '') : undefined}
            data-forma-build={section.settings?.designCollection === 'forma-build' ? String(section.settings.designBlock ?? '') : undefined}
            data-aurelle-apartments={section.settings?.designCollection === 'aurelle-apartments' ? String(section.settings.designBlock ?? '') : undefined}
            data-edefy={section.settings?.designCollection === 'edefy' ? String(section.settings.designBlock ?? '') : undefined}
            data-pebblewood={section.settings?.designCollection === 'pebblewood' ? String(section.settings.designBlock ?? '') : undefined}
            data-lumikin={section.settings?.designCollection === 'lumikin' ? String(section.settings.designBlock ?? '') : undefined}
            data-meridian={section.settings?.designCollection === 'meridian' ? String(section.settings.designBlock ?? '') : undefined}
            data-kairo={section.settings?.designCollection === 'kairo' ? String(section.settings.designBlock ?? '') : undefined}
            data-larkspur={section.settings?.designCollection === 'larkspur' ? String(section.settings.designBlock ?? '') : undefined}
            data-vertex={section.settings?.designCollection === 'vertex' ? String(section.settings.designBlock ?? '') : undefined}
            data-sable={section.settings?.designCollection === 'sable' ? String(section.settings.designBlock ?? '') : undefined}
            data-width={section.width}
            style={section.width === 'full' ? { width: '100%', maxWidth: 'none', marginInline: 'auto' } : { marginInline: 'auto' }}
            data-sticky={section.sticky ? 'true' : 'false'}
            {...backgroundLayerAttributes(section.style, context.breakpoint)}
            aria-label={section.name ?? undefined}
            {...nodeAttributes(context, ref)}
        >
            {section.settings?.designCollection === 'edefy' && typeof section.settings.videoUrl === 'string' && <EdefyVideo url={section.settings.videoUrl} editable={context.mode === 'editable'} />}
            <BackgroundVideo style={section.style} breakpoint={context.breakpoint} editable={context.mode === 'editable'} />
            <div className="fnl-section-inner" style={{ maxWidth: 'var(--fnl-max-width, var(--fnl-content-width, 1140px))' }}>
                {section.rows.map((row) => (
                    <RenderRow key={row.id} row={row} context={context} />
                ))}
            </div>
        </section>
    );

    return wrap(context, ref, element);
}

/* -------------------------------------------------------------------------- */

export function PageRenderer({
    document,
    mode = 'static',
    breakpoint,
    selected = null,
    onSelect,
    onBlockPropsChange,
    slots,
    className,
}: PageRendererProps) {
    const css = useMemo(() => documentCss(document, breakpoint), [document, breakpoint]);
    const root = useRef<HTMLDivElement>(null);
    useFormaMotion(root, mode === 'editable', document);
    useAurelleMotion(root, mode === 'editable', document);
    useKairoMotion(root, mode === 'editable', document);
    useLarkspurMotion(root, mode === 'editable', document);
    useVertexMotion(root, mode === 'editable', document);
    useSableMotion(root, mode === 'editable', document);
    useBuilderMotion(root, mode === 'editable', document);

    const context: RenderContext = {
        mode,
        breakpoint,
        selected,
        onSelect,
        onBlockPropsChange,
        slots: slots ?? {},
    };

    const customCss = document.settings.customCss?.replace(/<\/style/gi, '') ?? '';

    return (
        <Fragment>
            <style>{`${googleFontsImport(css)}
${VISTAHAVEN_CSS}
${SOLENNE_CSS}
${METROPOLIS_FONTS}\n${SCALEFORGE_FONTS}\n${SPAZIO_FONTS}\n${BASE_CSS}\n${EDEFY_CSS}\n${NURSING_CARE_CSS}\n${YORK_CITY_CSS}\n${FORMA_BUILD_CSS}\n${AURELLE_APARTMENTS_CSS}\n${CANDID_CSS}\n${FLUENT_CSS}\n${PEBBLEWOOD_CSS}\n${LUMIKIN_CSS}\n${MERIDIAN_CSS}\n${KAIRO_CSS}\n${LARKSPUR_CSS}\n${VERTEX_CSS}\n${SABLE_CSS}\n${mode === 'editable' ? EDITABLE_CSS : ''}\n${css}\n${customCss}`}</style>
            <div
                ref={root}
                className={['fnl-page', mode === 'editable' ? 'fnl-editable' : '', className ?? '']
                    .filter(Boolean)
                    .join(' ')}
                style={{ '--fnl-content-width': document.settings.contentWidth } as CSSProperties}
                data-fnl-breakpoint={breakpoint ?? 'viewport'}
                data-fnl-full-width={document.settings.contentWidth === '100%' ? 'true' : undefined}
            >
                {document.sections.map((section) => (
                    <Fragment key={section.id}>
                        {mode === 'editable' ? context.slots.sectionBefore?.(section) : null}
                        <RenderSection section={section} context={context} />
                    </Fragment>
                ))}
                {mode === 'editable' ? context.slots.pageAppend?.() : null}
            </div>
        </Fragment>
    );
}
