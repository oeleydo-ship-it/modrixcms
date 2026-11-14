import { useEffect, useMemo, useRef, useState } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { PageRenderer } from '../../renderer/PageRenderer';
import { normaliseDocument } from '../../renderer/document';
import { previewDocument, previewPages, previewForms, previewStepRuntime } from './preview';
import { previewFlowInteraction } from './previewInteraction';
import { FunnelProvider, normaliseForms } from '../../renderer/funnel';

export interface TemplatePreviewProps { payload: unknown; type: string; compact?: boolean }

const interaction = `
function notice(text){let el=document.getElementById('preview-feedback');el.textContent=text;el.hidden=false;}
${previewFlowInteraction}
function selectTab(button){const list=button.closest('[role="tablist"]');if(!list)return;const vertical=list.getAttribute('aria-orientation')==='vertical';list.querySelectorAll('[role="tab"]').forEach(function(tab){const selected=tab===button;tab.setAttribute('aria-selected',String(selected));tab.tabIndex=selected?0:-1;tab.style.fontWeight=selected?'600':'400';tab.style.opacity=selected?'1':'0.7';if(!vertical)tab.style.borderBottomColor=selected?'currentColor':'transparent';const panel=document.getElementById(tab.getAttribute('aria-controls'));if(panel)panel.hidden=!selected;});}
document.addEventListener('keydown',function(event){const button=event.target.closest('[role="tab"]');if(!button)return;const list=button.closest('[role="tablist"]');const tabs=Array.from(list.querySelectorAll('[role="tab"]'));const vertical=list.getAttribute('aria-orientation')==='vertical';const forward=vertical?'ArrowDown':'ArrowRight';const backward=vertical?'ArrowUp':'ArrowLeft';let next=tabs.indexOf(button);if(event.key===forward)next=(next+1)%tabs.length;else if(event.key===backward)next=(next-1+tabs.length)%tabs.length;else if(event.key==='Home')next=0;else if(event.key==='End')next=tabs.length-1;else return;event.preventDefault();selectTab(tabs[next]);tabs[next].focus();});
document.addEventListener('click',function(event){
 const link=event.target.closest('a');
 if(link){event.preventDefault();const href=link.getAttribute('href')||'';
  if(/^#preview-step-[0-9]+$/.test(href)){parent.postMessage({kind:'template-preview-step',index:Number(href.slice(14))},'*');return;}
  if(href==='#preview-submit'){const form=document.querySelector('form');if(form)form.requestSubmit();else notice('Demo submission successful. No information was sent or saved.');return;}
  if(href==='#preview-complete'){notice('You reached the end of this preview.');return;}
  if(href.startsWith('#')&&!href.startsWith('#preview-')){const target=document.getElementById(href.slice(1))||document.querySelector('[data-fnl-id="'+CSS.escape(href.slice(1))+'"]');if(target){target.scrollIntoView({behavior:'smooth'});return;}}
  notice('Preview only. External links, bookings and payments are disabled.');return;
 }
 const button=event.target.closest('button');
 if(button && button.hasAttribute('data-fnl-scroll-target')){event.preventDefault();const id=button.getAttribute('data-fnl-scroll-target');const target=id?document.querySelector('[data-fnl-id="'+CSS.escape(id)+'"]'):document.querySelector('form');if(target){target.scrollIntoView({behavior:'smooth',block:'start'});const field=target.querySelector('input,textarea,select');if(field)field.focus({preventScroll:true});}return;}
 if(button && button.classList.contains('fnl-nav-toggle')){event.preventDefault();const nav=button.closest('nav');const open=nav.getAttribute('data-open')!=='true';nav.setAttribute('data-open',String(open));button.setAttribute('aria-expanded',String(open));button.setAttribute('aria-label',open?'Close menu':'Open menu');button.style.marginBottom=open?'12px':'0';return;}
 if(button && button.getAttribute('role')==='tab'){event.preventDefault();selectTab(button);return;}
 if(button && /^(Previous slide|Next slide|Go to slide [0-9]+)$/.test(button.getAttribute('aria-label')||'')) {
  const root=button.parentElement.parentElement;const track=root.querySelector('[aria-roledescription="carousel"]');
  if(track){event.preventDefault();const step=track.clientWidth+12;const pages=Math.max(1,Math.ceil((track.scrollWidth+12)/step));const current=Math.round(track.scrollLeft/step);const label=button.getAttribute('aria-label');let next=label==='Next slide'?current+1:label==='Previous slide'?current-1:Number(label.split(' ').pop())-1;next=(next+pages)%pages;track.scrollTo({left:next*step,behavior:'auto'});return;}
 }

 if(button && button.hasAttribute('aria-expanded') && button.hasAttribute('aria-controls')) {event.preventDefault();const panel=document.getElementById(button.getAttribute('aria-controls'));if(panel){const open=button.getAttribute('aria-expanded')!=='true';button.setAttribute('aria-expanded',String(open));panel.hidden=!open;}return;}
 if(button && button.type!=='submit') {event.preventDefault();notice('Preview only. Connect this action after installing the template.');}
},true);`;

