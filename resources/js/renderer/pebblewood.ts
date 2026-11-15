/**
 * Finishing rules for the Pebblewood collection (sections tagged
 * `settings.designCollection = "pebblewood"`). Everything else is ordinary block
 * styling; these cover what block styles cannot express. The Parkinsans font
 * face itself is declared by the Edefy stylesheet, which every page loads.
 */
export const PEBBLEWOOD_CSS = `
.fnl-page [data-pebblewood] h1,.fnl-page [data-pebblewood] h2,.fnl-page [data-pebblewood] h3,.fnl-page [data-pebblewood] h6{margin:0}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="heading"] a{color:inherit;text-decoration:none}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="paragraph"] a{color:var(--site-accent,#ef7d45);text-decoration:underline;text-underline-offset:3px}
.fnl-page :is([data-pebblewood^="header"],[data-pebblewood="hero"]) .fnl-block[data-fnl-type="paragraph"] a{color:inherit;font-weight:600}
.fnl-page :is([data-pebblewood^="header"],[data-pebblewood="hero"],[data-pebblewood="footer"]) nav a{color:inherit;text-decoration:none;transition:color .2s}
.fnl-page :is([data-pebblewood^="header"],[data-pebblewood="hero"],[data-pebblewood="footer"]) nav a:hover{color:var(--site-accent,#ef7d45)}
.fnl-page :is([data-pebblewood^="header"],[data-pebblewood="hero"],[data-pebblewood="footer"])>.fnl-section-inner{max-width:none!important}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="logo"] img{height:var(--fnl-height,40px)!important;max-height:none!important;width:auto!important}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="logo"] span *{font-size:inherit!important;font-weight:700!important;letter-spacing:-0.04em!important}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="social_icons"] a{background:currentColor;transition:transform .2s}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="social_icons"] a svg{color:#ffffff}
.fnl-page [data-pebblewood] .fnl-block[data-fnl-type="social_icons"] a:hover{transform:translateY(-2px)}
.fnl-page [data-pebblewood="spaces"] [role="tablist"]{display:flex!important;flex-wrap:wrap;justify-content:center;padding:0!important}
.fnl-page [data-pebblewood="spaces"] [role="tab"]{padding:12px 22px!important;flex:0 0 auto}
.fnl-page [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"]>div>div{border-bottom:0!important;border-radius:32px;padding:14px 40px;transition:background-color .25s}
.fnl-page [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"]>div>div:has(button[aria-expanded="true"]),.fnl-page.fnl-editable [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"]>div>div:first-of-type{background:var(--site-surface,#fbf3e2);padding:30px 40px}
.fnl-page [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"] h3,.fnl-page [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"] button{font-size:18px!important;font-weight:600!important;color:var(--site-heading,#1f1d3d)}
.fnl-page [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"] p{color:var(--site-body,#5a5668);font-size:15px;line-height:1.7}
@media (max-width:640px){
.fnl-page [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"]>div>div{padding:12px 20px!important}
}
.fnl-page[data-fnl-breakpoint="mobile"] [data-pebblewood="faq"] .fnl-block[data-fnl-type="faq"]>div>div{padding:12px 20px!important}
`;
