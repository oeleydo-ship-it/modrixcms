/**
 * Layout, content and media blocks — the ones that render real output on a
 * public page with no backend behind them.
 */

import {
    ArrowRight,
    Waves,
    Dumbbell,
    Car,
    Package,
    PawPrint,
    BookOpen,
    Building2,
    Boxes,
    Construction,
    PanelsTopLeft,
    Ruler,
    Calculator,
    CalendarCheck,
    ChartNoAxesCombined,
    ClipboardCheck,
    Clock,
    Compass,
    AtSign,
    Camera,
    Check,
    ChevronLeft,
    ChevronRight,
    Globe,
    Heart,
    Mail,
    Menu,
    X as CloseIcon,
    MapPin,
    Home,
    Layers,
    MailCheck,
    Phone,
    Play,
    Shield,
    Star,
    Target,
    Users,
    Workflow,
    Video,
    Zap,
    Plus,
    TrainFront,
    Languages,
    ShieldCheck,
    Sparkle,
    CircleDot,
    CircleCheck,
    TriangleAlert,
    Thermometer,
    HeartPulse,
    Brain,
    Syringe,
    FlaskConical,
    MessageCircle,
    Stethoscope,
    Baby,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useRef, useState, type CSSProperties } from 'react';
import { bool, list, num, oneOf, optionalStr, record, recordList, safeHref, str } from '../props';
import { resolveButtonAction, scrollToTarget, useFunnel } from '../funnel';
import { CanvasAddButton, EditableText, Placeholder, stack, withItem, type BlockComponentProps } from './shared';
import { UpsellButton } from './upsell';
import { responsiveProp } from '../responsiveProps';

/**
 * lucide dropped its brand glyphs, so social networks map onto the closest
 * neutral icon. The label still names the network for assistive technology.
 */
const ICONS: Record<string, LucideIcon> = {
    waves: Waves,
    dumbbell: Dumbbell,
    car: Car,
    package: Package,
    'paw-print': PawPrint,
    'book-open': BookOpen,
    'building-2': Building2,
    boxes: Boxes,
    construction: Construction,
    'panels-top-left': PanelsTopLeft,
    ruler: Ruler,
    star: Star,
    zap: Zap,
    shield: Shield,
    heart: Heart,
    check: Check,
    clock: Clock,
    mail: Mail,
    'map-pin': MapPin,
    play: Play,
    'arrow-right': ArrowRight,
    globe: Globe,
    calculator: Calculator,
    'calendar-check': CalendarCheck,
    'chart-no-axes-combined': ChartNoAxesCombined,
    'clipboard-check': ClipboardCheck,
    compass: Compass,
    home: Home,
    layers: Layers,
    'mail-check': MailCheck,
    phone: Phone,
    target: Target,
    users: Users,
    workflow: Workflow,
    video: Video,
    x: AtSign,
    twitter: AtSign,
    threads: AtSign,
    instagram: Camera,
    facebook: Globe,
    linkedin: Globe,
    tiktok: Video,
    youtube: Video,
    'plus': Plus,
    'train-front': TrainFront,
    'languages': Languages,
    'shield-check': ShieldCheck,
    'sparkle': Sparkle,
    'circle-dot': CircleDot,
    'circle-check': CircleCheck,
    'triangle-alert': TriangleAlert,
    'thermometer': Thermometer,
    'heart-pulse': HeartPulse,
    'brain': Brain,
    'syringe': Syringe,
    'flask-conical': FlaskConical,
    'message-circle': MessageCircle,
    'stethoscope': Stethoscope,
    'baby': Baby,
};

/** The icon names a block may use, for the builder's icon pickers. */
export const ICON_NAMES: readonly string[] = ['star', 'zap', 'shield', 'heart', 'check', 'mail', 'map-pin', 'play', 'arrow-right', 'globe', 'calculator', 'calendar-check', 'chart-no-axes-combined', 'clipboard-check', 'compass', 'home', 'layers', 'mail-check', 'phone', 'target', 'users', 'workflow', 'video', 'building-2', 'boxes', 'construction', 'panels-top-left', 'ruler', 'waves', 'dumbbell', 'car', 'package', 'paw-print', 'book-open', 'plus', 'train-front', 'languages', 'shield-check', 'sparkle', 'circle-dot', 'circle-check', 'triangle-alert', 'thermometer', 'heart-pulse', 'brain', 'syringe', 'flask-conical', 'message-circle', 'stethoscope', 'baby', 'clock'];

export function iconFor(name: string): LucideIcon {
    return ICONS[name] ?? Star;
}

/* -------------------------------------------------------------------------- */

/**
 * The spans of a `columns` container, in twelfths. Child n is column n; each
 * child is itself a container, which is what lets a column hold columns.
 */
export function containerColumnSpans(props: Record<string, unknown>): number[] {
    const spans = list(props, 'columns')
        .map((value) => (typeof value === 'number' && Number.isFinite(value) ? Math.round(value) : NaN))
        .filter((value) => value >= 1 && value <= 12)
        .slice(0, 6);
    return spans.length > 0 ? spans : [6, 6];
}

export function ContainerBlock({ block, children, append }: BlockComponentProps) {
    const layout = oneOf(block.props, 'layout', ['stack', 'row', 'grid', 'columns'] as const, 'stack');
    const gap = responsiveProp('gap', str(block.props, 'gap', '16px'));

    if (layout === 'columns') {
        // A deleted column cell must not leave an empty track behind.
        const cellCount = block.children?.length ?? 0;
        const all = containerColumnSpans(block.props);
        const spans = cellCount > 0 && cellCount < all.length ? all.slice(0, cellCount) : all;
        return (
            <div
                className="fnl-nested-columns"
                data-stack={oneOf(block.props, 'stackBelow', ['mobile', 'tablet', 'none'] as const, 'mobile')}
                style={{
                    display: 'grid',
                    gridTemplateColumns: spans.map((span) => `minmax(0, ${span}fr)`).join(' '),
                    alignItems: 'var(--fnl-align-items, stretch)',
                    gap,
                }}
            >
                {children}
            </div>
        );
    }

    // The block's wrapper carries its style, so a row that declares how its items
    // line up passes that on to the flex row itself. Rows that declare nothing are
    // left alone, so template stylesheets targeting them still apply.
    const alignsItems = block.style?.justifyContent != null || block.style?.custom?.['justify-content'] != null;
    const crossAligns = block.style?.alignItems != null || block.style?.custom?.['align-items'] != null;
    const style: CSSProperties =
        layout === 'row'
            ? {
                  display: 'flex',
                  flexDirection: 'row',
                  flexWrap: 'wrap',
                  gap,
                  ...(alignsItems ? { justifyContent: 'inherit' } : {}),
                  ...(crossAligns ? { alignItems: 'inherit' } : {}),
              }
            : layout === 'grid'
              ? { display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(220px, 100%), 1fr))', gap }
              : stack(gap);

    return (
        <div style={style}>
            {children}
            {append}
        </div>
    );
}