export function TemplatePreview({ payload, type, compact = false }: TemplatePreviewProps) {
    const pages = useMemo(() => { try { return previewPages(payload, type); } catch { return []; } }, [payload, type]);
    const [selected, setSelected] = useState(0);
    const [mobile, setMobile] = useState(false);
    const [thumbnailScale, setThumbnailScale] = useState(0.28);
    const thumbnail = useRef<HTMLDivElement>(null);
    const frame = useRef<HTMLIFrameElement>(null);
    const active = compact ? 0 : Math.min(selected, Math.max(0, pages.length - 1));
    useEffect(() => { setSelected(0); }, [payload, type]);
    useEffect(() => {
        const element = thumbnail.current;
        if (!compact || !element) return;
        const update = () => setThumbnailScale(element.getBoundingClientRect().width / 1200);
        update();
        const observer = new ResizeObserver(update);
        observer.observe(element);
        return () => observer.disconnect();
    }, [compact, payload, type]);
    useEffect(() => {
        const listener = (event: MessageEvent) => {
            if (!compact && event.source === frame.current?.contentWindow && event.data?.kind === 'template-preview-step' && Number.isInteger(event.data.index) && event.data.index >= 0 && event.data.index < pages.length) setSelected(event.data.index);
        };
        window.addEventListener('message', listener);
        return () => window.removeEventListener('message', listener);
    }, [compact, pages.length]);
    const html = useMemo(() => {
        try {
        const document = normaliseDocument(previewDocument(pages[active]?.document ?? {}, pages, active));
        const nonce = Array.from(crypto.getRandomValues(new Uint8Array(16)), value => value.toString(16).padStart(2, '0')).join('');
        const body = renderToStaticMarkup(<FunnelProvider value={{ templatePreview: true, ...previewStepRuntime(pages, active), commerce: null, forms: normaliseForms(previewForms(document)) }}><PageRenderer document={document} mode="static" /></FunnelProvider>);
        return `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; script-src 'nonce-${nonce}'; style-src 'unsafe-inline'; img-src https: http: data:; font-src https: http: data:; connect-src 'none'; form-action 'none'; base-uri 'none'; frame-src 'none'"><style>*,::before,::after{box-sizing:border-box}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}body{line-height:1.5;margin:0;font-family:Inter,Arial,sans-serif;color:#172033;background:white}#preview-feedback{position:fixed;bottom:16px;left:16px;right:16px;z-index:2147483647;padding:14px 18px;border:1px solid #c7d2fe;border-radius:10px;background:#eef2ff;color:#312e81;font:14px/1.5 Arial;box-shadow:0 4px 20px #0002}</style></head><body>${body}<div id="preview-feedback" role="status" aria-live="polite" hidden></div>${compact ? '' : `<script nonce="${nonce}">${interaction}</script>`}</body></html>`;
        } catch {
            return null;
        }
    }, [pages, active, compact]);
    if (html === null) return <div role="status" className="p-8 text-center text-sm text-slate-500">This template could not be previewed. Check its document format before importing.</div>;
    if (!pages.length) return <div className="p-8 text-center text-sm text-slate-500">This template has no pages to preview.</div>;
    if (compact) return <div ref={thumbnail} className="relative h-full min-h-36 w-full overflow-hidden bg-white" aria-hidden="true"><iframe title="Template thumbnail" srcDoc={html} sandbox="" tabIndex={-1} loading="lazy" className="pointer-events-none absolute left-0 top-0 h-[760px] w-[1200px] origin-top-left border-0" style={{ transform: `scale(${thumbnailScale})` }} /></div>;
    return <div className="space-y-3">
        <div className="flex flex-wrap items-center justify-between gap-3">
            <label className="flex items-center gap-2 text-sm">Page <select aria-label="Preview page" value={active} onChange={event => setSelected(Number(event.target.value))} className="max-w-64 rounded-lg border border-slate-200 bg-white px-3 py-2">{pages.map((page, index) => <option key={index} value={index}>{index + 1}. {page.name}</option>)}</select></label>
            <div className="flex gap-1 rounded-lg bg-slate-100 p-1">{['Desktop', 'Mobile'].map((label, index) => <button key={label} type="button" aria-pressed={mobile === Boolean(index)} onClick={() => setMobile(Boolean(index))} className={`rounded-md px-3 py-1.5 text-sm ${mobile === Boolean(index) ? 'bg-white shadow-sm' : 'text-slate-500'}`}>{label}</button>)}</div>
        </div>
        <p className="text-xs text-slate-500">Try the complete step flow with demo forms, bookings and checkout. No data, appointments or payments are saved.</p>
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-slate-100 p-2"><iframe key={`${active}-${mobile ? 'mobile' : 'desktop'}`} ref={frame} title={`${pages[active]?.name} — template preview`} sandbox="allow-scripts allow-forms" srcDoc={html} className="mx-auto block h-[65vh] min-h-96 border-0 bg-white" style={{ width: mobile ? '390px' : '100%', maxWidth: '100%' }} /></div>
    </div>;
}
