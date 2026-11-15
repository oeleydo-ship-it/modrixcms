/**
 * Finishing rules for the Lumikin collection (sections tagged
 * `settings.designCollection = "lumikin"`): what block styles cannot express. The
 * Parkinsans font face is declared by the Edefy stylesheet, which every page loads.
 */
export const LUMIKIN_CSS = `
/* Each row sets its own width (up to 1560px, with the section padding as side margins), so content grows with the screen instead of stopping at the page width. */
.fnl-page [data-lumikin]>.fnl-section-inner{max-width:none!important}
.fnl-page [data-lumikin] h1,.fnl-page [data-lumikin] h2,.fnl-page [data-lumikin] h3{margin:0}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="heading"] a{color:inherit;text-decoration:none}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="heading"] a:hover{color:var(--site-primary,#9a233d)}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="paragraph"] a{color:inherit;text-decoration:none}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="paragraph"] a:hover{color:var(--site-primary,#9a233d)}
.fnl-page [data-lumikin] nav a{color:inherit;text-decoration:none;transition:color .2s}
.fnl-page [data-lumikin] nav a:hover{color:var(--site-primary,#9a233d)}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="logo"] img{height:var(--fnl-height,34px)!important;max-height:none!important;width:auto!important}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="logo"] span *{font-size:inherit!important;font-weight:600!important;letter-spacing:-0.04em!important}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="icon"]{display:flex!important;align-items:center;justify-content:center;flex:none;box-sizing:border-box;overflow:hidden;line-height:0}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="icon"]>span{display:flex;align-items:center;justify-content:center}
/* An icon circle beside text takes exactly its own width; the text gets the rest. */
.fnl-page [data-lumikin] .fnl-nested-columns:has(>.fnl-block[data-fnl-type="icon"]:first-child){grid-template-columns:auto minmax(0,1fr)!important}
.fnl-page [data-lumikin="story"]>.fnl-section-inner .fnl-block[data-fnl-type="image"] img{aspect-ratio:1/1;height:auto!important;border-radius:50%}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="social_icons"] a{border-color:rgba(0,0,0,.2)!important;transition:background-color .2s,color .2s}
.fnl-page [data-lumikin] .fnl-block[data-fnl-type="social_icons"] a:hover{background:var(--site-primary,#9a233d);border-color:var(--site-primary,#9a233d)!important;color:#fff}
/* Inner-page heroes: the photo fades to white behind the text. */
.fnl-page :is([data-lumikin="programs-hero"],[data-lumikin="fees-hero"],[data-lumikin="blog-hero"],[data-lumikin="contact-hero"])::after{content:"";position:absolute;inset:0;z-index:0;pointer-events:none;background:linear-gradient(90deg,#fff 0%,rgba(255,255,255,.88) 32%,rgba(255,255,255,0) 68%)}
@media (max-width:767px){.fnl-page :is([data-lumikin="programs-hero"],[data-lumikin="fees-hero"],[data-lumikin="blog-hero"],[data-lumikin="contact-hero"])::after{background:rgba(255,255,255,.78)}}
/* Activity cards stack on top of each other while scrolling. */
.fnl-page [data-lumikin="activities"]>.fnl-section-inner>.fnl-row:not(:first-child){position:sticky;top:96px;z-index:1}
/* Each card's "View all programs" button fills the width under its text card. */
.fnl-page [data-lumikin="activities"] .fnl-block[data-fnl-type="button"],.fnl-page [data-lumikin="activities"] .fnl-block[data-fnl-type="button"] a{width:100%;justify-content:center}
/* Review slider: arrows sit on the review card. */
.fnl-page [data-lumikin="testimonials"] .fnl-carousel-track+div{position:absolute;right:40px;bottom:40px;margin:0!important;z-index:2}
.fnl-page [data-lumikin="testimonials"] .fnl-carousel-track+div button{width:40px!important;height:40px!important;border-radius:50%!important;background:#fff!important;border:0!important;color:#111!important}
/* FAQ cards. */
.fnl-page [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"]>div>div{border-bottom:0!important;border-radius:20px;background:var(--site-surface,#f6f2ed);padding:18px 28px;margin-bottom:14px}
.fnl-page [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"]>div>div:has(button[aria-expanded="true"]),.fnl-page.fnl-editable [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"]>div>div:first-of-type{background:var(--site-primary,#9a233d);color:#fff}
.fnl-page [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"]>div>div:has(button[aria-expanded="true"]) :is(button,p),.fnl-page.fnl-editable [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"]>div>div:first-of-type :is(h3,p){color:#fff!important}
.fnl-page [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"] :is(h3,button){font-size:18px!important;font-weight:500!important;letter-spacing:-.5px;color:var(--site-heading,#111)}
.fnl-page [data-lumikin="faq"] .fnl-block[data-fnl-type="faq"] p{font-size:16px;line-height:1.75;color:var(--site-body,#4d4d4d)}
@media (max-width:767px){
.fnl-page [data-lumikin="activities"]>.fnl-section-inner>.fnl-row{position:relative!important;top:auto!important}
.fnl-page [data-lumikin="testimonials"] .fnl-carousel-track+div{right:24px;bottom:24px}
}
.fnl-page[data-fnl-breakpoint="mobile"] [data-lumikin="activities"]>.fnl-section-inner>.fnl-row{position:relative!important;top:auto!important}
`;