export function HeadingBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const level = oneOf(block.props, 'level', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as const, 'h2');
    const text = str(block.props, 'text', '');
    const href = safeHref(block.props['link']);

    const HeadingTag = level;
    const heading = mode === 'editable' && onPropsChange ? (
        <EditableText
            as={level}
            value={inlineMarkupToText(text)}
            mode={mode}
            multiline
            ariaLabel="Edit heading"
            style={{ margin: 0, whiteSpace: 'pre-line', fontWeight: 'inherit', fontSize: 'inherit', lineHeight: 'inherit', letterSpacing: 'inherit' }}
            onChange={(next: string) => onPropsChange({ text: textToInlineMarkup(next) })}
        />
    ) : (
        <HeadingTag style={{ margin: 0, fontWeight: 'inherit', fontSize: 'inherit', lineHeight: 'inherit', letterSpacing: 'inherit' }} dangerouslySetInnerHTML={{ __html: sanitiseInline(text) }} />
    );

    return href && mode === 'static' ? (
        <a href={href} style={{ color: 'inherit', textDecoration: 'none' }}>
            {heading}
        </a>
    ) : (
        heading
    );
}

/** Inline markup is a sanitised subset (b, i, u, a, br) written on save. */
export function ParagraphBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const text = str(block.props, 'text', '');

    if (mode === 'editable' && onPropsChange) {
        return (
            <EditableText
                as="p"
                value={inlineMarkupToText(text)}
                mode={mode}
                multiline
                ariaLabel="Edit paragraph"
                style={{ margin: 0, whiteSpace: 'pre-line' }}
                onChange={(next) => onPropsChange({ text: textToInlineMarkup(next) })}
            />
        );
    }

    return <p style={{ margin: 0 }} dangerouslySetInnerHTML={{ __html: sanitiseInline(text) }} />;
}

/** The only markup a paragraph may carry, matching what the API sanitises to. */
export function sanitiseInline(html: string): string {
    return html
        .replace(/<(?!\/?(b|i|u|em|strong|a|br)\b)[^>]*>/gi, '')
        .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '')
        .replace(/href\s*=\s*("|')?\s*javascript:[^"'\s>]*/gi, 'href="#"');
}

/** Convert the supported inline markup into readable canvas-editing text. */
function inlineMarkupToText(html: string): string {
    return html
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>');
}

/** Store canvas line breaks as safe inline markup understood by public pages. */
function textToInlineMarkup(text: string): string {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\r?\n/g, '<br>');
}

export function ImageBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const src = optionalStr(block.props, 'src');
    const alt = str(block.props, 'alt', '');
    const caption = optionalStr(block.props, 'caption');
    const href = safeHref(block.props['link']);
    const objectFit = oneOf(block.props, 'objectFit', ['cover', 'contain', 'fill', 'none'] as const, 'cover');
    const loading = oneOf(block.props, 'loading', ['lazy', 'eager'] as const, 'lazy');
    const opacity = Math.max(0, Math.min(100, num(block.props, 'imageOpacity', 100))) / 100;
    const brightness = Math.max(25, Math.min(175, num(block.props, 'imageBrightness', 100))) / 100;
    const contrast = Math.max(25, Math.min(175, num(block.props, 'imageContrast', 100))) / 100;
    const saturation = Math.max(0, Math.min(200, num(block.props, 'imageSaturation', 100))) / 100;
    const blur = Math.max(0, Math.min(20, num(block.props, 'imageBlur', 0)));

    if (!src) {
        return mode === 'editable' ? <Placeholder label="Image" detail="Choose an image in the properties panel." /> : null;
    }

    const image = (
        <img
            src={src}
            alt={alt}
            loading={loading}
            style={{
                display: 'block',
                width: '100%',
                height: 'var(--fnl-height, auto)',
                objectFit,
                borderRadius: 'var(--fnl-border-radius, 0px)',
                opacity,
                filter: `brightness(${brightness}) contrast(${contrast}) saturate(${saturation}) blur(${blur}px)`,
            }}
        />
    );

    return (
        <figure style={{ margin: 0, borderRadius: 'inherit' }}>
            {href && mode !== 'editable' ? <a href={href}>{image}</a> : image}
            {mode === 'editable' && onPropsChange ? (
                <EditableText
                    value={caption ?? ''}
                    mode={mode}
                    placeholder="Add a caption"
                    ariaLabel="Edit caption"
                    style={{ display: 'block', marginTop: '8px', fontSize: '13px', opacity: 0.75 }}
                    onChange={(next) => onPropsChange({ caption: next === '' ? null : next })}
                />
            ) : (
                caption && <figcaption style={{ marginTop: '8px', fontSize: '13px', opacity: 0.75 }}>{caption}</figcaption>
            )}
        </figure>
    );
}

interface PlaybackOptions {
    autoplay: boolean;
    muted: boolean;
    loop: boolean;
    controls: boolean;
}

/**
 * The provider's embed URL with the block's playback settings applied. Each
 * provider spells them differently, and browsers only allow autoplay when the
 * video starts muted, so autoplay always implies mute.
 */
export function embedUrl(provider: string, url: string, playback: PlaybackOptions): string | null {
    const muted = playback.muted || playback.autoplay;
    const flag = (value: boolean): string => (value ? '1' : '0');

    if (provider === 'youtube') {
        const id = /(?:v=|youtu\.be\/|embed\/|shorts\/)([\w-]{6,})/.exec(url)?.[1];
        if (!id) {
            return null;
        }
        const query = new URLSearchParams({
            autoplay: flag(playback.autoplay),
            mute: flag(muted),
            controls: flag(playback.controls),
            rel: '0',
            playsinline: '1',
        });
        // YouTube only loops a single video when it is also its own playlist.
        if (playback.loop) {
            query.set('loop', '1');
            query.set('playlist', id);
        }
        return `https://www.youtube.com/embed/${id}?${query.toString()}`;
    }

    if (provider === 'vimeo') {
        const id = /vimeo\.com\/(?:video\/)?(\d+)/.exec(url)?.[1];
        if (!id) {
            return null;
        }
        const query = new URLSearchParams({
            autoplay: flag(playback.autoplay),
            muted: flag(muted),
            loop: flag(playback.loop),
            controls: flag(playback.controls),
            playsinline: '1',
        });
        return `https://player.vimeo.com/video/${id}?${query.toString()}`;
    }

    if (provider === 'wistia') {
        const id = /(?:wistia\.(?:com|net)\/(?:medias|embed\/iframe)\/|wvideo=)([a-z0-9]{6,})/i.exec(url)?.[1];
        if (!id) {
            return null;
        }
        const query = new URLSearchParams({
            autoPlay: String(playback.autoplay),
            muted: String(muted),
            endVideoBehavior: playback.loop ? 'loop' : 'default',
            playbar: String(playback.controls),
            controlsVisibleOnLoad: String(playback.controls),
        });
        return `https://fast.wistia.net/embed/iframe/${id}?${query.toString()}`;
    }

    return null;
}

