import { CANDID_FONTS } from './candidFonts';

export const CANDID_CSS = `${CANDID_FONTS}
.fnl-page [data-candid],.fnl-page [data-candid] *{box-sizing:border-box}
.fnl-page [data-candid]{font-family:Goli,sans-serif}
.fnl-page [data-candid]>.fnl-section-inner{max-width:1320px}
.fnl-page [data-candid] h1,.fnl-page [data-candid] h2,.fnl-page [data-candid] h3,.fnl-page [data-candid] h4{font-size:inherit;line-height:inherit;font-weight:inherit;font-family:inherit;letter-spacing:inherit}
.fnl-page [data-candid] .fnl-block>figure{height:auto;border-radius:var(--fnl-border-radius,10px);overflow:hidden;margin:0}
.fnl-page [data-candid] .fnl-block>figure>a{display:block}
.fnl-page [data-candid] .fnl-block>figure img{object-fit:cover;border-radius:inherit}
.fnl-page [data-candid="header"],.fnl-page [data-candid="hero"],.fnl-page [data-candid="page-hero"],.fnl-page [data-candid="campaigns"],.fnl-page [data-candid="footer"]{width:calc(100% - 40px);margin-inline:20px}
.fnl-page [data-candid="header"]{margin-top:20px;border-bottom:1px solid #ffffff15}
.fnl-page [data-candid="header"] .fnl-column>.fnl-block>div{display:flex;align-items:center;justify-content:space-between;gap:24px!important;flex-wrap:nowrap!important}
.fnl-page [data-candid="header"] .fnl-column>.fnl-block>div>.fnl-block{min-width:0}
.fnl-page [data-candid="header"] nav ul{gap:28px!important;flex-wrap:nowrap!important}
.fnl-page [data-candid="header"] a{text-decoration:none}
.fnl-page [data-candid="header"] .fnl-column>.fnl-block>div>.fnl-block:first-child a{letter-spacing:-.6px}
.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:first-child{max-width:620px}
.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:first-child>.fnl-block:last-child>div{flex-wrap:wrap!important;gap:15px!important}
.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:nth-child(3) .fnl-block>figure{--fnl-height:145px}
.fnl-page [data-candid="priorities"] [role="tablist"],.fnl-page [data-candid="history"] [role="tablist"]{gap:0!important;justify-content:space-between;overflow-x:auto;margin-bottom:24px}
.fnl-page [data-candid="priorities"] [role="tab"],.fnl-page [data-candid="history"] [role="tab"]{font-size:22px!important;white-space:nowrap;padding:18px 22px!important;color:#091423!important}
.fnl-page [data-candid] [role="tab"][aria-selected="true"]{border-color:#2e3192!important;color:#2e3192!important}
.fnl-page [data-candid] [role="tabpanel"]>.fnl-block>div{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:center;gap:40px!important}
.fnl-page [data-candid] [role="tabpanel"]>.fnl-block>div>.fnl-block{min-width:0;width:100%}
.fnl-page [data-candid="campaigns"] button{color:inherit}
.fnl-page [data-candid="campaigns"] .fnl-block>figure img{object-position:center}
.fnl-page [data-candid="support"] input,.fnl-page [data-candid="support"] select,.fnl-page [data-candid="support"] textarea{font-family:inherit;border:1px solid #dadbe2;border-radius:5px;background:white;color:#091423;min-height:48px}
.fnl-page [data-candid="support"] button[type="submit"],.fnl-page [data-candid="footer"] button[type="submit"]{font-family:inherit;font-size:16px;border-radius:5px;padding:12px 30px;background:#091423;color:white;min-height:50px}
.fnl-page [data-candid="footer"] input{min-height:48px;font-family:inherit;color:#091423;background:#fff;border-radius:5px}
.fnl-page [data-candid="footer"] button[type="submit"]{background:white;color:#091423;border:1px solid #d0d4dd}
.fnl-page [data-candid="footer"] a{color:inherit}
.fnl-page [data-candid] a:focus-visible,.fnl-page [data-candid] button:focus-visible,.fnl-page [data-candid] input:focus-visible{outline:3px solid #8d91ee;outline-offset:4px}
@media(prefers-reduced-motion:no-preference){
.fnl-page [data-candid] a,.fnl-page [data-candid] button{transition:transform .25s,filter .25s}
.fnl-page [data-candid] a:hover{filter:brightness(1.1);transform:translateY(-2px)}
@supports(animation-timeline:view()){
@keyframes candid-reveal{from{opacity:.25;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
.fnl-page:not(.fnl-editable) [data-candid]:not([data-candid="header"]):not([data-candid="hero"]):not([data-candid="page-hero"])>.fnl-section-inner{animation:candid-reveal linear both;animation-timeline:view();animation-range:entry 0% entry 25%}
}}
@media(max-width:1100px){
.fnl-page [data-candid="header"] nav ul{gap:14px!important}
.fnl-page [data-candid="header"] .fnl-column>.fnl-block>div>.fnl-block:first-child{font-size:20px!important}
.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:first-child{grid-column:span 12/span 12;max-width:700px}
.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:nth-child(2),.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:nth-child(3){grid-column:span 6/span 6}
}
@media(max-width:800px){
.fnl-page [data-candid="header"] .fnl-column>.fnl-block>div>.fnl-block:last-child{display:none}
.fnl-page [data-candid] [role="tab"]{font-size:18px!important;padding:14px!important}
.fnl-page [data-candid] [role="tabpanel"]>.fnl-block>div{grid-template-columns:minmax(0,1fr);gap:26px!important}
}
@media(max-width:640px){
.fnl-page [data-candid] [role="tablist"][aria-orientation="horizontal"]{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));overflow-x:visible;align-items:stretch}
.fnl-page [data-candid] [role="tab"]{min-width:0;min-height:56px;white-space:normal;font-size:16px!important;line-height:1.35;padding:12px 8px!important;overflow-wrap:anywhere}
.fnl-page [data-candid="header"],.fnl-page [data-candid="hero"],.fnl-page [data-candid="page-hero"],.fnl-page [data-candid="campaigns"],.fnl-page [data-candid="footer"]{width:calc(100% - 24px);margin-inline:12px}
.fnl-page [data-candid="header"]{margin-top:12px;border-radius:20px 20px 0 0}
.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:nth-child(2),.fnl-page [data-candid="hero"] .fnl-row>.fnl-column:nth-child(3){grid-column:span 12/span 12}
.fnl-page [data-candid]>.fnl-section-inner>.fnl-row>.fnl-column{max-width:100%}
}
.fnl-page[data-fnl-breakpoint="mobile"] [data-candid] [role="tablist"][aria-orientation="horizontal"]{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));overflow-x:visible;align-items:stretch}
.fnl-page[data-fnl-breakpoint="mobile"] [data-candid] [role="tab"]{min-width:0;min-height:56px;white-space:normal;font-size:16px!important;line-height:1.35;padding:12px 8px!important;overflow-wrap:anywhere}
`;
