export const SOLENNE_CSS = `
[data-solenne]{font-family:Inter,sans-serif}
[data-solenne] h1,[data-solenne] h2,[data-solenne] h3,[data-solenne] h4{font-family:"Inter Tight",sans-serif}
[data-solenne] img{object-fit:cover;transition:transform .75s cubic-bezier(.2,.65,.25,1)}
[data-solenne] .fnl-block:has(>figure){overflow:hidden}
[data-solenne] .fnl-block:has(>figure):hover img{transform:scale(1.035)}
[data-solenne] a,[data-solenne] button{transition:transform .25s ease,opacity .25s ease}
[data-solenne] a:hover,[data-solenne] button:hover{transform:translateY(-2px);opacity:.82}
[data-solenne="header"]{position:sticky!important;top:14px!important;z-index:30;background:transparent!important;pointer-events:none}
[data-solenne="header"]>.fnl-section-inner{max-width:930px!important;pointer-events:auto}
[data-solenne="header"] .fnl-row{background:#f5f5f3;border-radius:6px;padding:8px 10px;box-shadow:none!important}
[data-solenne="hero"]{margin-top:-86px;overflow:hidden}
[data-solenne="hero"]>.fnl-section-inner{max-width:1280px!important}
[data-solenne="hero"] .fnl-column{max-width:650px}
[data-solenne="overview"] .fnl-row:first-child{align-items:center}
[data-solenne="overview"] .fnl-row:last-child{max-width:760px;margin-left:auto}
[data-solenne="featured-properties"] .fnl-row:first-child,[data-solenne="properties"] .fnl-row:first-child{align-items:end}
[data-solenne="featured-properties"] .fnl-column>.fnl-block:first-child,[data-solenne="properties"] .fnl-column>.fnl-block:first-child{position:relative}
[data-solenne="trust"] .fnl-row,[data-solenne="contact"] .fnl-row{align-items:center}
[data-solenne="process"] .fnl-row:first-child>.fnl-column,[data-solenne="team"] .fnl-row:first-child>.fnl-column{max-width:760px;margin-inline:auto;align-items:center}
[data-solenne="image-cta"]>.fnl-section-inner{max-width:900px!important}
[data-solenne="story"] .fnl-row{align-items:start}
[data-solenne="contact"] form{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:18px;margin-top:34px}
[data-solenne="contact"] form input,[data-solenne="contact"] form textarea{background:#f5f5f3;border:0;border-radius:8px;padding:16px}
[data-solenne="contact"] form textarea{min-height:190px}
[data-solenne="contact"] form button[type="submit"]{background:#151515;color:#fff;border:0;border-radius:6px;min-height:48px;width:100%}
[data-solenne="footer"]>.fnl-section-inner{max-width:1400px!important}
[data-solenne="footer"] h1{white-space:nowrap;overflow:hidden;line-height:.85}
.fnl-page:not(.fnl-editable) [data-solenne="header"]>.fnl-section-inner{animation:solenne-down .65s ease both}
.fnl-page:not(.fnl-editable) [data-solenne="hero"] .fnl-column{animation:solenne-up .85s .12s ease both}
.fnl-page:not(.fnl-editable) [data-solenne="hero"]::before{animation:solenne-zoom 14s ease-out both}
@supports(animation-timeline:view()){
.fnl-page:not(.fnl-editable) [data-solenne]:not([data-solenne="header"]):not([data-solenne="hero"])>.fnl-section-inner{animation:solenne-up linear both;animation-timeline:view();animation-range:entry 0% entry 28%}
}
@keyframes solenne-down{from{opacity:0;transform:translateY(-18px)}to{opacity:1;transform:none}}
@keyframes solenne-up{from{opacity:0;transform:translateY(34px)}to{opacity:1;transform:none}}
@keyframes solenne-zoom{from{transform:scale(1.06)}to{transform:scale(1)}}
@media(max-width:767px){
[data-solenne="header"]{top:8px!important}
[data-solenne="header"]>.fnl-section-inner{padding-inline:8px}
[data-solenne="hero"]{margin-top:-74px}
[data-solenne="overview"] .fnl-row:last-child{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:10px}
[data-solenne="overview"] .fnl-row:last-child>.fnl-column{grid-column:span 1!important}
[data-solenne="contact"] form{grid-template-columns:1fr}
[data-solenne="contact"] form>*{grid-column:1!important}
[data-solenne="footer"] h1{font-size:52px!important}
}
@media(prefers-reduced-motion:reduce){[data-solenne] *,[data-solenne]::before,[data-solenne]>.fnl-section-inner{animation:none!important;transition:none!important}}
`;