function ModalVideo({ block, mode }: BlockComponentProps) {
    const dialog = useRef<HTMLDialogElement>(null);
    const [open, setOpen] = useState(false);
    const close = (): void => { dialog.current?.close(); setOpen(false); };
    return (
        <>
            <button type="button" aria-label={str(block.props, 'title', 'Play video')} onClick={() => { setOpen(true); dialog.current?.showModal(); }}
                style={{ display: 'inline-flex', alignItems: 'center', justifyContent: 'center', width: '76px', height: '76px', border: '2px solid currentColor', borderRadius: '50%', background: 'transparent', color: 'inherit', cursor: 'pointer' }}>
                <Play size={26} fill="currentColor" aria-hidden />
            </button>
            <dialog ref={dialog} aria-label={str(block.props, 'title', 'Video player')} onCancel={close} onClose={() => setOpen(false)} onClick={(event) => { if (event.target === event.currentTarget) close(); }}
                style={{ width: 'min(960px, 94vw)', maxWidth: '94vw', padding: '45px 15px 15px', border: 0, borderRadius: '8px', background: '#101820', color: '#fff' }}>
                <button type="button" aria-label="Close video" autoFocus onClick={close} style={{ position: 'absolute', right: '16px', top: '8px', border: 0, background: 'transparent', color: '#fff', fontSize: '26px', cursor: 'pointer' }}>×</button>
                {open && <VideoBlock block={{ ...block, props: { ...block.props, presentation: 'inline' } }} mode={mode} />}
            </dialog>
        </>
    );
}

export function VideoBlock({ block, mode }: BlockComponentProps) {
    if (block.props['presentation'] === 'modal' && optionalStr(block.props, 'url')) {
        return <ModalVideo block={block} mode={mode} />;
    }
    const provider = oneOf(block.props, 'provider', ['youtube', 'vimeo', 'wistia', 'file'] as const, 'youtube');
    const url = optionalStr(block.props, 'url');
    const ratio = str(block.props, 'aspectRatio', '16:9').split(':');
    const aspectRatio = `${Number(ratio[0] ?? 16) || 16} / ${Number(ratio[1] ?? 9) || 9}`;

    const playback: PlaybackOptions = {
        autoplay: bool(block.props, 'autoplay', false),
        muted: bool(block.props, 'muted', true),
        loop: bool(block.props, 'loop', false),
        controls: bool(block.props, 'controls', true),
    };

    if (!url) {
        return mode === 'editable' ? <Placeholder label="Video" detail="Paste a video URL in the properties panel." /> : null;
    }

    if (provider === 'file') {
        return (
            <video
                src={url}
                controls={playback.controls}
                muted={playback.muted || playback.autoplay}
                loop={playback.loop}
                // Never autoplay inside the builder: a playing video on the
                // canvas is noise while editing.
                autoPlay={playback.autoplay && mode !== 'editable'}
                playsInline
                poster={optionalStr(block.props, 'posterSrc') ?? undefined}
                style={{ width: '100%', aspectRatio, borderRadius: 'inherit' }}
            />
        );
    }

    const embed = embedUrl(provider, url, { ...playback, autoplay: playback.autoplay && mode !== 'editable' });
    if (!embed) {
        return mode === 'editable' ? <Placeholder label="Video" detail={`That is not a recognised ${provider} link.`} /> : null;
    }

    return (
        <iframe
            src={embed}
            title={str(block.props, 'title', 'Embedded video')}
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
            allowFullScreen
            style={{ width: '100%', aspectRatio, border: 0, borderRadius: 'inherit' }}
        />
    );
}

const BUTTON_PADDING = { sm: '8px 14px', md: '11px 20px', lg: '15px 28px' } as const;

/**
 * The visible surface of a button, with the block's own style settings layered
 * over the variant.
 *
 * The renderer publishes a button's background, text colour, border, radius,
 * shadow and padding as `--fnl-*` custom properties only (see
 * SURFACE_VARS_ONLY in PageRenderer), so each is read here with the variant's
 * value as the fallback: set nothing and the variant shows; set a background
 * and the button itself changes colour, not a band behind it.
 */
export function buttonSurface(
    variant: 'primary' | 'secondary' | 'outline' | 'link',
    size: 'sm' | 'md' | 'lg',
    fullWidth: boolean,
): CSSProperties {
    const link = variant === 'link';
    const base =
        variant === 'primary'
            ? { background: 'var(--fnl-accent, var(--site-button-bg, #4f46e5))', color: 'var(--site-button-fg, #fff)', border: 'var(--site-button-border, 1px solid transparent)' }
            : variant === 'secondary'
              ? { background: 'color-mix(in srgb, var(--site-secondary, #0f172a) 8%, transparent)', color: 'inherit', border: '1px solid transparent' }
              : variant === 'outline'
                ? { background: 'transparent', color: 'inherit', border: '1px solid currentColor' }
                : { background: 'transparent', color: 'inherit', border: '0' };

    return {
        display: fullWidth ? 'flex' : 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        gap: '8px',
        width: fullWidth ? '100%' : undefined,
        padding: `var(--fnl-padding, ${link ? '0' : size === 'md' ? `var(--site-button-padding, ${BUTTON_PADDING.md})` : BUTTON_PADDING[size]})`,
        backgroundColor: `var(--fnl-background-color, ${base.background})`,
        backgroundImage: 'var(--fnl-background-image, none)',
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        color: `var(--fnl-color, ${base.color})`,
        border: `var(--fnl-border, ${base.border})`,
        borderTop: `var(--fnl-border-top, var(--fnl-border, ${base.border}))`,
        borderRight: `var(--fnl-border-right, var(--fnl-border, ${base.border}))`,
        borderBottom: `var(--fnl-border-bottom, var(--fnl-border, ${base.border}))`,
        borderLeft: `var(--fnl-border-left, var(--fnl-border, ${base.border}))`,
        borderRadius: `var(--fnl-border-radius, ${link ? '0' : 'var(--site-button-radius, 8px)'})`,
        boxShadow: 'var(--fnl-box-shadow, none)',
        font: 'inherit',
        fontWeight: 'var(--site-button-weight, 600)' as CSSProperties['fontWeight'],
        textTransform: 'var(--site-button-transform, none)' as CSSProperties['textTransform'],
        lineHeight: 1.2,
        cursor: 'pointer',
        textDecoration: link ? 'underline' : 'none',
    };
}

