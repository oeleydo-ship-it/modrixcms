import { useEffect, useRef, useState } from 'react';
import { EDEFY_FONT } from './edefyFont';

// Shared by previews, the editor and public rendering; also travels with inserted sections.
export const EDEFY_CSS = `
@font-face{font-family:Parkinsans;font-style:normal;font-weight:300 700;font-display:swap;src:url(data:font/woff2;base64,${EDEFY_FONT}) format('woff2')}
.fnl-page [data-edefy]{font-family:Parkinsans,sans-serif}
.fnl-page [data-edefy] h1,.fnl-page [data-edefy] h2,.fnl-page [data-edefy] h3,.fnl-page [data-edefy] h4{font-size:inherit;font-weight:inherit;line-height:inherit;letter-spacing:inherit}
.fnl-page [data-edefy],.fnl-page [data-edefy] *{box-sizing:border-box}
.fnl-page [data-edefy="header"] .fnl-block>a{text-decoration:none}
.fnl-page [data-edefy]>.fnl-section-inner{max-width:1812px}
.fnl-page [data-edefy="header"]>.fnl-section-inner{max-width:1100px;background:#f7f7f2;border-radius:0 0 24px 24px;padding:14px 20px}
.fnl-page [data-edefy="header"] .fnl-column>.fnl-block>div{align-items:center;justify-content:space-between;flex-wrap:nowrap!important;gap:20px!important}
.fnl-page [data-edefy="header"] nav ul{flex-wrap:nowrap!important;gap:18px!important}
.fnl-page [data-edefy="header"] .fnl-block{min-width:0}
.fnl-page [data-edefy="header"] + [data-edefy="hero"]{margin-top:-72px}
.fnl-page [data-edefy="hero"]{width:calc(100% - 20px);margin-inline:10px;overflow:hidden}
.fnl-page [data-edefy="hero"] h1{white-space:pre-line}
.fnl-page [data-edefy="hero"] .fnl-column>.fnl-block>div,.fnl-page [data-edefy="footer"]>.fnl-section-inner>.fnl-row:first-child .fnl-column>.fnl-block>div{justify-content:center}
.fnl-page [data-edefy="hero"]+[data-edefy="features"]{margin-top:-80px;background:transparent}
.fnl-page [data-edefy="features"]>.fnl-section-inner{max-width:1340px}
.fnl-page [data-edefy="mission"]>.fnl-section-inner{max-width:900px}
.fnl-page [data-edefy="footer"] h2{white-space:pre-line}
.fnl-page [data-edefy="testimonials"]>.fnl-section-inner>.fnl-row:nth-child(2)>.fnl-column:first-child>.fnl-block:last-child{margin:20px 0 0;z-index:2}
.fnl-edefy-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(.7);z-index:0}
.fnl-edefy-video-control{position:absolute;right:24px;bottom:96px;z-index:5;background:#191818;color:#fffeea;border:1px solid #fffeea;border-radius:99px;padding:10px 18px;font:inherit;cursor:pointer}
.fnl-page [data-edefy] a:focus-visible,.fnl-page [data-edefy] button:focus-visible{outline:3px solid #f9b000;outline-offset:4px}
@media(prefers-reduced-motion:no-preference){
.fnl-page [data-edefy] .fnl-block>a,.fnl-page [data-edefy] .fnl-block>button,.fnl-page [data-edefy] nav a{transition:transform .5s cubic-bezier(.645,.045,.355,1),opacity .3s}
.fnl-page [data-edefy] .fnl-block>a:hover,.fnl-page [data-edefy] .fnl-block>button:hover{transform:translateY(-3px)}
.fnl-page [data-edefy] nav a:hover{text-decoration:underline!important;text-underline-offset:5px}
@supports(animation-timeline:view()){
@keyframes edefy-reveal{from{opacity:.25;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.fnl-page:not(.fnl-editable) [data-edefy]:not([data-edefy="header"]):not([data-edefy="hero"])>.fnl-section-inner{animation:edefy-reveal linear both;animation-timeline:view();animation-range:entry 0% entry 25%}
}}
@media(max-width:1000px){.fnl-page [data-edefy="header"]>.fnl-section-inner{padding:12px}.fnl-page [data-edefy="header"] .fnl-column>.fnl-block>div{flex-wrap:wrap!important}.fnl-page [data-edefy="header"] nav ul{flex-wrap:wrap!important}}
@media(max-width:640px){
.fnl-page [data-edefy="header"] .fnl-column>.fnl-block>div{display:grid!important;grid-template-columns:1fr auto}
.fnl-page [data-edefy="header"] .fnl-column>.fnl-block>div>.fnl-block:last-child{display:none}
.fnl-page [data-edefy="header"] nav[data-open="true"] ul{position:absolute;top:100%;left:0;right:0;background:#f7f7f2;padding:24px!important;border-radius:0 0 24px 24px;box-shadow:0 16px 30px #19181822;z-index:40}
.fnl-page [data-edefy="header"] nav[data-open="true"] .fnl-nav-toggle{margin:0!important}
.fnl-page [data-edefy="hero"]+[data-edefy="features"]{margin-top:-60px}
.fnl-page [data-edefy="testimonials"]>.fnl-section-inner>.fnl-row:nth-child(2)>.fnl-column:first-child>.fnl-block:last-child{margin:0}
}
@media(max-width:767px){.fnl-page [data-edefy="benefits"] .fnl-row{column-gap:12px}}
.fnl-page[data-fnl-breakpoint="mobile"] [data-edefy="benefits"] .fnl-row{column-gap:12px}
.fnl-page[data-fnl-breakpoint="mobile"] [data-edefy="header"] .fnl-column>.fnl-block>div{display:grid!important;grid-template-columns:1fr auto}
.fnl-page[data-fnl-breakpoint="mobile"] [data-edefy="header"] .fnl-column>.fnl-block>div>.fnl-block:last-child{display:none}
`;

export function EdefyVideo({ url, editable }: { url: string; editable: boolean }) {
    const video = useRef<HTMLVideoElement>(null);
    const [playing, setPlaying] = useState(false);
    useEffect(() => {
        const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
        const sync = () => {
            if (!editable && !preference.matches) void video.current?.play().catch(() => setPlaying(false));
            else video.current?.pause();
        };
        sync();
        preference.addEventListener('change', sync);
        return () => preference.removeEventListener('change', sync);
    }, [editable, url]);
    if (!/^https:\/\//i.test(url)) return null;
    return <>
        <video ref={video} className="fnl-edefy-video" src={url} muted loop playsInline preload="none" poster="/templates/edefy/play.jpg" aria-hidden onPlay={() => setPlaying(true)} onPause={() => setPlaying(false)} />
        {!editable && <button className="fnl-edefy-video-control" type="button" onClick={() => { if (playing) video.current?.pause(); else void video.current?.play().catch(() => setPlaying(false)); }}>{playing ? 'Pause video' : 'Play video'}</button>}
    </>;
}
