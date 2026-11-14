import { useEffect, type RefObject } from 'react';

/** Reference scroll reveals use a one-second outQuart transition. */
export function useAurelleMotion(root: RefObject<HTMLDivElement | null>, editable: boolean, revision: unknown): void {
    useEffect(() => {
        const page = root.current;
        if (!page || editable || !page.querySelector('[data-aurelle-apartments]')) return;
        const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
        const nodes = Array.from(page.querySelectorAll<HTMLElement>('[data-aurelle-role="heading"], [data-aurelle-role="photo"], [data-aurelle-role="amenity-icon"], [data-aurelle-role="community-title"]'));
        const floating = Array.from(page.querySelectorAll<HTMLElement>('[data-aurelle-role="collage-left"], [data-aurelle-role="collage-left-second"], [data-aurelle-role="collage-right"]'));
        let observer: IntersectionObserver | undefined;
        let frame = 0;
        const paint = (): void => {
            frame = 0;
            floating.forEach(node => {
                const parent = node.closest('[data-aurelle-role="amenity-collage"]');
                if (!parent) return;
                const bounds = parent.getBoundingClientRect();
                const progress = Math.max(0, Math.min(1, (innerHeight - bounds.top) / (innerHeight + bounds.height)));
                const amount = node.dataset.aurelleRole?.startsWith('collage-left') ? 60 - 180 * progress : -100 + 175 * progress;
                node.style.setProperty('--aurelle-parallax', `${amount}px`);
            });
        };
        const scroll = (): void => { if (!frame) frame = requestAnimationFrame(paint); };
        const clear = (): void => {
            observer?.disconnect(); cancelAnimationFrame(frame); frame = 0;
            page.classList.remove('fnl-aurelle-motion');
            nodes.forEach(node => node.removeAttribute('data-aurelle-visible'));
            floating.forEach(node => node.style.removeProperty('--aurelle-parallax'));
            window.removeEventListener('scroll', scroll); window.removeEventListener('resize', scroll);
        };
        const start = (): void => {
            clear();
            if (preference.matches || typeof IntersectionObserver === 'undefined') return;
            page.classList.add('fnl-aurelle-motion');
            observer = new IntersectionObserver(entries => entries.forEach(entry => {
                if (entry.isIntersecting) { entry.target.setAttribute('data-aurelle-visible', 'true'); observer?.unobserve(entry.target); }
            }), { threshold: 0.08, rootMargin: '0px 0px -20px 0px' });
            nodes.forEach(node => observer?.observe(node));
            window.addEventListener('scroll', scroll, { passive: true }); window.addEventListener('resize', scroll); paint();
        };
        start(); preference.addEventListener('change', start);
        return () => { clear(); preference.removeEventListener('change', start); };
    }, [root, editable, revision]);
}