export function ButtonBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const label = str(block.props, 'label', 'Button');
    const size = oneOf(block.props, 'size', ['sm', 'md', 'lg'] as const, 'md');
    const variant = oneOf(block.props, 'variant', ['primary', 'secondary', 'outline', 'link'] as const, 'primary');
    const fullWidth = bool(block.props, 'fullWidth', false);
    const newTab = bool(block.props, 'newTab', false);
    const runtime = useFunnel();
    const action = resolveButtonAction(block.props, runtime);

    const style = buttonSurface(variant, size, fullWidth);

    const content =
        mode === 'editable' && onPropsChange ? (
            <EditableText
                value={label}
                mode={mode}
                ariaLabel="Edit button label"
                onChange={(next) => onPropsChange({ label: next })}
            />
        ) : (
            label
        );

    // In the builder a click selects the block; it must never navigate away
    // from the editor or submit anything.
    if (mode === 'editable') {
        return (
            <button type="button" style={style}>
                {content}
            </button>
        );
    }

    if (action.kind === 'link') {
        return (
            <a
                href={action.href}
                style={style}
                {...(newTab ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
            >
                {content}
            </a>
        );
    }

    if (action.kind === 'upsell') {
        return (
            <UpsellButton productId={action.productId} thenHref={action.thenHref} style={style} fullWidth={fullWidth}>
                {content}
            </UpsellButton>
        );
    }

    if (action.kind === 'scroll') {
        return (
            <button type="button" data-fnl-scroll-target={action.targetId} style={style} onClick={() => scrollToTarget(action.targetId)}>
                {content}
            </button>
        );
    }

    if (action.kind === 'submit') {
        // A button block is never inside the form block it belongs with — they
        // are siblings in a column — so it submits the nearest form on the page
        // through the form's own handler rather than posting anything itself.
        return (
            <button
                type="button"
                style={style}
                onClick={(event) => {
                    const scope = event.currentTarget.closest('.fnl-section') ?? document;
                    const form = scope.querySelector('form') ?? document.querySelector('.fnl-page form');
                    form?.requestSubmit();
                }}
            >
                {content}
            </button>
        );
    }

    if (action.kind === 'popup') {
        return (
            <button type="button" style={style} data-fnl-popup={action.popupId}>
                {content}
            </button>
        );
    }

    return (
        <button type="button" style={style}>
            {content}
        </button>
    );
}

export function IconBlock({ block, mode }: BlockComponentProps) {
    const Icon = iconFor(str(block.props, 'name', 'star'));
    const size = responsiveProp('size', str(block.props, 'size', '24px'));
    const href = safeHref(block.props['link']);
    const glyph = <Icon style={{ width: size, height: size }} aria-hidden />;

    return href && mode !== 'editable' ? (
        <a href={href} style={{ color: 'inherit', display: 'inline-flex' }}>
            {glyph}
        </a>
    ) : (
        <span style={{ display: 'inline-flex', color: 'inherit' }}>{glyph}</span>
    );
}

export function DividerBlock({ block }: BlockComponentProps) {
    const style = oneOf(block.props, 'style', ['solid', 'dashed', 'dotted'] as const, 'solid');
    return (
        <hr
            style={{
                border: 0,
                borderTopWidth: responsiveProp('thickness', str(block.props, 'thickness', '1px')),
                borderTopStyle: style,
                // The block's text colour is the line colour; with none set the
                // line is a faint tint of whatever text colour surrounds it.
                borderTopColor: 'var(--fnl-color, color-mix(in srgb, currentColor 25%, transparent))',
                width: responsiveProp('width', str(block.props, 'width', '100%')),
                margin: 0,
            }}
        />
    );
}

export function SpacerBlock({ block }: BlockComponentProps) {
    return <div aria-hidden style={{ height: responsiveProp('height', str(block.props, 'height', '48px')) }} />;
}

/**
 * Authored HTML is stored verbatim and rendered inside a marked wrapper. The
 * API is what sanitises it; the renderer never executes scripts it strips here.
 */
export function HtmlBlock({ block, mode }: BlockComponentProps) {
    const html = str(block.props, 'html', '');
    if (html.trim() === '') {
        return mode === 'editable' ? <Placeholder label="HTML" detail="Paste markup in the properties panel." /> : null;
    }
    if (mode === 'editable') {
        return <Placeholder label="HTML" detail="Custom markup renders on the published page." />;
    }
    return <div data-fnl-raw dangerouslySetInnerHTML={{ __html: stripScripts(html) }} />;
}

export function stripScripts(html: string): string {
    return html
        .replace(/<script[\s\S]*?<\/script>/gi, '')
        .replace(/\son\w+\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi, '');
}

/**
 * Custom HTML, CSS and JavaScript, run only on the published page.
 *
 * The script is emitted as an inert `text/fnl-custom-code` element and started
 * by the public page runtime, which places it where the author asked — inline,
 * in the head, or at the end of the body. A live `<script>` could not honour
 * the placement, and would not run at all after a client-side render.
 */
export function CustomCodeBlock({ block, mode }: BlockComponentProps) {
    const html = str(block.props, 'html', '');
    const css = str(block.props, 'css', '');
    const js = str(block.props, 'js', '');
    const placement = oneOf(block.props, 'placement', ['inline', 'head', 'body_end'] as const, 'inline');

    if (mode === 'editable') {
        if (!html.trim() && !css.trim()) {
            return <Placeholder label="Custom code" detail="Add HTML, CSS or JavaScript in the properties panel. Scripts run only on published pages." />;
        }
        return <div>
            <div style={{ fontSize: '12px', padding: '8px', background: '#f1f5f9', color: '#475569' }}>Custom code preview / scripts disabled</div>
            <iframe title="Custom HTML preview" sandbox="" tabIndex={-1}
                srcDoc={'<!doctype html><html><head><meta charset="utf-8"><style>body{margin:0;overflow-wrap:anywhere}' + css.replace(/<\/style/gi, '') + '</style></head><body>' + stripScripts(html) + '</body></html>'}
                style={{ width: '100%', height: 'var(--fnl-height, 280px)', border: 0, pointerEvents: 'none', background: 'white' }} />
        </div>;
    }

    return (
        <div data-fnl-raw>
            {css.trim() !== '' && <style>{css.replace(/<\/style/gi, '')}</style>}
            {html.trim() !== '' && <div dangerouslySetInnerHTML={{ __html: stripScripts(html) }} />}
            {/<script\b/i.test(html) && (
                <script type="text/fnl-custom-code" data-format="html" data-placement={placement}
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(html).replace(/</g, '\\u003c') }} />
            )}
            {js.trim() !== '' && (
                <script
                    type="text/fnl-custom-code"
                    data-placement={placement}
                    // `</script` would end the element early in the server HTML.
                    dangerouslySetInnerHTML={{ __html: js.replace(/<\/script/gi, '<\\/script') }}
                />
            )}
        </div>
    );
}

