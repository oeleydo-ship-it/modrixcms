export const VISTAHAVEN_CSS = `
@font-face{font-family:Geist;font-style:normal;font-weight:400;font-display:swap;src:url(https://fonts.gstatic.com/s/geist/v5/gyBhhwUxId8gMGYQMKR3pzfaWI_RnOM4nQ.ttf) format('truetype')}
@font-face{font-family:Geist;font-style:normal;font-weight:500;font-display:swap;src:url(https://fonts.gstatic.com/s/geist/v5/gyBhhwUxId8gMGYQMKR3pzfaWI_RruM4nQ.ttf) format('truetype')}
@font-face{font-family:Geist;font-style:normal;font-weight:600;font-display:swap;src:url(https://fonts.gstatic.com/s/geist/v5/gyBhhwUxId8gMGYQMKR3pzfaWI_RQuQ4nQ.ttf) format('truetype')}
@font-face{font-family:Geist;font-style:normal;font-weight:700;font-display:swap;src:url(https://fonts.gstatic.com/s/geist/v5/gyBhhwUxId8gMGYQMKR3pzfaWI_Re-Q4nQ.ttf) format('truetype')}

[data-vistahaven] img{transition:transform .65s ease;object-fit:cover}
[data-vistahaven] .fnl-block:has(>img){overflow:hidden;border-radius:24px}
[data-vistahaven] .fnl-block:has(>img):hover img{transform:scale(1.035)}
[data-vistahaven] a{transition:opacity .2s ease}
[data-vistahaven] a:hover{opacity:.75}
[data-vistahaven="hero"]{width:calc(100% - 48px)!important;max-width:1800px!important;margin-inline:auto;overflow:hidden}
[data-vistahaven="hero"]>.fnl-section-inner{max-width:1600px!important}
[data-vistahaven="hero"] .fnl-row:first-child p{max-width:440px}
[data-vistahaven="hero"] .fnl-row:last-child{margin-top:38px;margin-bottom:0}
[data-vistahaven="header"]>.fnl-section-inner{max-width:1740px!important}
[data-vistahaven="header"] a,[data-vistahaven="header"] button{border-radius:100px}
[data-vistahaven="services"] .fnl-row:first-child,[data-vistahaven="agents"] .fnl-row:first-child,[data-vistahaven="contact"] .fnl-row:first-child{text-align:center;display:flex;justify-content:center}
[data-vistahaven="services"] .fnl-row:first-child .fnl-column,[data-vistahaven="agents"] .fnl-row:first-child .fnl-column,[data-vistahaven="contact"] .fnl-row:first-child .fnl-column{max-width:600px;align-items:center}
[data-vistahaven="offers"] .fnl-row:last-child,[data-vistahaven="about"] .fnl-row{align-items:center}
[data-vistahaven="partners"]{text-align:center}
[data-vistahaven="partners"] h3{color:#a7a7a7;font-size:22px;letter-spacing:-.05em}
[data-vistahaven="properties"] .fnl-column{gap:12px}
[data-vistahaven="properties"] h3,[data-vistahaven="journal"] h3,[data-vistahaven="agents"] h3{font-size:20px}
[data-vistahaven="contact"] .fnl-row:nth-child(2){max-width:620px;text-align:center}
[data-vistahaven="contact"] form{text-align:left;padding:24px;border-radius:24px;background:#f6fafb}
[data-vistahaven="contact"] form button[type="submit"]{border-radius:100px;background:#111;color:#fff;width:100%}
[data-vistahaven="contact"] .fnl-row:last-child{margin-top:24px;margin-bottom:0}
.fnl-page:not(.fnl-editable) [data-vistahaven="hero"]>.fnl-section-inner{animation:haven-enter .75s ease both}
@keyframes haven-enter{from{transform:translateY(24px)}to{transform:none}}
@supports(animation-timeline:view()){
.fnl-page:not(.fnl-editable) [data-vistahaven]:not([data-vistahaven="header"]):not([data-vistahaven="hero"])>.fnl-section-inner{animation:haven-enter linear both;animation-timeline:view();animation-range:entry 0% entry 30%}
}
@media(max-width:767px){
[data-vistahaven="hero"]{width:calc(100% - 24px)!important;border-radius:24px}
[data-vistahaven="hero"] .fnl-row:last-child{display:flex;flex-wrap:wrap;gap:20px}
[data-vistahaven="hero"] .fnl-row:last-child>.fnl-column{flex:1;min-width:80px}
[data-vistahaven="partners"] h3{font-size:18px}
}
@media(prefers-reduced-motion:reduce){[data-vistahaven] *,[data-vistahaven]>.fnl-section-inner{animation:none!important;transition:none!important}}

/* Contact composition: editable native cards, map and connected form. */
[data-vistahaven="contact-details"] .fnl-row:first-child{text-align:center;margin-bottom:48px}
[data-vistahaven="contact-details"] .fnl-row:first-child>.fnl-column{align-items:center}
[data-vistahaven="contact-details"] h1{max-width:600px}
[data-vistahaven="contact-details"] .fnl-row:nth-child(2){gap:20px}
[data-vistahaven="contact-details"] .fnl-row:nth-child(2)>.fnl-column>.fnl-block{height:100%;min-height:210px}
[data-vistahaven="contact-details"] iframe{border-radius:28px}
[data-vistahaven="contact"]{max-width:none!important;width:100%!important;background-color:#f5f5f2;background-image:none}
[data-vistahaven="contact"]>.fnl-section-inner{max-width:1180px!important}
[data-vistahaven="contact"] .fnl-row:first-child{margin-bottom:38px}
[data-vistahaven="contact"] .fnl-row:nth-child(2){max-width:680px;margin-inline:auto}
[data-vistahaven="contact"] .fnl-row:nth-child(2)>.fnl-column{gap:38px}
[data-vistahaven="contact"] form{padding:34px 28px 42px;border:1px solid #d9d9d9;border-radius:12px;background:#fff;box-shadow:none}
[data-vistahaven="contact"] form input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),[data-vistahaven="contact"] form textarea{background:transparent;border:0;border-bottom:1px solid #ccc;border-radius:0;box-shadow:none;color:#2c2c2c;padding:12px 6px}
[data-vistahaven="contact"] form textarea{min-height:100px;resize:vertical}
[data-vistahaven="contact"] form input:focus-visible,[data-vistahaven="contact"] form textarea:focus-visible{outline:2px solid #666;outline-offset:3px}
[data-vistahaven="contact"] form label{font-size:13px;color:#666;font-weight:400}
[data-vistahaven="contact"] form button[type="submit"]{width:100%;border-radius:100px;background:#000;color:#fff;min-height:42px;border:0;font-weight:500}
[data-vistahaven="contact"] + [data-vistahaven="footer"]{background-color:#000}
@media(max-width:767px){
[data-vistahaven="contact-details"] .fnl-row:first-child{margin-bottom:30px}
[data-vistahaven="contact-details"] .fnl-row:nth-child(2)>.fnl-column>.fnl-block{min-height:170px}
[data-vistahaven="contact"] form{padding:26px 20px 30px}
[data-vistahaven="contact"]{background-position:60% bottom}
}
`;
