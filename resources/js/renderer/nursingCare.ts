import { NURSING_CARE_FONTS } from './nursingCareFonts';

export const NURSING_CARE_CSS = `${NURSING_CARE_FONTS}
.fnl-page [data-nursing-care],.fnl-page [data-nursing-care] *{box-sizing:border-box}
.fnl-page [data-nursing-care]{font-family:Inter,sans-serif}
.fnl-page [data-nursing-care] h1,.fnl-page [data-nursing-care] h2,.fnl-page [data-nursing-care] h3,.fnl-page [data-nursing-care] h4{font-family:inherit;font-size:inherit;font-weight:inherit;line-height:inherit;letter-spacing:inherit}
.fnl-page [data-nursing-care]>.fnl-section-inner{max-width:1320px}
.fnl-page [data-nursing-care] .fnl-block>figure{height:auto;border-radius:var(--fnl-border-radius,20px);overflow:hidden;isolation:isolate}
.fnl-page [data-nursing-care] .fnl-block>figure>a{display:block}
.fnl-page [data-nursing-care] .fnl-block>figure img{width:100%;object-fit:cover;border-radius:inherit}
.fnl-page [data-nursing-care] .fnl-column{min-width:0}
.fnl-page [data-nursing-care="services"] .fnl-column>.fnl-block{height:100%}
/* Backdrop also repairs already-inserted documents with the old compound URL.
   A valid editable image on the native ::before layer still paints above it. */
.fnl-page [data-nursing-care="hero"]{background-color:#001125;background-image:url('/templates/nursing-care/hero.jpg')!important;background-size:cover;background-position:center}
.fnl-page [data-nursing-care="page-hero"]{background-color:#001125;background-image:url('/templates/nursing-care/support.jpg')!important;background-size:cover;background-position:center}
.fnl-page [data-nursing-care="hero"]::after,.fnl-page [data-nursing-care="page-hero"]::after{background:rgba(0,17,37,.62)!important}
.fnl-page [data-nursing-care="hero"]>.fnl-section-inner>.fnl-row:last-child{overflow:hidden}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child{align-items:center;column-gap:28px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(1),.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2){grid-column:span 3/span 3}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:first-child>.fnl-block{--fnl-height:460px;height:460px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2){position:relative;align-self:center;padding-top:72px;padding-bottom:44px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:first-child{--fnl-height:370px;height:370px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child{position:absolute;left:-90px;bottom:0;width:168px;height:168px;z-index:2}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child>div{width:100%;height:100%;padding:20px!important;border:8px solid #fafcff;border-radius:50%!important;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:4px!important;box-shadow:0 8px 28px rgba(0,53,136,.08)}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child h2{font-size:40px;line-height:1.1}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child p{font-size:14px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:last-child{padding-left:32px;gap:22px;max-width:650px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:last-child h2{font-size:clamp(34px,3.6vw,52px);line-height:1.12;letter-spacing:-1px;text-wrap:balance}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:last-child p{line-height:1.65}
@media(min-width:641px) and (max-width:1000px){
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(1),.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2){grid-column:span 6/span 6}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:last-child{grid-column:span 12/span 12;padding:24px 0 0;max-width:700px}
}
@media(max-width:640px){
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child{column-gap:14px;row-gap:32px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(1),.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2){grid-column:span 6/span 6}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:first-child>.fnl-block{--fnl-height:280px;height:280px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2){padding-top:40px;padding-bottom:30px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:first-child{--fnl-height:230px;height:230px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child{width:118px;height:118px;left:-66px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child>div{padding:10px!important;border-width:5px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child h2{font-size:30px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:nth-child(2)>.fnl-block:last-child p{font-size:11px}
.fnl-page [data-nursing-care="about"]>.fnl-section-inner>.fnl-row:first-child>.fnl-column:last-child{grid-column:span 12/span 12;padding-left:0;gap:18px}
}
.fnl-page [data-nursing-care="why"] figure img{object-position:center 35%}
.fnl-page [data-nursing-care="team"] figure img{object-position:center 25%}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div{align-items:center;justify-content:space-between;flex-wrap:nowrap!important;gap:24px!important}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div>.fnl-block{min-width:0}
.fnl-page [data-nursing-care="header"] nav ul{gap:30px!important}
.fnl-page [data-nursing-care="header"] a{text-decoration:none}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div>.fnl-block:nth-child(2) a:before{content:'+';font-size:42px;font-weight:700;line-height:1;margin-right:8px;vertical-align:middle}
.fnl-page [data-nursing-care="hero"],.fnl-page [data-nursing-care="page-hero"]{width:calc(100% - 100px);margin:0 50px;overflow:hidden}
.fnl-page [data-nursing-care="hero"]>.fnl-section-inner>.fnl-row:first-child .fnl-block[data-block-type="paragraph"]{max-width:550px}
.fnl-page [data-nursing-care="hero"]>.fnl-section-inner>.fnl-row:last-child p{white-space:normal;width:auto;font-family:'Instrument Sans',sans-serif;font-weight:600;line-height:1.5}
.fnl-page [data-nursing-care="page-hero"]:after{content:'';position:absolute;right:0;bottom:0;width:90px;height:90px;background:#fafcff;border-radius:20px 0 0 0;pointer-events:none}
.fnl-page [data-nursing-care] details{border-bottom:1px solid #ccd9ee;padding:20px 0}
.fnl-page [data-nursing-care] summary{font-family:'Instrument Sans',sans-serif;font-size:20px;font-weight:600;cursor:pointer}
.fnl-page [data-nursing-care] input,.fnl-page [data-nursing-care] select,.fnl-page [data-nursing-care] textarea{border-radius:12px;border-color:#ccdaef;background:#fafcff;color:#003588;font-family:Inter,sans-serif}
.fnl-page [data-nursing-care] button[type="submit"]{border-radius:50px;background:#003588;color:#fafcff;padding:12px 20px;font-size:18px}
.fnl-page [data-nursing-care] a:focus-visible,.fnl-page [data-nursing-care] button:focus-visible,.fnl-page [data-nursing-care] summary:focus-visible{outline:3px solid #266afb;outline-offset:4px}
@media(prefers-reduced-motion:no-preference){
.fnl-page [data-nursing-care] a{transition:transform .3s,filter .3s}
.fnl-page [data-nursing-care] a:hover{filter:brightness(1.16);transform:translateY(-2px)}
@supports(animation-timeline:view()){
@keyframes nursing-rise{from{opacity:.2;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.fnl-page:not(.fnl-editable) [data-nursing-care]:not([data-nursing-care="header"]):not([data-nursing-care="hero"]):not([data-nursing-care="page-hero"])>.fnl-section-inner{animation:nursing-rise linear both;animation-timeline:view();animation-range:entry 0% entry 25%}
}}
@media(max-width:1000px){
.fnl-page [data-nursing-care="header"] nav ul{gap:16px!important}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div{gap:16px!important}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div>.fnl-block:nth-child(2){font-size:24px!important}
.fnl-page [data-nursing-care="hero"],.fnl-page [data-nursing-care="page-hero"]{width:calc(100% - 60px);margin-inline:30px}
.fnl-page [data-nursing-care="hero"]>.fnl-section-inner>.fnl-row:last-child{margin:80px 0 -80px -40px!important}
}
@media(max-width:640px){
.fnl-page [data-nursing-care="hero"],.fnl-page [data-nursing-care="page-hero"]{width:calc(100% - 40px);margin-inline:20px}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div>.fnl-block:nth-child(2){font-size:22px!important}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div>.fnl-block:last-child{display:none}
.fnl-page [data-nursing-care="header"] .fnl-column>.fnl-block>div>.fnl-block:first-child{order:2}
.fnl-page [data-nursing-care="hero"]>.fnl-section-inner>.fnl-row:last-child{margin:65px 0 -65px -25px!important;max-width:calc(100% + 50px)!important}
.fnl-page [data-nursing-care="page-hero"]:after{width:45px;height:45px}
}
`;