export function LogoBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const src = optionalStr(block.props, 'src');
    const alt = str(block.props, 'alt', 'Logo');
    const href = safeHref(block.props['url']) ?? '/';

    const inner = src ? (
        <img src={src} alt={alt} style={{ display: 'block', maxHeight: '36px', maxWidth: '100%', width: 'auto', objectFit: 'contain' }} />
    ) : (
        <EditableText
            value={alt}
            mode={mode}
            placeholder="Brand name"
            ariaLabel="Edit logo text"
            style={{ fontWeight: 700, fontSize: '20px', letterSpacing: '-0.01em' }}
            {...(mode === 'editable' && onPropsChange ? { onChange: (next: string) => onPropsChange({ alt: next }) } : {})}
        />
    );

    if (mode === 'editable') {
        return <span style={{ display: 'inline-flex', alignItems: 'center', color: 'inherit' }}>{inner}</span>;
    }

    return (
        <a href={href} style={{ display: 'inline-flex', alignItems: 'center', color: 'inherit', textDecoration: 'none' }}>
            {inner}
        </a>
    );
}

export function NavigationBlock({ block, mode, onPropsChange }: BlockComponentProps) {
    const items = recordList(block.props, 'items');
    const orientation = oneOf(block.props, 'orientation', ['horizontal', 'vertical'] as const, 'horizontal');
    const behaviour = oneOf(block.props, 'mobileBehaviour', ['drawer', 'stack', 'hide', 'inline'] as const, 'drawer');
    const [open, setOpen] = useState(false);
    const navRef = useRef<HTMLElement>(null);
    const scope = `[data-fnl-nav="${block.id.replace(/[^A-Za-z0-9]/g, '')}"]`;

    const closeDetails = (except?: HTMLDetailsElement): void => {
        navRef.current?.querySelectorAll<HTMLDetailsElement>('details.fnl-nav-details[open]').forEach((details) => {
            if (details !== except) {
                details.open = false;
            }
        });
    };

    useEffect(() => {
        if (mode === 'editable') {
            return;
        }

        const onPointerDown = (event: MouseEvent): void => {
            const nav = navRef.current;
            if (!nav || (event.target instanceof Node && nav.contains(event.target))) {
                return;
            }
            closeDetails();
            setOpen(false);
        };

        const onKeyDown = (event: KeyboardEvent): void => {
            if (event.key !== 'Escape') {
                return;
            }
            closeDetails();
            setOpen(false);
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [mode]);

    // Mobile behaviour needs a media query, which inline styles cannot express,
    // so it is a scoped rule. It keys off both the real viewport (the public
    // page) and the canvas breakpoint attribute (the builder's mobile preview,
    // which is narrow while the window is not). `!important` is required to
    // beat the list's inline display.
    const mobileRules = [
        `${scope}[data-mobile="hide"]{display:none!important}`,
        `${scope}[data-mobile="drawer"]{display:flex!important;flex-direction:row!important;justify-content:var(--fnl-justify-content,flex-end)!important;align-items:center!important;position:relative!important;width:100%!important}`,
        `${scope}[data-mobile="drawer"] .fnl-nav-toggle{display:inline-flex!important;margin-bottom:0!important;margin-left:auto!important;width:36px!important;height:36px!important;border-radius:999px!important;border:1px solid var(--site-border,rgba(9,9,11,0.14))!important;background:#ffffff!important;color:#09090b!important}`,
        `${scope}[data-mobile="drawer"]:not([data-open="true"]) ul{display:none!important}`,
        `${scope}[data-mobile="drawer"][data-open="true"] ul{display:flex!important;flex-direction:column!important;flex-wrap:nowrap!important;justify-content:flex-start!important;align-items:stretch!important;position:absolute!important;top:calc(100% + 12px)!important;right:0!important;min-width:210px!important;max-height:calc(100vh - 104px)!important;overflow-x:hidden!important;overflow-y:auto!important;background:var(--site-surface,#ffffff)!important;color:var(--site-heading,#09090b)!important;padding:16px 20px!important;border-radius:18px!important;border:1px solid var(--site-border,#e4e4e7)!important;box-shadow:0 20px 44px -14px rgba(9,9,11,0.18)!important;z-index:95!important;gap:14px!important}`,
        `${scope}[data-mobile="stack"] ul{flex-direction:column!important;align-items:var(--fnl-text-align, flex-start)}`,
        `${scope}[data-mobile="drawer"] details>div{position:static!important;left:auto!important;right:auto!important;transform:none!important;width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-template-columns:minmax(0,1fr)!important;box-shadow:none!important;padding:10px 0!important}`,
    ].join('');
    const css = `@media (max-width:640px){${mobileRules}}${mobileRules
        .split('}')
        .filter(Boolean)
        .map((rule) => `.fnl-page[data-fnl-breakpoint="mobile"] ${rule}}`)
        .join('')}`;

    return (
        <nav
            ref={navRef}
            aria-label="Page navigation"
            data-fnl-nav={block.id.replace(/[^A-Za-z0-9]/g, '')}
            data-mobile={behaviour}
            data-open={open ? 'true' : 'false'}
        >
            <style>{css}</style>
            <button
                type="button"
                className="fnl-nav-toggle"
                aria-expanded={open}
                aria-label={open ? 'Close menu' : 'Open menu'}
                onClick={() => {
                    if (mode !== 'editable') {
                        setOpen((current) => !current);
                    }
                }}
                style={{
                    display: 'none',
                    alignItems: 'center',
                    justifyContent: 'center',
                    width: '40px',
                    height: '40px',
                    marginBottom: open ? '12px' : 0,
                    borderRadius: '8px',
                    border: '1px solid currentColor',
                    background: 'transparent',
                    color: 'inherit',
                    cursor: 'pointer',
                }}
            >
                {open ? (
                    <CloseIcon style={{ width: '20px', height: '20px' }} aria-hidden />
                ) : (
                    <Menu style={{ width: '20px', height: '20px' }} aria-hidden />
                )}
            </button>
            <ul
                style={{
                    display: 'flex',
                    flexDirection: orientation === 'vertical' ? 'column' : 'row',
                    flexWrap: 'wrap',
                    // Follows the block's text alignment, so "centre" centres the links.
                    justifyContent: orientation === 'vertical' ? undefined : 'var(--fnl-text-align, flex-start)',
                    gap: 'var(--fnl-gap, 20px)',
                    listStyle: 'none',
                    margin: 0,
                    padding: 0,
                }}
            >
                {items.map((item, index) => {
                    const label = typeof item['label'] === 'string' ? item['label'] : 'Link';
                    if (mode === 'editable' && onPropsChange) {
                        return (
                            <li key={index}>
                                <EditableText
                                    value={label}
                                    mode={mode}
                                    placeholder="Link"
                                    ariaLabel={`Edit link ${index + 1}`}
                                    onChange={(next) => onPropsChange({ items: withItem(items, index, { label: next }) })}
                                />
                            </li>
                        );
                    }
                      const submenu = recordList(item, 'children');
                      if (submenu.length > 0) {
                          return (
                              <li key={`${label}-${index}`} className="fnl-nav-item-has-dropdown" style={{ position: 'relative' }}>
                                  <details
                                      className="fnl-nav-details"
                                      onToggle={(event) => {
                                          if (event.currentTarget.open) {
                                              closeDetails(event.currentTarget);
                                          }
                                      }}
                                  >
                                      <summary className="fnl-nav-summary" style={{ cursor: 'pointer', listStyle: 'none', whiteSpace: 'nowrap' }}>{label} <span className="fnl-nav-caret" aria-hidden style={{ fontSize: '.7em' }}>▾</span></summary>
                                      <div
                                          className="fnl-nav-dropdown"
                                          data-fnl-dropdown
                                          style={{
                                              position: 'absolute',
                                              top: 'var(--fnl-dropdown-top, calc(100% + 18px))',
                                              left: 'var(--fnl-dropdown-left, auto)',
                                              right: 'var(--fnl-dropdown-right, 0)',
                                              width: 'var(--fnl-dropdown-width, min(460px, 85vw))',
                                              boxSizing: 'border-box',
                                              display: 'grid',
                                              gridTemplateColumns: 'var(--fnl-dropdown-cols, repeat(2,minmax(0,1fr)))',
                                              gap: 'var(--fnl-dropdown-gap, 18px)',
                                              padding: 'var(--fnl-dropdown-padding, 28px)',
                                              borderRadius: 'var(--fnl-dropdown-radius, 0px)',
                                              border: 'var(--fnl-dropdown-border, none)',
                                              background: 'var(--fnl-dropdown-bg, #ffffff)',
                                              color: 'var(--fnl-dropdown-color, #444444)',
                                              boxShadow: 'var(--fnl-dropdown-shadow, 0 8px 30px rgba(0,0,0,.16))',
                                              zIndex: 60,
                                              textAlign: 'left',
                                          }}
                                      >
                                          {submenu.map((child, childIndex) => {
                                              const childLabel = typeof child['label'] === 'string' ? child['label'] : 'Link';
                                              const childDesc = typeof child['description'] === 'string' && child['description'].trim() !== '' ? child['description'] : null;
                                              const childIcon = typeof child['icon'] === 'string' && child['icon'].trim() !== '' ? child['icon'] : null;
                                              const ChildIcon = childIcon ? iconFor(childIcon) : null;
                                              const href = safeHref(child['url']) ?? '#';

                                              if (ChildIcon === null && childDesc === null) {
                                                  return (
                                                      <a
                                                          key={childIndex}
                                                          className="fnl-nav-dropdown-link"
                                                          href={href}
                                                          onClick={() => {
                                                              closeDetails();
                                                              setOpen(false);
                                                          }}
                                                          style={{ color: 'inherit', textDecoration: 'none', lineHeight: 1.5 }}
                                                      >
                                                          {childLabel}
                                                      </a>
                                                  );
                                              }

                                              return (
                                                  <a
                                                      key={childIndex}
                                                      className="fnl-nav-dropdown-link fnl-nav-dropdown-rich"
                                                      href={href}
                                                      onClick={() => {
                                                          closeDetails();
                                                          setOpen(false);
                                                      }}
                                                      style={{ color: 'inherit', textDecoration: 'none', lineHeight: 1.4, display: 'flex', alignItems: 'flex-start', gap: '12px', minWidth: 0, width: '100%', boxSizing: 'border-box', whiteSpace: 'normal' }}
                                                  >
                                                      {ChildIcon ? (
                                                          <span className="fnl-nav-dropdown-icon" aria-hidden style={{ flexShrink: 0 }}>
                                                              <ChildIcon style={{ width: '18px', height: '18px' }} />
                                                          </span>
                                                      ) : null}
                                                      <span className="fnl-nav-dropdown-copy" style={{ display: 'flex', flexDirection: 'column', gap: '2px', minWidth: 0, flex: '1 1 0%', whiteSpace: 'normal' }}>
                                                          <span className="fnl-nav-dropdown-title">{childLabel}</span>
                                                          {childDesc ? <span className="fnl-nav-dropdown-desc">{childDesc}</span> : null}
                                                      </span>
                                                  </a>
                                              );
                                          })}
                                      </div>
                                  </details>
                              </li>
                          );
                      }
                      return (
                          <li key={`${label}-${index}`}>
                            <a
                                href={safeHref(item['url']) ?? '#'}
                                onClick={() => {
                                    closeDetails();
                                    setOpen(false);
                                }}
                                style={{ color: 'inherit', textDecoration: 'none' }}
                            >
                                {label}
                            </a>
                        </li>
                    );
                })}
                {mode === 'editable' && onPropsChange && (
                    <li>
                        <CanvasAddButton
                            mode={mode}
                            label="Add a link"
                            onAdd={() => onPropsChange({ items: [...items, { label: 'New link', url: '#' }] })}
                        />
                    </li>
                )}
            </ul>
        </nav>
    );
}

export function SocialIconsBlock({ block, mode }: BlockComponentProps) {
    const networks = recordList(block.props, 'networks');
    const size = responsiveProp('size', str(block.props, 'size', '20px'));
    const shape = oneOf(block.props, 'shape', ['circle', 'square', 'bare'] as const, 'circle');

    return (
        <ul
            style={{
                display: 'flex',
                flexWrap: 'wrap',
                justifyContent: 'var(--fnl-text-align, flex-start)',
                gap: 'var(--fnl-gap, 10px)',
                listStyle: 'none',
                margin: 0,
                padding: 0,
            }}
        >
            {networks.map((entry, index) => {
                const network = typeof entry['network'] === 'string' ? entry['network'] : 'x';
                const Icon = iconFor(network);
                return (
                    <li key={`${network}-${index}`}>
                        <a
                            href={safeHref(entry['url']) ?? '#'}
                            onClick={mode === 'editable' ? (event) => event.preventDefault() : undefined}
                            aria-label={network}
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                width: `calc(${size} * 2)`,
                                height: `calc(${size} * 2)`,
                                borderRadius: shape === 'circle' ? '999px' : shape === 'square' ? '8px' : 0,
                                border: shape === 'bare' ? 0 : '1px solid currentColor',
                                color: 'inherit',
                            }}
                        >
                            <Icon style={{ width: size, height: size }} aria-hidden />
                        </a>
                    </li>
                );
            })}
        </ul>
    );
}

