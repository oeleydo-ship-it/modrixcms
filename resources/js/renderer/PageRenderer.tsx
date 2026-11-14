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
    useMemo,
    useRef,
    type CSSProperties,
    type KeyboardEvent as ReactKeyboardEvent,
    type MouseEvent as ReactMouseEvent,
    type ReactNode,
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
.fnl-page{width:100%;color:inherit;margin-inline:auto}
.fnl-page,.fnl-page *,.fnl-page *::before,.fnl-page *::after{box-sizing:border-box}
/* Section content limits must not inherit the body maximum width. */
.fnl-section{position:relative;width:100%;--fnl-max-width:initial}
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
.fnl-block{position:relative;min-width:0;--fnl-padding:initial;--fnl-background-color:initial;--fnl-background-image:initial;--fnl-color:initial;--fnl-border:initial;--fnl-border-top:initial;--fnl-border-right:initial;--fnl-border-bottom:initial;--fnl-border-left:initial;--fnl-border-radius:initial;--fnl-box-shadow:initial;--fnl-gap:initial;--fnl-height:initial}
.fnl-block[data-fnl-raw]{display:block}
.fnl-page img{max-width:100%}
.fnl-page [data-fnl-effect~="lift"]{transition:transform .45s cubic-bezier(.2,.7,.2,1),box-shadow .45s cubic-bezier(.2,.7,.2,1)}
.fnl-page [data-fnl-effect~="lift"]:hover{transform:translateY(-8px);box-shadow:0 30px 60px -32px rgba(15,23,42,.45)}
.fnl-page [data-fnl-effect~="zoom"]{overflow:hidden}
.fnl-page [data-fnl-effect~="zoom"] img{transition:transform 1.2s cubic-bezier(.2,.7,.2,1)}
.fnl-page [data-fnl-effect~="zoom"]:hover img{transform:scale(1.07)}
.fnl-page [data-fnl-effect~="press"] :is(a,button){transition:transform .25s cubic-bezier(.2,.7,.2,1),filter .25s,box-shadow .25s}
.fnl-page [data-fnl-effect~="press"] :is(a,button):hover{transform:translateY(-3px);filter:brightness(1.07);box-shadow:0 16px 30px -18px rgba(0,0,0,.55)}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="float"]{animation:fnl-float 7s ease-in-out infinite}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="kenburns"]::before{animation:fnl-kenburns 26s ease-in-out infinite alternate}
.fnl-page [data-fnl-effect~="marquee"]{overflow:hidden}
.fnl-page [data-fnl-effect~="marquee"]>*{display:inline-block;width:max-content;max-width:none;white-space:nowrap}
.fnl-page:not(.fnl-editable) [data-fnl-effect~="marquee"]>*{animation:fnl-marquee 42s linear infinite}
@supports (animation-timeline:view()){.fnl-page:not(.fnl-editable) [data-fnl-effect~="reveal"]{animation:fnl-reveal linear both;animation-timeline:view();animation-range:entry 0% cover 26%}}
@keyframes fnl-float{50%{transform:translateY(-12px)}}
@keyframes fnl-kenburns{from{transform:scale(1)}to{transform:scale(1.09)}}
@keyframes fnl-marquee{to{transform:translateX(-50%)}}
@keyframes fnl-reveal{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:none}}
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

