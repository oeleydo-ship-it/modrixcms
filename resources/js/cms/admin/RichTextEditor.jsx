import { useEffect, useRef, useState } from 'react';
import MediaLibraryModal from '@/cms/admin/MediaLibraryModal';

/**
 * A small rich text editor for the post body: headings, bold/italic/underline, lists, quotes,
 * links, images and a source view. It edits HTML; the server cleans it before saving.
 */

const BLOCKS = [
    ['p', 'Paragraph'],
    ['h2', 'Heading 2'],
    ['h3', 'Heading 3'],
    ['h4', 'Heading 4'],
    ['blockquote', 'Quote'],
    ['pre', 'Code block'],
];

const Icon = ({ children }) => (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{children}</svg>
);

const TOOLS = [
    { cmd: 'bold', title: 'Bold (Ctrl+B)', icon: <Icon><path d="M7 5h6a3.5 3.5 0 0 1 0 7H7zM7 12h7a3.5 3.5 0 0 1 0 7H7z" /></Icon> },
    { cmd: 'italic', title: 'Italic (Ctrl+I)', icon: <Icon><path d="M19 4h-9M14 20H5M15 4 9 20" /></Icon> },
    { cmd: 'underline', title: 'Underline (Ctrl+U)', icon: <Icon><path d="M6 4v7a6 6 0 0 0 12 0V4M4 20h16" /></Icon> },
    { cmd: 'strikeThrough', title: 'Strikethrough', icon: <Icon><path d="M16 6a4 4 0 0 0-4-2c-2.5 0-4 1.3-4 3 0 4 8 2 8 6 0 1.8-1.7 3-4 3a4.5 4.5 0 0 1-4.5-2.5M4 12h16" /></Icon> },
    'sep',
    { cmd: 'insertUnorderedList', title: 'Bulleted list', icon: <Icon><path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" /></Icon> },
    { cmd: 'insertOrderedList', title: 'Numbered list', icon: <Icon><path d="M10 6h10M10 12h10M10 18h10M4 5l1-1v4M4 14h2l-2 3h2" /></Icon> },
    { cmd: 'outdent', title: 'Decrease indent', icon: <Icon><path d="M3 6h18M11 12h10M3 18h18M7 9l-3 3 3 3" /></Icon> },
    { cmd: 'indent', title: 'Increase indent', icon: <Icon><path d="M3 6h18M11 12h10M3 18h18M4 9l3 3-3 3" /></Icon> },
    'sep',
    { cmd: 'insertHorizontalRule', title: 'Divider line', icon: <Icon><path d="M4 12h16" /></Icon> },
    { cmd: 'removeFormat', title: 'Clear formatting', icon: <Icon><path d="M4 7V4h16v3M9 20h6M12 4v16M3 3l18 18" /></Icon> },
    'sep',
    { cmd: 'undo', title: 'Undo', icon: <Icon><path d="M9 14 4 9l5-5M4 9h10a6 6 0 0 1 0 12h-3" /></Icon> },
    { cmd: 'redo', title: 'Redo', icon: <Icon><path d="m15 14 5-5-5-5M20 9H10a6 6 0 0 0 0 12h3" /></Icon> },
];

const plainTextToHtml = (text) => text
    .split(/\n{2,}/)
    .map((paragraph) => `<p>${paragraph.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')}</p>`)
    .join('');

export default function RichTextEditor({ value, onChange, placeholder = 'Start writing…' }) {
    const surface = useRef(null);
    const saved = useRef(null);
    const [source, setSource] = useState(false);
    const [ask, setAsk] = useState(null); // { kind: 'link' | 'image', url, alt }
    const [block, setBlock] = useState('p');
    const [library, setLibrary] = useState(false);

    // Load the content once, and again only when it is replaced from outside (for example by the AI writer).
    useEffect(() => {
        const node = surface.current;
        if (node && node.innerHTML !== (value ?? '')) {
            node.innerHTML = value ?? '';
        }
    }, [value, source]);

    const emit = () => {
        const html = surface.current?.innerHTML ?? '';
        onChange(html === '<br>' ? '' : html);
    };

    const exec = (command, argument = null) => {
        surface.current?.focus();
        document.execCommand(command, false, argument);
        emit();
    };

    const remember = () => {
        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0 && surface.current?.contains(selection.anchorNode)) {
            saved.current = selection.getRangeAt(0).cloneRange();
        }
    };

    const restore = () => {
        surface.current?.focus();
        const selection = window.getSelection();
        if (saved.current && selection) {
            selection.removeAllRanges();
            selection.addRange(saved.current);
        }
    };

    const trackBlock = () => {
        remember();
        const found = (document.queryCommandValue('formatBlock') || 'p').toLowerCase().replace(/[<>]/g, '');
        setBlock(BLOCKS.some(([tag]) => tag === found) ? found : 'p');
    };

    const applyAsk = (event) => {
        event.preventDefault();
        const url = (ask.url ?? '').trim();
        restore();
        if (url !== '') {
            if (ask.kind === 'link') {
                exec('createLink', url);
                surface.current?.querySelectorAll('a[href]').forEach((anchor) => {
                    if (anchor.getAttribute('href') === url && /^https?:/i.test(url)) {
                        anchor.setAttribute('rel', 'noopener');
                    }
                });
            } else {
                const alt = (ask.alt ?? '').replace(/"/g, '&quot;');
                exec('insertHTML', `<img src="${url.replace(/"/g, '&quot;')}" alt="${alt}">`);
            }
        }
        setAsk(null);
    };

    const openAsk = (kind) => {
        remember();
        const anchor = kind === 'link' ? window.getSelection()?.anchorNode?.parentElement?.closest('a') : null;
        setAsk({ kind, url: anchor?.getAttribute('href') ?? '', alt: '' });
    };

    return (
        <div className="rte">
            <div className="rte-toolbar" role="toolbar" aria-label="Formatting">
                <select
                    className="rte-block"
                    value={block}
                    disabled={source}
                    onChange={(event) => { setBlock(event.target.value); exec('formatBlock', event.target.value); }}
                    aria-label="Text style"
                >
                    {BLOCKS.map(([tag, label]) => <option key={tag} value={tag}>{label}</option>)}
                </select>
                {TOOLS.map((tool, index) => (tool === 'sep'
                    ? <span key={`sep-${index}`} className="rte-sep" />
                    : (
                        <button key={tool.cmd} type="button" className="rte-btn" title={tool.title} aria-label={tool.title} disabled={source} onMouseDown={(event) => event.preventDefault()} onClick={() => exec(tool.cmd)}>
                            {tool.icon}
                        </button>
                    )))}
                <button type="button" className="rte-btn" title="Link" aria-label="Link" disabled={source} onMouseDown={(event) => event.preventDefault()} onClick={() => openAsk('link')}>
                    <Icon><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1" /></Icon>
                </button>
                <button type="button" className="rte-btn" title="Remove link" aria-label="Remove link" disabled={source} onMouseDown={(event) => event.preventDefault()} onClick={() => exec('unlink')}>
                    <Icon><path d="m18.8 13.2 1.2-1.2a5 5 0 0 0-7-7l-1.2 1.2M5.2 10.8 4 12a5 5 0 0 0 7 7l1.2-1.2M3 3l18 18" /></Icon>
                </button>
                <button type="button" className="rte-btn" title="Image" aria-label="Image" disabled={source} onMouseDown={(event) => event.preventDefault()} onClick={() => openAsk('image')}>
                    <Icon><rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="1.5" /><path d="m21 16-5-5-8 8" /></Icon>
                </button>
                <span className="rte-grow" />
                <button type="button" className={`rte-btn rte-source${source ? ' on' : ''}`} title="Edit the HTML" onClick={() => { setSource((open) => !open); setAsk(null); }}>
                    {'</>'} HTML
                </button>
            </div>

            {ask ? (
                <form className="rte-ask" onSubmit={applyAsk}>
                    <input
                        autoFocus
                        type="url"
                        placeholder={ask.kind === 'link' ? 'https://example.com' : 'Image address (https://…)'}
                        value={ask.url}
                        onChange={(event) => setAsk({ ...ask, url: event.target.value })}
                    />
                    {ask.kind === 'image' ? (
                        <input type="text" placeholder="Describe the image (alt text)" value={ask.alt} onChange={(event) => setAsk({ ...ask, alt: event.target.value })} />
                    ) : null}
                    {ask.kind === 'image' ? <button type="button" className="btn btn-light btn-sm" onClick={() => setLibrary(true)}>Choose from library</button> : null}
                    <button type="submit" className="btn btn-sm">{ask.kind === 'link' ? 'Add link' : 'Insert image'}</button>
                    <button type="button" className="btn btn-light btn-sm" onClick={() => setAsk(null)}>Cancel</button>
                </form>
            ) : null}

            {library ? (
                <MediaLibraryModal
                    kind="image"
                    onClose={() => setLibrary(false)}
                    onSelect={(item) => { setAsk((current) => ({ ...current, url: new URL(item.url, window.location.origin).href, alt: current?.alt || item.alt || '' })); setLibrary(false); }}
                />
            ) : null}

            {source ? (
                <textarea className="rte-source-area" value={value ?? ''} onChange={(event) => onChange(event.target.value)} spellCheck={false} />
            ) : (
                <div
                    ref={surface}
                    className="rte-surface"
                    contentEditable
                    suppressContentEditableWarning
                    data-placeholder={placeholder}
                    role="textbox"
                    aria-multiline="true"
                    aria-label="Post content"
                    onInput={emit}
                    onBlur={emit}
                    onKeyUp={trackBlock}
                    onMouseUp={trackBlock}
                    onPaste={(event) => {
                        const text = event.clipboardData.getData('text/plain');
                        if (text) {
                            event.preventDefault();
                            document.execCommand('insertHTML', false, plainTextToHtml(text));
                            emit();
                        }
                    }}
                />
            )}
        </div>
    );
}