export function GalleryBlock({ block, mode }: BlockComponentProps) {
    const columns = Math.max(1, num(block.props, 'columns', 3));
    const gap = responsiveProp('gap', str(block.props, 'gap', '12px'));
    const lightbox = bool(block.props, 'lightbox', true) && mode !== 'editable';
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    const images = list(block.props, 'images')
        .map((entry) => {
            const image = record(entry);
            const src = typeof image['src'] === 'string' ? image['src'] : typeof entry === 'string' ? entry : '';
            return { src, alt: typeof image['alt'] === 'string' ? image['alt'] : '' };
        })
        .filter((image) => safeHref(image.src) !== undefined);

    useEffect(() => {
        if (openIndex === null) {
            return;
        }
        const onKey = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                setOpenIndex(null);
            }
            if (event.key === 'ArrowRight') {
                setOpenIndex((current) => (current === null ? null : (current + 1) % images.length));
            }
            if (event.key === 'ArrowLeft') {
                setOpenIndex((current) => (current === null ? null : (current - 1 + images.length) % images.length));
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [openIndex, images.length]);

    if (images.length === 0) {
        return mode === 'editable' ? <Placeholder label="Gallery" detail="Upload images in the properties panel." /> : null;
    }

    const radius = 'var(--fnl-border-radius, 8px)';
    const current = openIndex === null ? null : images[openIndex];

    return (
        <>
            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: responsiveProp('grid', `repeat(auto-fill, minmax(min(100%, calc((100% - ${columns - 1} * ${gap}) / ${columns})), 1fr))`),
                    gap,
                }}
            >
                {images.map((image, index) => {
                    const img = (
                        <img
                            src={image.src}
                            alt={image.alt}
                            loading="lazy"
                            style={{ display: 'block', width: '100%', aspectRatio: '4 / 3', objectFit: 'cover', borderRadius: radius }}
                        />
                    );
                    return lightbox ? (
                        <button
                            key={`${image.src}-${index}`}
                            type="button"
                            aria-label={image.alt ? `Open ${image.alt}` : `Open image ${index + 1}`}
                            onClick={() => setOpenIndex(index)}
                            style={{ padding: 0, border: 0, background: 'transparent', cursor: 'zoom-in', borderRadius: radius }}
                        >
                            {img}
                        </button>
                    ) : (
                        <div key={`${image.src}-${index}`}>{img}</div>
                    );
                })}
            </div>

            {current && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={current.alt || 'Image'}
                    onClick={() => setOpenIndex(null)}
                    style={{
                        position: 'fixed',
                        inset: 0,
                        zIndex: 1000,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        padding: '24px',
                        background: 'rgba(2,6,23,.88)',
                        cursor: 'zoom-out',
                    }}
                >
                    <img
                        src={current.src}
                        alt={current.alt}
                        style={{ maxWidth: '100%', maxHeight: '100%', borderRadius: '8px', boxShadow: '0 20px 60px rgba(0,0,0,.5)' }}
                    />
                    <button
                        type="button"
                        aria-label="Close"
                        onClick={() => setOpenIndex(null)}
                        style={{
                            position: 'absolute',
                            top: '16px',
                            right: '16px',
                            width: '40px',
                            height: '40px',
                            borderRadius: '999px',
                            border: 0,
                            background: 'rgba(255,255,255,.15)',
                            color: '#fff',
                            cursor: 'pointer',
                        }}
                    >
                        <CloseIcon style={{ width: '20px', height: '20px' }} aria-hidden />
                    </button>
                </div>
            )}
        </>
    );
}

