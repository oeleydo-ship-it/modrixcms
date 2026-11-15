/**
 * Public renderer for pages made with the visual builder.
 *
 * The published document is embedded in the page by resources/views/builder-page.blade.php,
 * and the same renderer that paints the builder canvas paints it here — what the admin
 * edits is what ships.
 */
import { createRoot } from 'react-dom/client';
import { PageRenderer } from './renderer/PageRenderer';
import { normaliseDocument } from './renderer/document';
import { normaliseFunnelRuntime } from './renderer/funnel';
import './shared/styles/app.css';

const root = document.getElementById('public-page');
const payload = document.getElementById('published-document');
const runtimePayload = document.getElementById('published-runtime');

if (root && payload?.textContent) {
    const runtime = runtimePayload?.textContent ? normaliseFunnelRuntime(JSON.parse(runtimePayload.textContent)) : null;
    createRoot(root).render(<PageRenderer document={normaliseDocument(JSON.parse(payload.textContent))} mode="static" runtime={runtime} />);
}

/**
 * Start the JavaScript authored in custom code blocks (only admins can add them).
 *
 * The renderer emits it inert (`type="text/fnl-custom-code"`), so it runs exactly once,
 * here, after the page is interactive.
 */
let customCodeRunning = false;
async function runCustomCode(): Promise<void> {
    if (customCodeRunning) return;
    customCodeRunning = true;
    try {
        const markers = document.querySelectorAll<HTMLScriptElement>('script[type="text/fnl-custom-code"]:not([data-fnl-ran])');
        for (const inert of markers) {
            inert.setAttribute('data-fnl-ran', 'true');
            try {
                const sources: HTMLScriptElement[] = [];
                if (inert.dataset['format'] === 'html') {
                    const template = document.createElement('template');
                    template.innerHTML = String(JSON.parse(inert.textContent || '""'));
                    sources.push(...template.content.querySelectorAll<HTMLScriptElement>('script'));
                } else {
                    const source = document.createElement('script');
                    source.textContent = inert.textContent ?? '';
                    sources.push(source);
                }
                let anchor: Element = inert;
                for (const source of sources) {
                    const type = (source.getAttribute('type') || '').trim().toLowerCase();
                    if (type && !['module', 'text/javascript', 'application/javascript', 'text/ecmascript', 'application/ecmascript'].includes(type)) continue;
                    if (source.hasAttribute('nomodule')) continue;
                    const script = document.createElement('script');
                    if (type) script.type = type;
                    for (const attribute of source.attributes) {
                        if (attribute.name.startsWith('data-') || ['integrity', 'crossorigin', 'referrerpolicy', 'id'].includes(attribute.name)) {
                            script.setAttribute(attribute.name, attribute.value);
                        }
                    }
                    const src = source.getAttribute('src');
                    if (src) {
                        const url = new URL(src, document.baseURI);
                        if (!['https:', 'http:'].includes(url.protocol)) continue;
                        script.src = url.href;
                        script.async = false;
                    } else {
                        script.textContent = source.textContent ?? '';
                    }
                    const loaded = src || type === 'module' ? new Promise<void>((resolve) => {
                        script.onload = () => resolve();
                        script.onerror = () => { console.warn('Custom code script failed to load.'); resolve(); };
                    }) : null;
                    const placement = inert.dataset['placement'];
                    if (placement === 'head') document.head.appendChild(script);
                    else if (placement === 'body_end') document.body.appendChild(script);
                    else { anchor.after(script); anchor = script; }
                    if (loaded) await loaded;
                }
            } catch (error) {
                console.warn('Unable to start a custom code block.', error);
            }
        }
    } finally {
        customCodeRunning = false;
        if (document.querySelector('script[type="text/fnl-custom-code"]:not([data-fnl-ran])')) {
            window.setTimeout(() => { void runCustomCode(); }, 0);
        }
    }
}

if (root) new MutationObserver(() => { void runCustomCode(); }).observe(root, { childList: true, subtree: true });
window.setTimeout(() => { void runCustomCode(); }, 0);
