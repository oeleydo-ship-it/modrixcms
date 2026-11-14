import { useEffect, useRef, useState, type RefObject } from 'react';
import { Play, X } from 'lucide-react';
import type { BlockData } from './types';
import { safeHref } from './props';

/** The reference uses one-second image wipes and heading reveals. */
export function useFormaMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
    useEffect(() => {
        const page = root.current;
        if (!page || editable || !page.querySelector('[data-forma-build]')) return;
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        let observer: IntersectionObserver | undefined;
        const nodes = Array.from(page.querySelectorAll<HTMLElement>('[data-forma-build] [data-forma-role="heading"], [data-forma-build] [data-forma-role="caption"], [data-forma-build] [data-forma-role="image-wipe"]'));
        const clear = (): void => { observer?.disconnect(); page.classList.remove('fnl-forma-motion'); nodes.forEach(n => n.removeAttribute('data-forma-visible')); };
        const start = (): void => {
            clear();
            if (media.matches || typeof IntersectionObserver === 'undefined') return;
            page.classList.add('fnl-forma-motion');
            observer = new IntersectionObserver(entries => entries.forEach(entry => {
                if (entry.isIntersecting) { entry.target.setAttribute('data-forma-visible', 'true'); observer?.unobserve(entry.target); }
            }), { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
            nodes.forEach(node => observer?.observe(node));
        };
        start(); media.addEventListener('change', start);
        return () => { clear(); media.removeEventListener('change', start); };
    }, [root, editable, revision]);
}

/** Keeps the native Video block's editable URL and poster, with a modal player. */
export function FormaFilm({ block }: { block: BlockData }) {
    const [open, setOpen] = useState(false);
    const dialog = useRef<HTMLDialogElement>(null);
    const trigger = useRef<HTMLButtonElement>(null);
    const url = safeHref(block.props.url);
    const poster = safeHref(block.props.posterSrc);
    const title = typeof block.props.title === 'string' ? block.props.title : 'Inside the build';
    useEffect(() => {
        if (!open) return;
        const modal = dialog.current; modal?.showModal();
        return () => { modal?.close(); trigger.current?.focus(); };
    }, [open]);
    if (!url) return null;
    return <>
        <button ref={trigger} type="button" className="fnl-forma-film-trigger" aria-label={`Play ${title}`} onClick={() => setOpen(true)}>
            {poster && <img src={poster} alt="Construction team reviewing a project" />}
            <span><span>{title}<small>Watch the site in motion</small></span><Play aria-hidden /></span>
        </button>
        {open && <dialog ref={dialog} className="fnl-forma-film-dialog" aria-label={title} onCancel={() => setOpen(false)} onClick={event => { if (event.target === event.currentTarget) setOpen(false); }}>
            <button type="button" className="fnl-forma-film-close" aria-label="Close film" onClick={() => setOpen(false)}><X aria-hidden /></button>
            <video src={url} poster={poster} controls autoPlay muted playsInline preload="metadata" aria-label={title} />
        </dialog>}
    </>;
}

const FORMA_STYLES = `
@font-face{font-family:Sora;font-style:normal;font-weight:400 600;font-display:swap;src:url(/templates/forma-build/sora.ttf) format('truetype')}
[data-forma-build]{--forma-ink:var(--site-ink,#181817);--forma-amber:var(--site-accent,#ffbb0f);--forma-muted:var(--site-muted,#565656)}
[data-forma-build] .fnl-section-inner{width:100%;max-width:1296px!important;margin-inline:auto}
[data-forma-build] .fnl-block{min-width:0}
[data-forma-build] :is(a,button,input,textarea,summary):focus-visible{outline:2px solid var(--forma-amber);outline-offset:5px}
[data-forma-build] [data-forma-role=button] a{transition:background .3s,color .3s}
[data-forma-build] [data-forma-role=button] a:hover{background:var(--forma-ink)!important;color:white!important}
[data-forma-build] :is([data-forma-role=button],[data-forma-role=text-link]) svg{transition:transform .3s}
[data-forma-build] :is([data-forma-role=button],[data-forma-role=text-link]) a:hover svg{transform:translateX(5px)}
[data-forma-build=header] [data-forma-role=header-bar]>div{justify-content:space-between;align-items:center;flex-wrap:nowrap!important}
[data-forma-build=header] [data-forma-role=main-nav]>div{align-items:center;gap:36px!important;flex-wrap:nowrap!important}
[data-forma-build] [data-forma-role=logo] img{width:170px!important;max-width:100%;height:auto}
[data-forma-build=header] nav a{position:relative;padding-bottom:6px}
[data-forma-build=header] nav a:after{content:'';position:absolute;bottom:0;left:0;right:0;height:1px;background:currentColor;transform:scaleX(0);transform-origin:left;transition:transform .3s}
[data-forma-build=header] nav a:hover:after{transform:scaleX(1)}
[data-forma-role=mobile-nav]{display:none}
[data-forma-role=page-menu]{position:relative}
[data-forma-role=page-menu] details{border:0!important;border-radius:0!important;background:transparent}
[data-forma-role=page-menu] summary{padding:0!important;font-size:16px;font-weight:400!important;white-space:nowrap}
[data-forma-role=page-menu] details>div{position:absolute;left:-16px;top:32px;width:170px;background:#fff;border:1px solid #dededb;border-radius:6px;padding:16px!important;z-index:50}
[data-forma-role=page-menu]>div>div{border:0!important}
[data-forma-role=page-menu] h3{font-size:16px!important}
[data-forma-role=page-menu] button[aria-expanded]{padding:0!important;font-weight:400!important;gap:8px!important;white-space:nowrap}
[data-forma-role=page-menu] button[aria-expanded] svg{width:12px!important;height:12px!important;transition:transform .3s!important}
[data-forma-role=page-menu]>div>div>div[id]{position:absolute;left:-16px;top:34px;width:170px;background:#fff;border:1px solid #dededb;border-radius:6px;padding:16px!important;z-index:50}
[data-forma-build=hero] .fnl-section-inner{max-width:1400px!important}
[data-forma-role=hero-panel]{position:relative;isolation:isolate;overflow:hidden}
[data-forma-role=hero-panel]:after{content:'';position:absolute;inset:0;z-index:0;background:rgba(0,0,0,.32);pointer-events:none}
[data-forma-role=hero-panel]>div{min-height:530px;justify-content:space-between}
[data-forma-role=hero-panel]>div>[data-forma-role=heading]{max-width:820px!important;line-height:1.2!important}
[data-forma-role=hero-bottom]>div{justify-content:space-between;align-items:flex-end;gap:40px!important;flex-wrap:nowrap!important}
[data-forma-role=film]{width:340px;flex-shrink:0}
.fnl-forma-film-trigger{display:block;width:100%;border:0;padding:0;border-radius:10px;overflow:hidden;background:#282827;color:white;text-align:left;cursor:pointer;font:inherit;position:relative}
.fnl-forma-film-trigger img{width:100%;height:190px;object-fit:cover;display:block;transition:transform .3s}
.fnl-forma-film-trigger:hover img{transform:scale(1.05)}
.fnl-forma-film-trigger>span{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:16px 20px;background:rgba(24,24,23,.85);font-size:14px}
.fnl-forma-film-trigger small{display:block;color:#b4b4b2;margin-top:4px;font-size:11px}
.fnl-forma-film-trigger svg{width:28px;height:28px;color:#ffbb0f}
.fnl-forma-film-dialog{width:min(1100px,92vw);max-width:none;padding:46px 16px 16px;border:0;border-radius:12px;background:#181817;color:white}
.fnl-forma-film-dialog::backdrop{background:rgba(0,0,0,.82)}
.fnl-forma-film-dialog video{display:block;width:100%;max-height:80vh;border-radius:6px}
.fnl-forma-film-close{position:absolute;right:12px;top:8px;background:transparent;color:white;border:0;cursor:pointer;padding:4px}
[data-forma-role=stats]>div{flex-wrap:nowrap!important;gap:40px!important}
[data-forma-role=stat]{min-width:0;flex:1}
[data-forma-role=stat]>div{gap:8px!important}
[data-forma-role=image-wipe]{position:relative;overflow:hidden;border-radius:10px}
[data-forma-role=project-grid]>div{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:40px!important}
[data-forma-role=project-card]>div{gap:20px!important}
[data-forma-role=project-card] [data-forma-role=image]{overflow:hidden;border-radius:10px}
[data-forma-role=project-card] img{transition:transform .3s}
[data-forma-role=project-card]:hover img{transform:scale(1.05)}
[data-forma-role=project-card] a{color:inherit;text-decoration:none}
[data-forma-role=project-card] a:hover{text-decoration:underline;text-underline-offset:5px}
[data-forma-role=project-meta]>div{justify-content:space-between;align-items:center;gap:16px!important;flex-wrap:nowrap!important}
[data-forma-role=project-meta] [data-forma-role=heading]{flex:1;font-size:22px!important}
[data-forma-role=project-meta] [data-forma-role=paragraph]{max-width:160px!important;text-align:right}
[data-forma-role=project-pagination]>div{display:flex;flex-direction:column}
[data-forma-role=project-pagination] [role=tablist]{order:2;justify-content:flex-end;margin-top:40px;border:0!important;gap:12px}
[data-forma-role=project-pagination] [role=tab]{border:0!important;border-radius:6px!important;background:var(--forma-amber)!important;color:var(--forma-ink)!important;padding:10px 20px!important;font:inherit;white-space:normal!important}
[data-forma-role=project-pagination] [role=tab][aria-selected=true]{opacity:.55}
[data-forma-role=service-icon]{margin-bottom:20px}
[data-forma-role=service-icon] svg{stroke-width:.75}
[data-forma-build=services-dark] [data-forma-role=text-link] a{color:white!important}
[data-forma-role=service-detail] details,[data-forma-role=article-detail] details{border:0!important;background:transparent;border-radius:0!important}
[data-forma-role=service-detail] summary,[data-forma-role=article-detail] summary{padding:0!important;font-weight:400!important;justify-content:flex-start!important;gap:12px}
[data-forma-role=service-detail] details>div,[data-forma-role=article-detail] details>div{padding:20px 0 0!important}
[data-forma-role=service-detail]>div>div,[data-forma-role=article-detail]>div>div{border:0!important}
[data-forma-role=service-detail] h3,[data-forma-role=article-detail] h3{font-size:16px!important}
[data-forma-role=service-detail] button[aria-expanded],[data-forma-role=article-detail] button[aria-expanded]{padding:0!important;font-weight:400!important}
[data-forma-role=service-detail] div[id],[data-forma-role=article-detail] div[id]{padding:20px 0 0!important}
[data-forma-role=service-detail] div[id]:not([hidden]),[data-forma-role=article-detail] div[id]:not([hidden]),[data-forma-role=faq] div[id]:not([hidden]){animation:formaPanelIn .3s ease}
@keyframes formaPanelIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
[data-forma-role=features] h4{font-weight:400!important;font-size:18px!important}
[data-forma-role=features]>div{gap:28px!important}
[data-forma-role=features]>div>div{border:0!important;box-shadow:none!important;background:transparent!important;padding:0!important}
[data-forma-role=features] svg{color:#999!important;width:20px!important;height:20px!important}
[data-forma-role=features] p{font-size:14px!important;color:var(--forma-muted)!important}
[data-forma-role=cta-panel]>div{justify-content:space-between;align-items:flex-start;gap:60px!important;flex-wrap:nowrap!important}
[data-forma-role=cta-copy]{flex:1}
[data-forma-role=cta-panel]>div>[data-forma-role=image-wipe]{width:36%;flex-shrink:0}
[data-forma-role=cta-copy]>div>[data-forma-role=button]{margin:35px 0 30px}
[data-forma-role=cta-copy] [data-forma-role=stats]>div{gap:24px!important}
[data-forma-role=review-card]>div{height:100%;justify-content:space-between;gap:30px!important}
[data-forma-role=review-author]>div{align-items:center;flex-wrap:nowrap!important;gap:12px!important}
[data-forma-role=review-author] [data-forma-role=image]{width:40px;flex-shrink:0;border-radius:50%;overflow:hidden}
[data-forma-role=reviews] .fnl-carousel-track{align-items:stretch}
[data-forma-role=reviews] .fnl-carousel-track>[data-forma-role=review-card]{height:100%}
[data-forma-role=insight-card]>div{align-items:center;gap:24px!important;flex-wrap:nowrap!important}
[data-forma-role=insight-card]>div>[data-forma-role=image]{width:32%;flex-shrink:0;overflow:hidden;border-radius:6px}
[data-forma-role=insight-copy]{flex:1}
[data-forma-role=insight-card] img{transition:transform .3s}
[data-forma-role=insight-card]:hover img{transform:scale(1.05)}
[data-forma-role=team-card] [data-forma-role=image-wipe]{padding:12px;background:#262625;border:1px solid #393938;border-radius:10px}
[data-forma-role=team-card] img{object-position:center 25%}
[data-forma-role=milestone]>div{align-items:flex-start;flex-wrap:nowrap!important}
[data-forma-role=milestone]>div>[data-forma-role=paragraph]{width:70px;flex-shrink:0;padding-top:24px}
[data-forma-role=milestone-copy]{flex:1;position:relative}
[data-forma-role=milestone-copy]:before{content:'';width:7px;height:7px;border:1px solid #ccc;border-radius:50%;position:absolute;left:-23px;top:28px;background:#fff}
[data-forma-build=history] .fnl-column:last-child{gap:40px!important;padding-top:70px}
[data-forma-role=faq] details{border:0!important;border-bottom:1px solid #dcdcd9!important;border-radius:0!important;padding:0!important}
[data-forma-role=faq] summary{font-size:16px;font-weight:400!important;padding:22px 0!important;gap:20px}
[data-forma-role=faq] details>div{padding:0 24px 22px 0!important;color:var(--forma-muted);font-size:14px;line-height:1.7}
[data-forma-role=faq] h3{font-size:16px!important}
[data-forma-role=faq] button[aria-expanded]{font-weight:400!important;padding:22px 0!important}
[data-forma-role=faq] button[aria-expanded] svg{transition:transform .3s!important}
[data-forma-role=faq] div[id]{font-size:14px!important;color:var(--forma-muted);padding:0 24px 22px 0!important;line-height:1.7}
[data-forma-role=contact-strip]>div{justify-content:space-between;align-items:flex-start;gap:36px!important;flex-wrap:nowrap!important}
[data-forma-role=contact-strip]>div>.fnl-block{flex:1}
[data-forma-role=contact-form] form{gap:24px!important}
[data-forma-role=contact-form] label{font-weight:400!important;font-size:16px!important}
[data-forma-role=contact-form] input,[data-forma-role=contact-form] textarea{border:1px solid #dededb!important;border-radius:4px!important;font-size:14px!important;padding:12px!important;background:white!important}
[data-forma-role=contact-form] textarea{min-height:170px}
[data-forma-role=contact-form] button,[data-forma-role=newsletter] button{background:var(--forma-amber)!important;color:var(--forma-ink)!important;border-radius:6px!important;font-weight:400!important}
[data-forma-role=story-columns]>div{align-items:flex-end;gap:40px!important;flex-wrap:nowrap!important;margin-top:70px}
[data-forma-role=story-copy]{flex:1}
[data-forma-role=story-columns]>div>[data-forma-role=image-wipe]{width:38%;flex-shrink:0}
[data-forma-build=footer] .fnl-row{gap:36px!important}
[data-forma-build=footer] nav a{color:white!important;font-size:14px}
[data-forma-build=footer] nav a:hover{color:var(--forma-amber)!important}
[data-forma-role=footer-strip]>div{justify-content:space-between;align-items:center;flex-wrap:nowrap!important;gap:32px!important}
[data-forma-role=newsletter] form{display:flex!important;align-items:flex-end!important;gap:10px!important}
[data-forma-role=newsletter] form>div:has(label){flex:1}
[data-forma-role=newsletter] input{border:1px solid #393938!important;background:#262625!important;color:white!important;border-radius:6px!important;font-size:14px!important}
[data-forma-role=newsletter] label{font-size:12px!important;color:#b4b4b2!important;font-weight:400!important}
.fnl-forma-motion [data-forma-build]:not([data-forma-build=header]) [data-forma-role=heading],.fnl-forma-motion [data-forma-role=caption]{opacity:0;transform:translateY(70px);transition:opacity 1s cubic-bezier(.165,.84,.44,1),transform 1s cubic-bezier(.165,.84,.44,1)}
.fnl-forma-motion [data-forma-build] [data-forma-visible=true]{opacity:1!important;transform:none!important}
.fnl-forma-motion [data-forma-role=image-wipe]:after{content:'';position:absolute;inset:0;background:#aaa;pointer-events:none;transform:translateY(0);transition:transform 1s cubic-bezier(.901,.2,.187,.79);z-index:2}
.fnl-forma-motion [data-forma-role=image-wipe][data-forma-visible=true]:after{transform:translateY(101%)}
@media(max-width:767px){[data-forma-build=header] [data-forma-role=main-nav],[data-forma-build=header] [data-forma-role=header-bar]>div>[data-forma-role=button]{display:none}[data-forma-role=mobile-nav]{display:block}[data-forma-role=mobile-nav] .fnl-nav-toggle{display:inline-flex!important}[data-forma-role=mobile-nav] nav:not([data-open=true]) ul{display:none!important}[data-forma-role=mobile-nav] nav[data-open=true] ul{position:absolute;left:20px;right:20px;top:82px;background:#fff;padding:22px!important;border:1px solid #dededb;border-radius:10px;z-index:50;flex-direction:column!important;align-items:flex-start}}
@media(max-width:1100px){[data-forma-build=header] [data-forma-role=main-nav]>div{gap:20px!important}[data-forma-role=hero-panel]>div>[data-forma-role=heading]{font-size:60px!important}[data-forma-role=cta-panel]>div{gap:30px!important}[data-forma-role=project-meta]>div{flex-wrap:wrap!important;gap:8px!important}[data-forma-role=project-meta] [data-forma-role=paragraph]{text-align:left;max-width:none!important}[data-forma-role=project-card] [data-forma-role=image]{height:360px!important}[data-forma-role=insight-card]>div{gap:18px!important}[data-forma-role=story-columns]>div{flex-wrap:wrap!important;margin-top:30px}[data-forma-role=story-columns]>div>[data-forma-role=image-wipe]{width:100%}[data-forma-role=footer-strip]>div{flex-wrap:wrap!important}}
@media(max-width:767px){[data-forma-build] .fnl-row{gap:24px!important}[data-forma-role=hero-panel]>div{min-height:520px;gap:40px!important}[data-forma-role=hero-panel]>div>[data-forma-role=heading]{font-size:40px!important}[data-forma-role=hero-bottom]>div{flex-wrap:wrap!important;gap:24px!important}[data-forma-role=film]{width:100%;max-width:360px!important}[data-forma-role=hero-bottom] [data-forma-role=stats]{width:100%}[data-forma-role=stats]>div{gap:24px!important}[data-forma-role=project-grid]>div{grid-template-columns:1fr!important;gap:28px!important}[data-forma-role=project-card]{padding:16px!important}[data-forma-role=cta-panel]>div{flex-direction:column!important;align-items:stretch!important;gap:36px!important}[data-forma-role=cta-copy]{width:100%;flex:none!important}[data-forma-role=cta-panel]>div>[data-forma-role=image-wipe]{width:100%;height:340px!important}[data-forma-role=cta-copy] [data-forma-role=stats]>div{gap:16px!important;flex-wrap:wrap!important}[data-forma-role=cta-copy] [data-forma-role=stat]{min-width:120px}[data-forma-role=cta-copy]>div>[data-forma-role=button]{margin:8px 0 14px}[data-forma-role=insight-card]>div{align-items:flex-start}[data-forma-role=insight-card]>div>[data-forma-role=image]{width:35%;height:170px!important}[data-forma-role=contact-strip]>div{flex-wrap:wrap!important;gap:28px!important}[data-forma-role=contact-strip]>div>.fnl-block{flex-basis:100%}[data-forma-role=service-icon]{margin-bottom:10px}[data-forma-build=history] .fnl-column:last-child{padding-top:0}[data-forma-role=faq-help]{margin-top:20px!important}[data-forma-role=story-columns]>div{flex-direction:column!important;align-items:stretch!important;margin-top:25px}[data-forma-role=story-copy]{width:100%;flex:none!important}[data-forma-role=story-columns]>div>[data-forma-role=image-wipe]{width:100%}[data-forma-role=footer-strip]>div{align-items:flex-start;gap:24px!important}[data-forma-role=footer-strip]>div>.fnl-block{flex-basis:100%}}
@media(max-width:640px){[data-forma-build=header] [data-forma-role=main-nav],[data-forma-build=header] [data-forma-role=header-bar]>div>[data-forma-role=button]{display:none}[data-forma-role=mobile-nav]{display:block}[data-forma-role=mobile-nav] nav[data-open=true] ul{position:absolute;left:20px;right:20px;top:82px;background:#fff;padding:22px!important;border:1px solid #dededb;border-radius:10px;z-index:50}[data-forma-role=hero-panel]>div>[data-forma-role=heading]{font-size:32px!important}[data-forma-role=project-meta] [data-forma-role=heading]{font-size:20px!important}[data-forma-role=insight-card]>div{flex-direction:column!important}[data-forma-role=insight-card]>div>[data-forma-role=image]{width:100%;height:200px!important}[data-forma-role=insight-copy]{width:100%}}
@media(prefers-reduced-motion:reduce){[data-forma-build] *,[data-forma-build] *:before,[data-forma-build] *:after{transition:none!important;animation:none!important}.fnl-forma-motion [data-forma-build] [data-forma-role=heading],.fnl-forma-motion [data-forma-role=caption]{opacity:1;transform:none}.fnl-forma-motion [data-forma-role=image-wipe]:after{display:none}}
`;

/** The builder's phone canvas lives inside a desktop viewport. */
function canvasBreakpointRules(css: string): string {
    let result = '';
    for (const match of css.matchAll(/@media\(max-width:(\d+)px\)\{/g)) {
        let end = match.index! + match[0].length;
        const start = end;
        let depth = 1;
        while (end < css.length && depth > 0) { if (css[end] === '{') depth++; if (css[end] === '}') depth--; end++; }
        const content = css.slice(start, end - 1);
        const breakpoints = Number(match[1]) > 767 ? ['tablet', 'mobile'] : ['mobile'];
        for (const breakpoint of breakpoints) {
            result += content.split('}').filter(Boolean).map(rule => {
                const split = rule.indexOf('{');
                const selectors = rule.slice(0, split).split(',').map(selector => `.fnl-page[data-fnl-breakpoint="${breakpoint}"] ${selector}`).join(',');
                return `${selectors}{${rule.slice(split + 1)}}`;
            }).join('');
        }
    }
    return result;
}

export const FORMA_BUILD_CSS = FORMA_STYLES + canvasBreakpointRules(FORMA_STYLES);