const STYLES = `
@font-face{font-family:Manrope;font-style:normal;font-weight:200 800;font-display:swap;src:url(/templates/aurelle-apartments/manrope.ttf) format('truetype')}
@font-face{font-family:'Playfair Display';font-style:italic;font-weight:400;font-display:swap;src:url(/templates/aurelle-apartments/playfair.ttf) format('truetype')}
[data-aurelle-apartments]{--aurelle-plum:var(--site-ink,#493f5c);--aurelle-clay:var(--site-accent,#b1806a);--aurelle-dark:var(--site-dark,#493f5c)}
[data-aurelle-apartments] .fnl-section-inner{width:100%;max-width:1300px!important;margin-inline:auto}
[data-aurelle-apartments] .fnl-block{min-width:0}
[data-aurelle-apartments] :is(a,button,input,textarea,select):focus-visible{outline:2px solid var(--aurelle-clay);outline-offset:5px}
[data-aurelle-apartments] a{transition:color .3s,background .3s,border-color .3s;text-decoration:none}
[data-aurelle-role=heading] a{color:inherit}
[data-aurelle-role=heading] :is(h1,h2,h3,h4),[data-aurelle-role=hero-title] h1,[data-aurelle-role=community-title] h2{font-family:inherit!important;font-style:inherit}
[data-aurelle-apartments=header]{position:absolute!important;top:0;left:0;right:0;z-index:30;overflow:visible!important}
[data-aurelle-role=header-bar]>div{align-items:center;justify-content:space-between;gap:20px!important;flex-wrap:nowrap!important}
[data-aurelle-role=logo]{flex-shrink:0;width:125px}
[data-aurelle-role=logo] img{width:125px!important;height:auto;max-width:100%}
[data-aurelle-role=main-nav]{margin-left:auto}
[data-aurelle-role=main-nav]>div{align-items:center;gap:20px!important;flex-wrap:nowrap!important}
[data-aurelle-role=main-nav] nav a{white-space:nowrap;color:white!important;font-size:14px!important}
[data-aurelle-role=main-nav] nav a:hover{color:var(--aurelle-clay)!important}
[data-aurelle-role=header-button] a{background:var(--aurelle-clay)!important;color:white!important;padding:8px 20px!important;border:0!important;font-size:13px!important;white-space:nowrap}
[data-aurelle-role=header-button] a:hover{background:white!important;color:var(--aurelle-plum)!important}
[data-aurelle-role=resident-menu]{position:relative;white-space:nowrap}
[data-aurelle-role=resident-menu]>div>div{border:0!important;background:transparent!important}
[data-aurelle-role=resident-menu] h3{font-family:Manrope,sans-serif!important;font-size:14px!important;font-style:normal}
[data-aurelle-role=resident-menu] button[aria-expanded]{padding:0!important;gap:8px;font-weight:400!important;color:white!important}
[data-aurelle-role=resident-menu] button[aria-expanded] svg{width:12px;height:12px;color:var(--aurelle-clay)}
[data-aurelle-role=resident-menu] div[id]{position:absolute;top:30px;right:0;width:210px;background:var(--aurelle-plum);padding:20px!important;border:1px solid #6b617d;z-index:40}
[data-aurelle-role=mobile-nav]{display:none}
[data-aurelle-apartments=home-hero]:after{content:'';position:absolute;inset:0;background:linear-gradient(rgba(0,0,0,.71) 13%,rgba(255,255,255,0));pointer-events:none}
[data-aurelle-apartments=introduction]{margin-top:-75px;z-index:2;overflow:visible!important}
[data-aurelle-role=hero-title] em{color:var(--aurelle-clay);font-style:inherit}
[data-aurelle-role=intro-copy]{padding-top:12px}
[data-aurelle-role=intro-contact]{margin-top:14px}
[data-aurelle-role=intro-contact]>div{gap:6px!important}
[data-aurelle-role=plain-link] a{padding:0!important;border:0!important;border-radius:0!important;font-size:13px!important}
[data-aurelle-role=plain-link] a:hover{color:var(--aurelle-plum)!important}
[data-aurelle-role=outline-button] a:hover,[data-aurelle-role=display-button] a:hover{background:var(--aurelle-plum)!important;color:white!important;border-color:var(--aurelle-plum)!important}
[data-aurelle-role=amenity-grid]>div{grid-template-columns:repeat(6,minmax(0,1fr))!important;gap:14px!important}
[data-aurelle-role=amenity-tile]>div{align-items:center;gap:24px!important}
[data-aurelle-role=amenity-icon] svg{stroke-width:1.25}
[data-aurelle-apartments=welcome-gallery]{overflow:visible!important}
[data-aurelle-apartments=welcome-gallery] .fnl-column:first-child{align-self:stretch}
[data-aurelle-role=sticky-intro]{position:sticky;top:24px}
[data-aurelle-role=sticky-intro]>div{gap:18px!important}
[data-aurelle-apartments=welcome-gallery] .fnl-column:last-child{gap:24px!important}
[data-aurelle-role=plan-grid]>div{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:90px 60px!important}
[data-aurelle-role=plan-image]{border:1px solid #e6e6e6;overflow:hidden;aspect-ratio:1;--fnl-height:100%!important}
[data-aurelle-role=plan-image] figure,[data-aurelle-role=plan-image] figure>a{display:block;width:100%;height:100%}
[data-aurelle-role=plan-image] img{height:100%!important;object-fit:contain!important;transition:transform .4s}
[data-aurelle-role=plan-card]:hover [data-aurelle-role=plan-image] img{transform:scale(1.025)}
[data-aurelle-role=plan-card]>div{gap:18px!important}
[data-aurelle-role=plan-meta]>div{justify-content:space-between;align-items:flex-start;gap:16px!important;flex-wrap:nowrap!important}
[data-aurelle-role=plan-description]{flex:1}
[data-aurelle-role=plan-price]{flex-shrink:0}
[data-aurelle-role=plan-price]>div{gap:2px!important}
[data-aurelle-role=chips]>div{flex-wrap:wrap!important;gap:10px!important}
[data-aurelle-role=chip]{border:1px solid #a9a3ae;border-radius:100px;padding:4px 16px}
[data-aurelle-role=facility-grid]>div{grid-template-columns:repeat(3,minmax(0,1fr))!important}
[data-aurelle-apartments=community] .fnl-section-inner{max-width:none!important}
[data-aurelle-apartments=community] .fnl-column{gap:0!important}
[data-aurelle-role=community-title]{text-align:center;font-size:10vw!important;line-height:1!important;position:relative;z-index:2;margin-bottom:-.22em}
[data-aurelle-role=community-copy]{padding:0 20px}
[data-aurelle-role=community-copy]>div,[data-aurelle-role=amenity-copy]>div{align-items:center}
[data-aurelle-role=community-copy] [data-aurelle-role=paragraph],[data-aurelle-role=amenity-copy] [data-aurelle-role=paragraph]{margin-inline:auto}
[data-aurelle-role=community-copy] [data-aurelle-role=display-button],[data-aurelle-role=amenity-copy] [data-aurelle-role=display-button]{align-self:center!important}
[data-aurelle-role=display-button] a{font-family:'Playfair Display',Georgia,serif!important;font-size:40px!important;font-style:italic!important;font-weight:400!important;line-height:1!important;border-color:var(--aurelle-plum)!important;color:var(--aurelle-plum)!important;padding:16px 80px!important}
[data-aurelle-role=amenity-collage]{position:relative;padding:30px 0 50px}
[data-aurelle-role=collage-main]{width:60%!important;margin:0 auto}
[data-aurelle-role=collage-left],[data-aurelle-role=collage-left-second],[data-aurelle-role=collage-right]{position:absolute;width:25%!important;z-index:2;transform:translateY(var(--aurelle-parallax,0px))}
[data-aurelle-role=collage-left]{left:0;top:55px}
[data-aurelle-role=collage-left-second]{left:0;top:300px}
[data-aurelle-role=collage-right]{right:0;bottom:20px}
[data-aurelle-role=gallery]>div:first-child{grid-template-columns:repeat(3,minmax(0,1fr))!important}
[data-aurelle-role=gallery] img{border-radius:0!important}
[data-aurelle-role=gallery] button{overflow:hidden}
[data-aurelle-role=gallery] button img{transition:transform .4s}
[data-aurelle-role=gallery] button:hover img{transform:scale(1.025)}
[data-aurelle-role=agent]>div{align-items:center;gap:24px!important;flex-wrap:nowrap!important}
[data-aurelle-role=agent-photo]{width:130px!important;flex-shrink:0}
[data-aurelle-role=agent]>div>.fnl-block:last-child{flex:1}
[data-aurelle-role=agent] nav a{word-break:break-word}
[data-aurelle-role=enquiry-form] form{row-gap:28px!important;column-gap:12px!important}
[data-aurelle-role=enquiry-form] label{font-size:14px!important;font-weight:400!important;color:var(--aurelle-plum)}
[data-aurelle-role=enquiry-form] :is(input,textarea,select){background:white!important;border:1px solid #c9c2d0!important;border-radius:0!important;padding:12px 16px!important;font-family:Manrope,sans-serif;font-size:16px!important;color:var(--aurelle-plum)!important}
[data-aurelle-role=enquiry-form] textarea{min-height:150px}
[data-aurelle-role=enquiry-form] :is(input,textarea,select){width:100%;min-width:0;max-width:100%}
[data-aurelle-role=enquiry-form] button{background:transparent!important;border:1px solid var(--aurelle-clay)!important;border-radius:100px!important;color:var(--aurelle-clay)!important;padding:12px 30px!important;font-weight:400!important;transition:background .3s,color .3s}
[data-aurelle-role=enquiry-form] button:hover{background:var(--aurelle-plum)!important;color:white!important}
[data-aurelle-role=faq]>div>div{border:0!important;border-bottom:1px solid #e5e0e9!important}
[data-aurelle-role=faq] h3{font-size:18px!important}
[data-aurelle-role=faq] button[aria-expanded]{padding:24px 0!important;font-weight:400!important;color:var(--aurelle-plum)}
[data-aurelle-role=faq] div[id]{padding:0 24px 24px 0!important;line-height:1.5}
[data-aurelle-apartments=footer] .fnl-row{gap:20px!important}
[data-aurelle-apartments=footer] nav a{font-size:14px!important;color:white!important}
[data-aurelle-apartments=footer] nav a:hover{color:var(--aurelle-clay)!important}
[data-aurelle-role=footer-main] nav a{color:var(--aurelle-clay)!important;text-decoration:underline;text-underline-offset:3px}
[data-aurelle-role=footer-utility] ul{justify-content:flex-end}
.fnl-aurelle-motion [data-aurelle-apartments] :is([data-aurelle-role=heading],[data-aurelle-role=community-title]){opacity:0;transform:translateY(100px);transition:opacity 1s cubic-bezier(.165,.84,.44,1),transform 1s cubic-bezier(.165,.84,.44,1)}
.fnl-aurelle-motion [data-aurelle-role=photo]{opacity:0;transform:scale(.75);transition:opacity 1s cubic-bezier(.165,.84,.44,1),transform 1s cubic-bezier(.165,.84,.44,1)}
.fnl-aurelle-motion [data-aurelle-role=amenity-icon]{opacity:0;transform:rotateY(-90deg);transition:opacity 1s cubic-bezier(.165,.84,.44,1),transform 1s cubic-bezier(.165,.84,.44,1)}
.fnl-aurelle-motion [data-aurelle-visible=true]{opacity:1!important;transform:none!important}
@media(max-width:1100px){[data-aurelle-role=main-nav]>div{gap:12px!important}[data-aurelle-role=main-nav] nav ul{gap:12px!important}[data-aurelle-role=main-nav] nav a{font-size:12px!important}[data-aurelle-role=resident-menu] h3{font-size:12px!important}[data-aurelle-role=header-bar]>div{gap:12px!important}[data-aurelle-role=header-button] a{font-size:11px!important;padding:8px 12px!important}}
@media(max-width:991px){[data-aurelle-role=main-nav],[data-aurelle-role=header-button]{display:none}[data-aurelle-role=mobile-nav]{display:block}[data-aurelle-role=mobile-nav] .fnl-nav-toggle{display:inline-flex!important;color:white!important}[data-aurelle-role=mobile-nav] nav:not([data-open=true]) ul{display:none!important}[data-aurelle-role=mobile-nav] nav[data-open=true] ul{position:absolute;left:20px;right:20px;top:50px;background:var(--aurelle-plum);padding:24px!important;border:1px solid #6b617d;flex-direction:column!important;align-items:flex-start;z-index:50}[data-aurelle-role=mobile-nav] nav a{color:white!important}[data-aurelle-role=amenity-grid]>div{grid-template-columns:repeat(3,minmax(0,1fr))!important}[data-aurelle-apartments=introduction]{margin-top:-60px}[data-aurelle-apartments=introduction] .fnl-column{grid-column:span 12!important}[data-aurelle-role=intro-contact]{max-width:430px;margin-top:20px}[data-aurelle-role=plan-grid]>div{gap:60px 28px!important}[data-aurelle-role=facility-grid]>div{grid-template-columns:repeat(2,minmax(0,1fr))!important}[data-aurelle-role=gallery]>div:first-child{grid-template-columns:repeat(2,minmax(0,1fr))!important}[data-aurelle-role=agent]>div{flex-wrap:wrap!important}}
@media(max-width:767px){[data-aurelle-apartments] .fnl-row{row-gap:30px!important;column-gap:12px!important}[data-aurelle-apartments=page-hero] h1{font-size:56px!important;line-height:1.05!important}[data-aurelle-role=hero-title]{font-size:64px!important;line-height:1.05!important}[data-aurelle-apartments=introduction]{margin-top:-40px}[data-aurelle-role=sticky-intro]{position:static}[data-aurelle-apartments=welcome-gallery] .fnl-column:last-child{gap:20px!important}[data-aurelle-role=plan-grid]>div{grid-template-columns:1fr!important;gap:50px!important}[data-aurelle-role=facility-grid]>div{grid-template-columns:1fr!important;gap:36px!important}[data-aurelle-role=gallery]>div:first-child{grid-template-columns:1fr!important}[data-aurelle-role=plan-description] h3{font-size:24px!important}[data-aurelle-role=plan-meta]>div{gap:10px!important}[data-aurelle-role=plan-price] h3{font-size:24px!important}[data-aurelle-role=chips]>div{gap:8px!important}[data-aurelle-role=chip]{padding:4px 12px}[data-aurelle-role=display-button] a{font-size:30px!important;padding:14px 28px!important}[data-aurelle-role=community-title]{font-size:12vw!important;line-height:1.2!important;margin-bottom:-.15em}[data-aurelle-role=community-copy]{margin-top:24px!important}[data-aurelle-role=collage-main]{width:80%!important}[data-aurelle-role=collage-main] img{height:320px!important}[data-aurelle-role=collage-left],[data-aurelle-role=collage-left-second],[data-aurelle-role=collage-right]{width:30%!important}[data-aurelle-role=collage-left] img,[data-aurelle-role=collage-left-second] img,[data-aurelle-role=collage-right] img{height:110px!important}[data-aurelle-role=collage-left-second]{top:170px}[data-aurelle-role=amenity-collage]{padding:20px 0 30px}[data-aurelle-role=amenity-copy] p{font-size:18px!important}[data-aurelle-apartments=footer] .fnl-row:nth-child(2){margin:60px 0 30px!important}[data-aurelle-role=footer-utility] ul{justify-content:flex-start}[data-aurelle-role=agent-photo]{width:100px!important}[data-aurelle-role=agent-photo] img{height:110px!important}[data-aurelle-role=agent]>div{flex-direction:column!important;align-items:flex-start!important}[data-aurelle-role=agent]>div>.fnl-block:last-child{width:100%;flex:none!important}}
@media(max-width:479px){[data-aurelle-apartments=home-hero]{min-height:35vh!important}[data-aurelle-role=hero-title]{font-size:52px!important}[data-aurelle-apartments=page-hero] h1{font-size:44px!important}[data-aurelle-role=amenity-grid]>div{grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px!important}[data-aurelle-role=amenity-tile]{padding:20px 8px!important}[data-aurelle-apartments=footer] h2{font-size:36px!important}[data-aurelle-role=plan-meta]>div{flex-direction:column!important}[data-aurelle-role=plan-price]{text-align:left!important}[data-aurelle-role=plan-price]>div{flex-direction:row!important;align-items:center;gap:12px!important}[data-aurelle-role=plan-price] [data-aurelle-role=paragraph]:first-child{order:2}}
@media(prefers-reduced-motion:reduce){[data-aurelle-apartments] *,[data-aurelle-apartments] *:before,[data-aurelle-apartments] *:after{animation:none!important;transition:none!important;scroll-behavior:auto!important}.fnl-aurelle-motion [data-aurelle-apartments] [data-aurelle-role]{opacity:1;transform:none}[data-aurelle-role=collage-left],[data-aurelle-role=collage-left-second],[data-aurelle-role=collage-right]{transform:none!important}}
`;

/** Mirrors media queries for the builder's tablet and phone canvases. */
function canvasRules(css: string): string {
    let result = '';
    for (const match of css.matchAll(/@media\(max-width:(\d+)px\)\{/g)) {
        let end = match.index! + match[0].length;
        const start = end;
        let depth = 1;
        while (end < css.length && depth > 0) { if (css[end] === '{') depth++; if (css[end] === '}') depth--; end++; }
        const content = css.slice(start, end - 1);
        for (const breakpoint of Number(match[1]) > 767 ? ['tablet', 'mobile'] : ['mobile']) {
            result += content.split('}').filter(rule => rule.includes('{')).map(rule => {
                const split = rule.indexOf('{');
                const selectors = rule.slice(0, split).split(',').map(selector => `.fnl-page[data-breakpoint="${breakpoint}"] ${selector.trim()}`).join(',');
                return `${selectors}{${rule.slice(split + 1)}}`;
            }).join('');
        }
    }
    return result;
}

export const AURELLE_APARTMENTS_CSS = STYLES + canvasRules(STYLES);