/** Marks the node that owns an adjustable background or overlay layer. */
function backgroundLayerAttributes(style: StyleData | null | undefined): Record<string, string> {
    const hasBackgroundImage = style?.backgroundImage !== undefined && style.backgroundImage !== null;
    const hasBackgroundVideo = style?.backgroundVideo !== undefined && style.backgroundVideo !== null;
    const hasOverlay = (style?.overlayColor !== undefined && style.overlayColor !== null)
        || (style?.overlayImage !== undefined && style.overlayImage !== null);
    const effect = effectOf(style);
    return {
        'data-fnl-background-image': hasBackgroundImage ? 'true' : 'false',
        'data-fnl-background-video': hasBackgroundVideo ? 'true' : 'false',
        'data-fnl-overlay': hasOverlay ? 'true' : 'false',
        ...(effect ? { 'data-fnl-effect': effect } : {}),
    };
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

/**
 * Motion a node asks for through its `--fnl-effect` custom style: `reveal`,
 * `lift`, `zoom`, `press`, `float`, `kenburns` or `marquee`, space-separated.
 * The CSS lives in BASE_CSS; the builder canvas keeps everything still, and
 * visitors who prefer reduced motion get none of it.
 */
function effectOf(style: StyleData | null | undefined): string | null {
    const raw: unknown = (style?.custom as Record<string, unknown> | undefined)?.['--fnl-effect'];
    const value = typeof raw === 'object' && raw !== null ? (raw as Record<string, unknown>)['desktop'] : raw;
    if (typeof value !== 'string') {
        return null;
    }
    const effects = value.split(/\s+/).filter((name) => /^(reveal|lift|zoom|press|float|kenburns|marquee)$/.test(name));
    return effects.length > 0 ? effects.join(' ') : null;
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

    const element = (
        <div className="fnl-block" data-aurelle-role={typeof block.props.aurelleRole === 'string' ? block.props.aurelleRole : undefined} data-forma-role={typeof block.props.formaRole === 'string' ? block.props.formaRole : undefined} data-york-role={typeof block.props.yorkRole === 'string' ? block.props.yorkRole : undefined} style={{ maxWidth: 'min(100%, var(--fnl-max-width, 100%))', overflowWrap: 'anywhere' }} {...backgroundLayerAttributes(block.style)} {...nodeAttributes(context, ref)}>
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

    const element = (
        <div
            className="fnl-column"
            data-empty={column.blocks.length === 0 ? 'true' : 'false'}
            {...backgroundLayerAttributes(column.style)}
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

    const element = (
        <div className="fnl-row" data-stack={row.stackBelow ?? 'mobile'} {...backgroundLayerAttributes(row.style)} {...nodeAttributes(context, ref)}>
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
    const element = (
        <section
            id={typeof section.settings?.anchor === 'string' && section.settings.anchor !== '' ? section.settings.anchor : undefined}
            data-fluent={section.settings?.designCollection === 'fluent-365' ? String(section.settings.designBlock ?? '') : (typeof section.settings?.fluent === 'string' && section.settings.fluent !== '' ? section.settings.fluent : undefined)}
            className="fnl-section"
            data-vistahaven={section.settings?.designCollection === 'vistahaven' ? String(section.settings.designBlock ?? '') : undefined}
            data-solenne={section.settings?.designCollection === 'solenne-estates' ? String(section.settings.designBlock ?? '') : undefined}
            data-candid={section.settings?.designCollection === 'candid' ? String(section.settings.designBlock ?? '') : undefined}
            data-nursing-care={section.settings?.designCollection === 'nursing-care' ? String(section.settings.designBlock ?? '') : undefined}
            data-york-city={section.settings?.designCollection === 'york-city' ? String(section.settings.designBlock ?? '') : undefined}
            data-forma-build={section.settings?.designCollection === 'forma-build' ? String(section.settings.designBlock ?? '') : undefined}
            data-aurelle-apartments={section.settings?.designCollection === 'aurelle-apartments' ? String(section.settings.designBlock ?? '') : undefined}
            data-edefy={section.settings?.designCollection === 'edefy' ? String(section.settings.designBlock ?? '') : undefined}
            data-width={section.width}
            style={section.width === 'full' ? { width: '100%', maxWidth: 'none', marginInline: 'auto' } : { marginInline: 'auto' }}
            data-sticky={section.sticky ? 'true' : 'false'}
            {...backgroundLayerAttributes(section.style)}
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
${METROPOLIS_FONTS}\n${SCALEFORGE_FONTS}\n${SPAZIO_FONTS}\n${BASE_CSS}\n${EDEFY_CSS}\n${NURSING_CARE_CSS}\n${YORK_CITY_CSS}\n${FORMA_BUILD_CSS}\n${AURELLE_APARTMENTS_CSS}\n${CANDID_CSS}\n${FLUENT_CSS}\n${mode === 'editable' ? EDITABLE_CSS : ''}\n${css}\n${customCss}`}</style>
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