export function MapBlock({ block, mode }: BlockComponentProps) {
    const address = optionalStr(block.props, 'address');
    const provider = oneOf(block.props, 'provider', ['osm', 'google'] as const, 'osm');
    const zoom = Math.max(1, Math.min(20, Math.round(num(block.props, 'zoom', 14))));
    const showMarker = bool(block.props, 'showMarker', true);
    const latitude = Number(block.props['latitude']);
    const longitude = Number(block.props['longitude']);
    const hasCoordinates =
        block.props['latitude'] !== null &&
        block.props['latitude'] !== '' &&
        block.props['longitude'] !== null &&
        block.props['longitude'] !== '' &&
        Number.isFinite(latitude) &&
        Number.isFinite(longitude);

    const frameStyle: CSSProperties = {
        display: 'block',
        width: '100%',
        height: 'var(--fnl-height, 360px)',
        minHeight: '240px',
        border: 0,
        borderRadius: 'inherit',
    };

    // OpenStreetMap needs coordinates. Everything else, including an address
    // on its own, uses Google's keyless embed, which geocodes the query itself.
    if (provider === 'osm' && hasCoordinates) {
        const delta = 0.02 * (16 / zoom);
        const bbox = [longitude - delta, latitude - delta, longitude + delta, latitude + delta].join('%2C');
        const marker = showMarker ? `&marker=${latitude}%2C${longitude}` : '';
        return (
            <iframe
                title={address ?? 'Map'}
                loading="lazy"
                src={`https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik${marker}`}
                style={frameStyle}
            />
        );
    }

    const query = hasCoordinates ? `${latitude},${longitude}` : address;

    if (!query) {
        return mode === 'editable' ? <Placeholder label="Map" detail="Set an address or coordinates in the properties panel." /> : null;
    }

    return (
        <iframe
            title={address ?? 'Map'}
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
            src={`https://maps.google.com/maps?q=${encodeURIComponent(query)}&z=${zoom}&output=embed`}
            style={frameStyle}
        />
    );
}

