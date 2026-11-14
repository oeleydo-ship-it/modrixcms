import { useState } from 'react';

export function YorkAnnouncement({ text }: { text: string }) {
    const [dismissed, setDismissed] = useState(false);
    return dismissed ? <span data-york-dismissed="true" /> : <div className="fnl-york-announcement"><span>{text}</span><button type="button" aria-label="Dismiss city announcement" onClick={() => setDismissed(true)}>×</button></div>;
}

// Native block wrappers keep every image, heading, link and field editable.
export const YORK_CITY_CSS = `
@font-face{font-family:Geist;src:url('/templates/york-city/geist-latin.woff2') format('woff2');font-style:normal;font-weight:100 900;font-display:swap}
.fnl-page [data-york-city]{font-family:Geist,sans-serif;line-height:1.56}
.fnl-page [data-york-city]>.fnl-section-inner{max-width:none}
.fnl-page [data-york-city] h1,.fnl-page [data-york-city] h2,.fnl-page [data-york-city] h3{font-family:inherit;font-size:inherit;line-height:inherit;font-weight:inherit;letter-spacing:inherit}
.fnl-page [data-york-city] p{margin:0}
.fnl-page [data-york-city] [data-york-role="image"] figure{margin:0;overflow:hidden;border-radius:var(--fnl-border-radius,14px)}
.fnl-page [data-york-city] [data-york-role="image"] img{width:100%;object-fit:cover;transition:transform .55s}
.fnl-page [data-york-city] a{color:inherit;text-decoration:none}
.fnl-page [data-york-city] [data-york-role="button"] a{box-shadow:none;transition:transform .2s}
.fnl-page [data-york-city] [data-york-role="button"] a:hover{transform:translateY(-2px)}
.fnl-page [data-york-city] .fnl-block[data-york-role="button"]{align-self:flex-start}
.fnl-page [data-york-city="header"]>.fnl-section-inner{display:flex;flex-direction:column;gap:0}
.fnl-page [data-york-city="header"] .fnl-row,.fnl-page [data-york-city="header"] .fnl-column{gap:0}
.fnl-page:has([data-york-city="hero"],[data-york-city="about-hero"]) [data-york-city="header"]{background:transparent!important;margin-bottom:-110px}
.fnl-page [data-york-role="announcement"]{line-height:18px}
.fnl-page [data-york-role="announcement"]:has([data-york-dismissed]){display:none}
.fnl-york-announcement{position:relative;text-align:center;width:100%}
.fnl-york-announcement button{position:absolute;right:-16px;top:-3px;padding:0;border:0;background:none;font:inherit;font-size:20px;color:#85847f;cursor:pointer}
.fnl-page [data-york-role="nav-pill"]>div{align-items:center;justify-content:space-between;flex-wrap:nowrap!important;gap:20px!important;min-height:42px}
.fnl-page [data-york-role="nav-pill"]>div>.fnl-block:first-child{flex:0 0 125px}
.fnl-page [data-york-role="main-nav"]>div{flex-wrap:nowrap!important;align-items:center;gap:30px!important}
.fnl-page [data-york-role="page-menu"]{position:relative;min-width:70px;z-index:40}
.fnl-page [data-york-role="page-menu"]>div>div{border:0!important}
.fnl-page [data-york-role="page-menu"] h3 button{padding:10px 0!important;font-weight:400!important;font-size:14px!important}
.fnl-page [data-york-role="page-menu"] h3 svg{width:14px!important;height:14px!important}
.fnl-page [data-york-role="page-menu"]>div>div>div:not([hidden]){position:absolute;top:45px;left:-20px;width:210px;background:#fff;padding:20px!important;border-radius:18px;box-shadow:0 8px 32px #1615141c;z-index:50}
.fnl-page [data-york-role="page-menu"] nav a{white-space:nowrap}
.fnl-page [data-york-city="hero"]:after,.fnl-page [data-york-city="about-hero"]:after{content:'';position:absolute;inset:0;z-index:0;pointer-events:none;background:linear-gradient(45deg,rgba(0,0,0,.92) 24%,transparent 67%)}
.fnl-page [data-york-city="hero"] .fnl-column>.fnl-block[data-york-role="paragraph"],.fnl-page [data-york-city="about-hero"] .fnl-column>.fnl-block[data-york-role="paragraph"]{max-width:420px!important}
.fnl-page [data-york-city="about-hero"]+[data-york-city]{position:relative;border-radius:28px 28px 0 0;margin-top:-28px;padding-top:90px}
.fnl-page [data-york-role="photo-card"]:after,.fnl-page [data-york-role="alerts"]:after{content:'';position:absolute;inset:0;pointer-events:none;z-index:0;border-radius:inherit;background:linear-gradient(0deg,#161514,transparent 74%)}
.fnl-page [data-york-role="alerts"]:after{background:linear-gradient(270deg,#161514e8,transparent)}
.fnl-page [data-york-role="photo-card"]>div,.fnl-page [data-york-role="alerts"]>div{position:relative;z-index:1}
.fnl-page [data-york-role="photo-card"] [data-york-role="button"] a{background:transparent!important;color:#fff!important;padding:0!important;font-size:12px!important}
.fnl-page [data-york-role="photo-card"]>div>.fnl-block:first-child[data-york-role="paragraph"]{border-radius:40px;background:#fff;padding:4px 10px;align-self:flex-start}
.fnl-page [data-york-role="quick-carousel"]>div>div:last-child:not(.fnl-carousel-track){position:absolute;right:0;top:-65px;margin:0!important}
.fnl-page [data-york-role="quick-carousel"] button{background:#e8e6de!important;color:#161514!important;border:0!important;border-radius:100px!important;width:32px!important;height:32px!important}
.fnl-page [data-york-role="service-card"]>div{gap:16px!important}
.fnl-page [data-york-role="service-card"]{height:100%}
.fnl-page [data-york-role="category-tag"]{align-self:flex-start}
.fnl-page [data-york-role="category-tag"]>div{gap:0!important}
.fnl-page [data-york-role="service-card"] [data-york-role="paragraph"]{max-width:none!important}
.fnl-page [data-york-role="service-card"] [data-york-role="button"]{margin-top:auto}
.fnl-page [data-york-role="service-card"] img:hover{transform:scale(1.025)}
.fnl-page [data-york-role="history-card"]>div{height:100%}
.fnl-page [data-york-role="history-home"]>div>div{border:0!important;margin-bottom:14px}
.fnl-page [data-york-role="history-home"] button{font-size:15px!important;font-weight:500!important;white-space:pre-wrap;padding:8px 0!important}
.fnl-page [data-york-role="history-home"] button svg{display:none}
.fnl-page [data-york-role="history-home"]>div>div>div:not([hidden]){padding-left:65px!important}
.fnl-page [data-york-role="history-photo"]{position:relative}
.fnl-page [data-york-role="history-stats"]{position:absolute!important;bottom:14px;left:14px;z-index:2}
.fnl-page [data-york-role="history-stats"]>div{gap:10px!important}
.fnl-page [data-york-role="history"]>div{min-height:340px;justify-content:space-between}
.fnl-page [data-york-role="history"] [role="tablist"],.fnl-page [data-york-role="history-home"] [role="tablist"]{border:0!important}
.fnl-page [data-york-role="history"] [role="tab"],.fnl-page [data-york-role="history-home"] [role="tab"]{flex:1;background:#f6f4f0!important;border:0!important;border-radius:100px;padding:7px!important;font-size:11px!important;font-weight:400!important;opacity:1!important}
.fnl-page [data-york-role="history"] [role="tab"][aria-selected="true"],.fnl-page [data-york-role="history-home"] [role="tab"][aria-selected="true"]{background:#f3df3e!important}
.fnl-page [data-york-role="history"] [role="tabpanel"],.fnl-page [data-york-role="history-home"] [role="tabpanel"]{align-content:end;animation:fnl-york-enter .35s}
.fnl-page [data-york-role="event-copy"]>div{display:grid!important;grid-template-columns:1fr 1fr;gap:24px!important}
.fnl-page [data-york-role="event-stack"]>div{gap:24px!important}
.fnl-page [data-york-role="events-home"]>div{gap:0!important}
.fnl-page [data-york-role="events-home"] [role="tablist"]{flex:0 0 40%;align-items:flex-start;gap:20px!important;padding-top:10px}
.fnl-page [data-york-role="events-home"] [role="tab"]{padding:0!important;border:0!important;color:#85847f!important;font-weight:400!important;font-size:13px!important}
.fnl-page [data-york-role="events-home"] [role="tab"][aria-selected="true"]{color:#161514!important}
.fnl-page [data-york-role="events-home"] [role="tabpanel"]{min-width:0;flex:1}
.fnl-page [data-york-role="events-filter"] [role="tablist"]{border:0!important;flex-wrap:wrap;gap:10px!important;margin-bottom:20px}
.fnl-page [data-york-role="events-filter"] [role="tab"]{background:#fff!important;border-radius:100px;border:0!important;font-weight:400!important;opacity:1!important}
.fnl-page [data-york-role="events-filter"] [role="tab"][aria-selected="true"]{background:#f3df3e!important}
.fnl-page [data-york-role="events-filter"] [data-york-role="event-stack"]>div{display:grid!important;grid-template-columns:1fr 1fr;gap:24px!important}
.fnl-page [data-york-role="news-small"]>div{display:grid!important;grid-template-columns:135px minmax(0,1fr);gap:18px!important;align-items:center}
.fnl-page [data-york-role="news-small"] [data-york-role="button"]{font-size:11px}
.fnl-page [data-york-city="faq"] .fnl-column:first-child{gap:32px}
.fnl-page [data-york-city="faq"] [data-york-role="faq"]>div>div{border-color:#e6e3dc!important;border-top:1px solid #e6e3dc;border-bottom:0!important;padding:12px 0}
.fnl-page [data-york-city="faq"] [data-york-role="faq"] button{font-size:16px!important;font-weight:500!important;padding:16px 0!important}
.fnl-page [data-york-city="faq"] [data-york-role="faq"] p{font-size:14px;color:#85847f;max-width:510px;line-height:1.56}
.fnl-page [data-york-role="person"]>div{gap:8px!important}
.fnl-page [data-york-role="person"] img{object-position:center 22%!important}
.fnl-page [data-york-role="person"] [data-york-role="heading"]{margin-top:8px}
.fnl-page [data-york-role="stat"]>div{gap:0!important}
.fnl-page [data-york-role="contact-detail"]>div{display:flex!important;flex-direction:row!important;align-items:center;gap:22px!important}
.fnl-page [data-york-role="contact-detail"]>div>.fnl-block:first-child{flex:0 0 28px}
.fnl-page [data-york-role="contact-detail"]>div>.fnl-block:last-child>div{gap:4px!important}
.fnl-page [data-york-role="contact-form"] form{gap:22px!important}
.fnl-page [data-york-role="contact-form"] form label{font-weight:400!important;font-size:13px!important;color:#161514!important;margin-bottom:8px!important}
.fnl-page [data-york-role="contact-form"] input,.fnl-page [data-york-role="contact-form"] select,.fnl-page [data-york-role="contact-form"] textarea{background:#f6f4f0!important;border:1px solid #e9e6df!important;border-radius:10px!important;padding:14px 18px!important;font:inherit!important;box-shadow:none!important}
.fnl-page [data-york-role="contact-form"] textarea{min-height:105px}
.fnl-page [data-york-role="contact-form"] button[type="submit"],.fnl-page [data-york-role="newsletter"] button[type="submit"]{background:#f3df3e!important;color:#161514!important;border:0!important;border-radius:100px!important;padding:12px 20px!important;font-size:14px!important;font-weight:500!important;box-shadow:none!important}
.fnl-page [data-york-role="job-card"]>div{display:flex!important;flex-direction:row!important;align-items:center;justify-content:space-between;gap:24px!important}
.fnl-page [data-york-city="footer"] .fnl-column,.fnl-page [data-york-city="footer"] nav{color:#f6f4f0}
.fnl-page [data-york-city="footer"] p{max-width:none!important}
.fnl-page [data-york-city="footer"]>.fnl-section-inner>.fnl-row:last-child .fnl-block[data-york-role="paragraph"]{max-width:100%!important}
.fnl-page [data-york-city="footer"] .fnl-row:first-child>.fnl-column:first-child> .fnl-block[data-york-role="paragraph"]{max-width:380px!important}
.fnl-page [data-york-role="newsletter"] input{background:#ffffff14!important;color:#fff!important;border:0!important;border-radius:100px!important;padding:13px 20px!important;font:inherit!important;min-width:0;box-shadow:none!important}
.fnl-page [data-york-role="newsletter"] label{display:none!important}
.fnl-page [data-york-role="newsletter"] form{flex-wrap:nowrap!important;align-items:center!important;gap:8px!important;max-width:370px;margin-top:4px}
.fnl-page [data-york-role="newsletter"] form>div:first-child{flex:1;min-width:0}
@keyframes fnl-york-enter{from{opacity:.6;transform:translateY(12px)}to{opacity:1;transform:none}}
@supports(animation-timeline:view()){.fnl-page:not(.fnl-editable) [data-york-city]:not([data-york-city="header"]):not([data-york-city="hero"]):not([data-york-city="about-hero"]){animation:fnl-york-enter linear both;animation-timeline:view();animation-range:entry 0% cover 20%}}
@media(max-width:1000px){.fnl-page [data-york-role="main-nav"]>div{gap:20px!important}.fnl-page [data-york-role="service-card"] img{height:190px!important}.fnl-page [data-york-role="person"] img{height:200px!important}}
@media(max-width:640px){
.fnl-page [data-york-role="nav-pill"]{padding:10px 14px!important}.fnl-page [data-york-role="nav-pill"]>div{gap:10px!important}.fnl-page [data-york-role="nav-pill"]>div>.fnl-block:first-child{flex-basis:105px}.fnl-page [data-york-role="nav-pill"]>div>.fnl-block:last-child a{padding:10px 14px!important;font-size:12px!important}.fnl-page [data-york-role="main-nav"]>div{gap:0!important}.fnl-page [data-york-role="main-nav"]>div>.fnl-block:last-child{display:none}.fnl-page [data-york-role="page-menu"]{min-width:42px}.fnl-page [data-york-role="page-menu"] h3 button{font-size:12px!important}.fnl-page [data-york-role="page-menu"]>div>div>div:not([hidden]){left:auto;right:-60px;width:230px}
.fnl-page [data-york-city="hero"]:after,.fnl-page [data-york-city="about-hero"]:after{background:linear-gradient(0deg,#161514e8 10%,transparent 95%)}
.fnl-page [data-york-city="hero"] [data-york-role="heading"]{font-size:44px!important}
.fnl-page [data-york-role="hero-actions"]>div{gap:10px!important;flex-wrap:wrap!important}
.fnl-page [data-york-role="quick-carousel"]>div>div:last-child:not(.fnl-carousel-track){top:-54px}
.fnl-page [data-york-role="alerts"]:after{background:linear-gradient(0deg,#161514e8,transparent)}
.fnl-page [data-york-role="event-copy"]>div{grid-template-columns:1fr;gap:12px!important}
.fnl-page [data-york-role="events-home"]>div{flex-direction:column!important;gap:24px!important}.fnl-page [data-york-role="events-home"] [role="tablist"]{flex-direction:row!important;flex-wrap:wrap;gap:14px 20px!important;flex-basis:auto}
.fnl-page [data-york-role="events-filter"] [data-york-role="event-stack"]>div{grid-template-columns:1fr}
.fnl-page [data-york-role="news-small"]>div{grid-template-columns:105px minmax(0,1fr);gap:14px!important}
.fnl-page [data-york-role="person"] img{height:220px!important}
.fnl-page [data-york-city="team"]>.fnl-section-inner>.fnl-row:not(:first-child)>.fnl-column{grid-column:span 6/span 6}
.fnl-page [data-york-role="person"] img{height:180px!important}
.fnl-page [data-york-city="faq"] [data-york-role="faq"] button{font-size:15px!important}
.fnl-page [data-york-role="job-card"]>div{flex-direction:column!important;align-items:flex-start}
.fnl-page [data-york-city="footer"] .fnl-row:first-child>.fnl-column:not(:first-child){grid-column:span 6/span 6}
.fnl-page [data-york-city="footer"] .fnl-row:nth-child(2){margin:45px 0 35px!important}
}
@media(prefers-reduced-motion:reduce){.fnl-page [data-york-city],.fnl-page [data-york-city] *,.fnl-page [data-york-city]::after{animation:none!important;transition:none!important}}
`;
