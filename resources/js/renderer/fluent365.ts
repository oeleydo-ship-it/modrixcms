export const FLUENT_CSS = `
.fnl-page [data-fluent],.fnl-page [data-fluent] *{box-sizing:border-box}
.fnl-page [data-fluent]{font-family:"Segoe UI Variable Display","Segoe UI Variable Text","Segoe UI",-apple-system,BlinkMacSystemFont,"Roboto","Helvetica Neue",sans-serif;color:#171717}
.fnl-page [data-fluent]>.fnl-section-inner{max-width:1320px;margin-inline:auto}
.fnl-page [data-fluent] h1,.fnl-page [data-fluent] h2,.fnl-page [data-fluent] h3,.fnl-page [data-fluent] h4{font-family:inherit;letter-spacing:-0.018em}
.fnl-page [data-fluent] .fnl-block>figure{margin:0;overflow:hidden;border-radius:var(--fnl-border-radius,16px)}
.fnl-page [data-fluent] .fnl-block>figure img{width:100%;height:100%;object-fit:cover;display:block}
.fnl-page [data-fluent="header"]{border-bottom:1px solid #e5e7eb;background:#ffffff!important}
.fnl-page [data-fluent="header"]>.fnl-section-inner{max-width:1440px}
.fnl-page [data-fluent="header"] nav ul{gap:24px!important;align-items:center}
.fnl-page [data-fluent="header"] a{font-size:13px;color:#242424;text-decoration:none}
.fnl-page [data-fluent="header"] a:hover{text-decoration:underline}
.fnl-page [data-fluent="announcement"]{background:#091f2c!important;color:#ffffff!important}
.fnl-page [data-fluent="announcement"] p,.fnl-page [data-fluent="announcement"] a{color:#ffffff!important;text-align:center!important;margin-inline:auto!important;max-width:none!important}
.fnl-page [data-fluent="hero"]{
  background:
    radial-gradient(ellipse 55% 38% at 14% 92%, rgba(56,189,248,0.55) 0%, rgba(59,130,246,0.22) 48%, transparent 75%),
    radial-gradient(ellipse 52% 40% at 86% 88%, rgba(99,102,241,0.48) 0%, rgba(168,85,247,0.22) 50%, transparent 78%),
    radial-gradient(ellipse 48% 32% at 48% 86%, rgba(244,114,182,0.28) 0%, rgba(251,191,36,0.16) 52%, transparent 80%),
    linear-gradient(180deg, #faf7f2 0%, #f8f3ed 52%, #e6ecf8 82%, #d5e2f6 100%)!important;
  text-align:center;
}
.fnl-page [data-fluent="hero"] .fnl-column{align-items:center!important;text-align:center!important}
.fnl-page [data-fluent="hero"] h1,.fnl-page [data-fluent="hero"] p{text-align:center!important;margin-inline:auto!important}
.fnl-page [data-fluent="hero"] .fnl-row:first-child .fnl-column>.fnl-block:nth-child(4)>div{display:flex!important;justify-content:center!important;align-items:center!important;gap:10px!important;flex-wrap:wrap}
.fnl-page [data-fluent="hero"] .fnl-row:last-child>.fnl-column>.fnl-block>div{
  max-width:780px;
  margin:44px auto 0!important;
  padding:6px!important;
  background:#ffffff!important;
  border-radius:9999px!important;
  box-shadow:0 8px 28px rgba(9,31,44,0.14),0 1.5px 4px rgba(9,31,44,0.08)!important;
  display:grid!important;
  grid-template-columns:repeat(4,minmax(0,1fr))!important;
  gap:6px!important;
  align-items:center!important;
}
.fnl-page [data-fluent="hero"] .fnl-row:last-child a{
  width:100%!important;
  justify-content:center!important;
  border-radius:9999px!important;
  padding:11px 18px!important;
  font-size:14px!important;
  font-weight:600!important;
  text-decoration:none!important;
}
.fnl-page [data-fluent="subnav"]{border-bottom:1px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,0.05)}
.fnl-page [data-fluent="subnav"] nav ul{gap:28px!important}
.fnl-page [data-fluent="subnav"] nav li:first-child a{font-weight:600;border-bottom:2px solid #091f2c;padding-bottom:14px}
.fnl-page [data-fluent="copilot"] [role="tablist"]{gap:8px!important;border-left:2px solid #e5e7eb;padding-left:16px}
.fnl-page [data-fluent="copilot"] [role="tab"]{text-align:left!important;font-size:17px!important;font-weight:600!important;padding:14px 16px!important;border-radius:12px!important;color:#242424!important}
.fnl-page [data-fluent="copilot"] [role="tab"][aria-selected="true"]{background:#f5f2ec!important;color:#091f2c!important;box-shadow:inset 3px 0 0 #091f2c}
.fnl-page [data-fluent="ai-features"]{background:#f7f3ee!important}
.fnl-page [data-fluent="apps"]{background:linear-gradient(180deg,#dce8fa 0%,#e9effa 45%,#f4f7fc 100%)!important}
.fnl-page [data-fluent="apps"] [role="tablist"],.fnl-page [data-fluent="pricing"] [role="tablist"]{
  display:inline-flex!important;
  background:#ffffff!important;
  padding:4px!important;
  border-radius:9999px!important;
  box-shadow:0 2px 8px rgba(0,0,0,0.08)!important;
  gap:4px!important;
  margin-bottom:28px!important;
  border:none!important;
}
.fnl-page [data-fluent="apps"] [role="tab"],.fnl-page [data-fluent="pricing"] [role="tab"]{
  border-radius:9999px!important;
  padding:8px 20px!important;
  font-size:14px!important;
  font-weight:600!important;
  border:none!important;
  color:#091f2c!important;
}
.fnl-page [data-fluent="apps"] [role="tab"][aria-selected="true"],.fnl-page [data-fluent="pricing"] [role="tab"][aria-selected="true"]{
  background:#091f2c!important;
  color:#ffffff!important;
}
.fnl-page [data-fluent="pricing"]{
  background:linear-gradient(165deg,#f3d5ce 0%,#f5e3df 28%,#e6dff5 58%,#cde0f7 85%,#bfd6f4 100%)!important;
}
.fnl-page [data-fluent="next-step"]{
  background:
    radial-gradient(ellipse 60% 35% at 25% 95%, rgba(147,197,253,0.45) 0%, transparent 70%),
    radial-gradient(ellipse 55% 35% at 80% 95%, rgba(244,114,182,0.32) 0%, transparent 70%),
    linear-gradient(180deg,#ffffff 0%,#f8fafc 72%,#e6eef9 100%)!important;
}
.fnl-page [data-fluent] a,.fnl-page [data-fluent] button{transition:background-color 0.2s cubic-bezier(0.16,1,0.3,1),color 0.2s ease,transform 0.2s ease,box-shadow 0.2s ease}
.fnl-page [data-fluent="news"] .fnl-column>.fnl-block>div,.fnl-page [data-fluent="apps"] [role="tabpanel"] .fnl-block>div>.fnl-block>div,.fnl-page [data-fluent="next-step"] .fnl-column>.fnl-block>div{
  transition:transform 0.25s cubic-bezier(0.16,1,0.3,1),box-shadow 0.25s cubic-bezier(0.16,1,0.3,1);
}
.fnl-page [data-fluent="news"] .fnl-column>.fnl-block>div:hover,.fnl-page [data-fluent="apps"] [role="tabpanel"] .fnl-block>div>.fnl-block>div:hover,.fnl-page [data-fluent="next-step"] .fnl-column>.fnl-block>div:hover{
  transform:translateY(-4px);
  box-shadow:0 12px 28px rgba(9,31,44,0.12)!important;
}
@media(max-width:768px){
  .fnl-page [data-fluent="hero"] .fnl-row:last-child>.fnl-column>.fnl-block>div{grid-template-columns:repeat(2,minmax(0,1fr))!important;border-radius:20px!important}
}
`;