const CAROUSEL_GAP = 12;

export function CarouselBlock({ block, children, mode }: BlockComponentProps) {
    const showArrows = bool(block.props, 'showArrows', true);
    const showDots = bool(block.props, 'showDots', true);
    const autoplay = bool(block.props, 'autoplay', false);
    const loop = bool(block.props, 'loop', true);
    const intervalMs = Math.max(1500, num(block.props, 'intervalMs', 5000));
    const desktopPerView = Math.max(1, Math.round(num(block.props, 'slidesPerView', 1)));
    const mobilePerView = Math.max(1, Math.round(num(block.props, 'slidesPerViewMobile', desktopPerView)));
    const [isMobile, setIsMobile] = useState(false);
    const perView = isMobile ? mobilePerView : desktopPerView;
    useEffect(() => {
        const media = window.matchMedia('(max-width: 767px)');
        const update = () => setIsMobile(media.matches);
        update(); media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);

    const track = useRef<HTMLDivElement>(null);
    const [page, setPage] = useState(0);
    const slides = Array.isArray(children) ? children.length : children ? 1 : 0;
    const pages = Math.max(1, Math.ceil(slides / perView));

    const go = (target: number): void => {
        const element = track.current;
        if (!element) {
            return;
        }
        let next = target;
        if (next < 0) {
            next = loop ? pages - 1 : 0;
        }
        if (next >= pages) {
            next = loop ? 0 : pages - 1;
        }
        const left = (element.clientWidth + CAROUSEL_GAP) * next;
        const reduceMotion =
            typeof window.matchMedia === 'function' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        element.scrollTo({ left, behavior: reduceMotion ? 'auto' : 'smooth' });

        // A smooth scroll is not guaranteed to run: a hidden tab, a browser
        // that skips the animation, or a layout change mid-flight can leave the
        // track where it was while the dots say it moved. If it has not arrived
        // shortly after, jump there, so an arrow click always changes slide.
        window.setTimeout(() => {
            if (Math.abs(element.scrollLeft - left) > 2) {
                element.scrollTo({ left, behavior: 'auto' });
            }
        }, 450);

        setPage(next);
    };

    // Keep the dots honest when someone swipes or scrolls the track directly.
    useEffect(() => {
        const element = track.current;
        if (!element) {
            return;
        }
        const onScroll = (): void => {
            setPage(Math.round(element.scrollLeft / Math.max(1, element.clientWidth + CAROUSEL_GAP)));
        };
        element.addEventListener('scroll', onScroll, { passive: true });
        return () => element.removeEventListener('scroll', onScroll);
    }, []);

    // Autoplay only on the published page: a canvas that slides away while
    // someone edits a slide is unusable.
    useEffect(() => {
        if (!autoplay || mode === 'editable' || pages < 2) {
            return;
        }
        const timer = window.setTimeout(() => go(page + 1), intervalMs);
        return () => window.clearTimeout(timer);
    });

    return (
        <div style={{ position: 'relative' }}>
            <div
                ref={track}
                role="group"
                aria-roledescription="carousel"
                className="fnl-carousel-track"
                data-mobile-columns={mobilePerView}
                style={{
                    display: 'grid',
                    gridAutoFlow: 'column',
                    gridAutoColumns: `calc((100% - ${(perView - 1) * CAROUSEL_GAP}px) / ${perView})`,
                    gap: `${CAROUSEL_GAP}px`,
                    overflowX: 'auto',
                    scrollbarWidth: 'none',
                    paddingBottom: '4px',
                }}
            >
                {children}
            </div>
            {(showArrows || showDots) && pages > 1 && (
                <div style={{ display: 'flex', gap: '8px', alignItems: 'center', justifyContent: 'center', marginTop: '12px' }}>
                    {showArrows && (
                        <button type="button" aria-label="Previous slide" style={arrowStyle} onClick={() => go(page - 1)}>
                            <ChevronLeft style={{ width: '16px', height: '16px' }} aria-hidden />
                        </button>
                    )}
                    {showDots &&
                        Array.from({ length: pages }, (_, index) => (
                            <button
                                key={index}
                                type="button"
                                aria-label={`Go to slide ${index + 1}`}
                                aria-current={index === page}
                                onClick={() => go(index)}
                                style={{
                                    width: index === page ? '22px' : '8px',
                                    height: '8px',
                                    padding: 0,
                                    border: 0,
                                    borderRadius: '999px',
                                    background: 'currentColor',
                                    opacity: index === page ? 1 : 0.3,
                                    cursor: 'pointer',
                                    transition: 'width 150ms, opacity 150ms',
                                }}
                            />
                        ))}
                    {showArrows && (
                        <button type="button" aria-label="Next slide" style={arrowStyle} onClick={() => go(page + 1)}>
                            <ChevronRight style={{ width: '16px', height: '16px' }} aria-hidden />
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}

const arrowStyle: CSSProperties = {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    width: '32px',
    height: '32px',
    borderRadius: '999px',
    border: '1px solid currentColor',
    background: 'transparent',
    color: 'inherit',
    cursor: 'pointer',
};

/**
 * A saved form, drawn in full. The form's own public page is shown in a frame that
 * grows to the form's height (the page reports it), so nothing scrolls inside a box
 * and the form keeps its fields, layout and the active theme's styling.
 */
export function SiteFormBlock({ block, mode }: BlockComponentProps) {
    const slug = str(block.props, 'formSlug', '').trim();
    const frame = useRef<HTMLIFrameElement>(null);
    const [height, setHeight] = useState(520);

    useEffect(() => {
        const onMessage = (event: MessageEvent) => {
            const data = event.data as { type?: string; slug?: string; height?: number } | null;
            if (data && data.type === 'modrix-form-height' && data.slug === slug && event.source === frame.current?.contentWindow && typeof data.height === 'number') {
                setHeight(Math.max(120, Math.ceil(data.height)));
            }
        };
        window.addEventListener('message', onMessage);

        return () => window.removeEventListener('message', onMessage);
    }, [slug]);

    if (slug === '') {
        return mode === 'editable' ? <Placeholder label="Saved form" detail="Choose a form in the properties panel." /> : null;
    }

    return (
        <iframe
            ref={frame}
            src={`/forms/${encodeURIComponent(slug)}?embed=1`}
            title="Form"
            loading="lazy"
            style={{ display: 'block', width: '100%', height, border: 0, background: 'transparent', pointerEvents: mode === 'editable' ? 'none' : undefined }}
        />
    );
}
