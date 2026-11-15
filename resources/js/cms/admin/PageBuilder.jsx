import { useMemo, useRef, useState } from 'react';

/**
 * Page builder: edits a page's list of blocks. Block types (core and plugin) and their
 * fields come from the server (PageBuilder::forEditor). Everything the admin types is
 * rendered as text; only the fixed icon set is inserted as markup.
 */

const uid = () => Math.random().toString(36).slice(2, 10);
const clone = (value) => JSON.parse(JSON.stringify(value));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

const DEFAULT_CATEGORIES = {
    all: 'All blocks',
    hero: 'Hero & Headers',
    content: 'Content & Layout',
    commerce: 'Commerce & Pricing',
    social: 'Social Proof & FAQ',
    media: 'Media & Video',
    conversion: 'Conversion & CTA',
    plugin: 'Extensions',
};

export function normaliseBlocks(blocks, definitions) {
    return (blocks ?? [])
        .filter((block) => definitions[block.type])
        .map((block) => ({
            id: block.id || uid(),
            type: block.type,
            hidden: !!block.hidden,
            data: { ...clone(definitions[block.type].defaults), ...(block.data || {}) },
        }));
}

function summary(block) {
    const d = block.data || {};
    const text = d.title || d.label || d.caption || d.badge || d.eyebrow || String(d.content || '').replace(/[#*_>`-]/g, '').trim() || '';

    return String(text).split('\n')[0].slice(0, 80);
}

function blockCategory(type, def) {
    if (def.category) return def.category;
    if (type.includes(':')) return 'plugin';
    return 'content';
}

function Tool({ label, title, disabled, onClick, danger }) {
    return (
        <button
            type="button"
            className={`pb-tool${danger ? ' danger' : ''}`}
            title={title}
            aria-label={title}
            disabled={disabled}
            onClick={onClick}
        >
            {label}
        </button>
    );
}

export default function PageBuilder({
    blocks,
    onChange,
    definitions,
    icons,
    categories = DEFAULT_CATEGORIES,
    presets = [],
    uploadUrl,
}) {
    const [open, setOpen] = useState(() => new Set(blocks.length <= 3 ? blocks.map((b) => b.id) : []));
    const [palettePos, setPalettePos] = useState(blocks.length);
    const [paletteSearch, setPaletteSearch] = useState('');
    const [paletteCat, setPaletteCat] = useState('all');
    const [blockFilter, setBlockFilter] = useState('');
    const [dragging, setDragging] = useState(null);
    const [dropTarget, setDropTarget] = useState(null);
    const [jsonText, setJsonText] = useState('');
    const [jsonError, setJsonError] = useState('');

    const dialogRef = useRef(null);
    const presetDialogRef = useRef(null);
    const jsonDialogRef = useRef(null);

    const update = (next) => onChange([...next]);

    const toggle = (id) =>
        setOpen((current) => {
            const next = new Set(current);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });

    const expandAll = () => setOpen(new Set(blocks.map((b) => b.id)));
    const collapseAll = () => setOpen(new Set());

    const move = (from, to) => {
        if (to < 0 || to >= blocks.length) return;
        const next = [...blocks];
        const [block] = next.splice(from, 1);
        next.splice(to, 0, block);
        update(next);
    };

    const setBlock = (index, block) => {
        const next = [...blocks];
        next[index] = block;
        update(next);
    };

    const openPalette = (position) => {
        setPalettePos(position);
        setPaletteSearch('');
        setPaletteCat('all');
        dialogRef.current?.showModal();
    };

    const add = (type) => {
        if (!definitions[type]) return;
        const block = { id: uid(), type, hidden: false, data: clone(definitions[type].defaults) };
        const next = [...blocks];
        next.splice(palettePos, 0, block);
        setOpen((current) => new Set(current).add(block.id));
        dialogRef.current?.close();
        update(next);
    };

    const applyPreset = (preset, mode = 'append') => {
        const created = (preset.types || [])
            .filter((type) => definitions[type])
            .map((type) => ({
                id: uid(),
                type,
                hidden: false,
                data: clone(definitions[type].defaults),
            }));

        if (!created.length) return;

        if (mode === 'replace') {
            if (blocks.length > 0 && !window.confirm(`Replace existing ${blocks.length} blocks with "${preset.label}"?`)) {
                return;
            }
            setOpen(new Set(created.slice(0, 2).map((b) => b.id)));
            update(created);
        } else {
            setOpen((current) => {
                const next = new Set(current);
                created.slice(0, 2).forEach((b) => next.add(b.id));
                return next;
            });
            update([...blocks, ...created]);
        }
        presetDialogRef.current?.close();
    };

    const openJsonModal = () => {
        setJsonText(JSON.stringify(blocks, null, 2));
        setJsonError('');
        jsonDialogRef.current?.showModal();
    };

    const importJson = () => {
        try {
            const parsed = JSON.parse(jsonText);
            if (!Array.isArray(parsed)) {
                throw new Error('JSON must be an array of blocks.');
            }
            const normalised = normaliseBlocks(parsed, definitions);
            update(normalised);
            jsonDialogRef.current?.close();
        } catch (err) {
            setJsonError(err.message || 'Invalid JSON format.');
        }
    };

    const visibleCount = blocks.filter((b) => !b.hidden).length;

    const paletteEntries = useMemo(() => {
        const q = paletteSearch.trim().toLowerCase();
        return Object.entries(definitions).filter(([type, def]) => {
            const cat = blockCategory(type, def);
            if (paletteCat !== 'all' && cat !== paletteCat) return false;
            if (!q) return true;
            return (
                def.label.toLowerCase().includes(q) ||
                (def.description || '').toLowerCase().includes(q) ||
                type.toLowerCase().includes(q)
            );
        });
    }, [definitions, paletteSearch, paletteCat]);

    const filteredBlocks = useMemo(() => {
        const q = blockFilter.trim().toLowerCase();
        return blocks.map((block, index) => ({ block, index })).filter(({ block }) => {
            if (!q) return true;
            const def = definitions[block.type];
            return (
                (def?.label || '').toLowerCase().includes(q) ||
                summary(block).toLowerCase().includes(q) ||
                block.type.toLowerCase().includes(q) ||
                String(block.data?.anchor || '').toLowerCase().includes(q)
            );
        });
    }, [blocks, blockFilter, definitions]);

    return (
        <>
            {/* Top Builder Control Bar */}
            <div className="pb-toolbar">
                <div className="pb-toolbar-left">
                    <span className="pb-stat-pill">
                        <b>{blocks.length}</b> {blocks.length === 1 ? 'section' : 'sections'}
                        {blocks.length > 0 ? <small> · {visibleCount} visible</small> : null}
                    </span>
                    {blocks.length > 2 ? (
                        <input
                            type="search"
                            className="pb-filter-input"
                            placeholder="Filter sections on page…"
                            value={blockFilter}
                            onChange={(e) => setBlockFilter(e.target.value)}
                        />
                    ) : null}
                </div>
                <div className="pb-toolbar-actions">
                    {presets?.length ? (
                        <button type="button" className="btn btn-light btn-sm" onClick={() => presetDialogRef.current?.showModal()}>
                            ✦ Section presets
                        </button>
                    ) : null}
                    <button type="button" className="btn btn-light btn-sm" onClick={expandAll} disabled={blocks.length === 0}>
                        Expand all
                    </button>
                    <button type="button" className="btn btn-light btn-sm" onClick={collapseAll} disabled={blocks.length === 0}>
                        Collapse all
                    </button>
                    <button type="button" className="btn btn-light btn-sm" onClick={openJsonModal}>
                        JSON
                    </button>
                    <button type="button" className="btn btn-sm" onClick={() => openPalette(blocks.length)}>
                        + Add section
                    </button>
                </div>
            </div>

            <div className="pb-list">
                {filteredBlocks.map(({ block, index }) => {
                    const def = definitions[block.type];
                    if (!def) return null;
                    const isOpen = open.has(block.id);
                    const iconSvg = icons[def.icon] || icons.sparkle;
                    const anchor = block.data?.anchor ? `#${block.data.anchor}` : null;

                    return (
                        <div className="pb-item" key={block.id}>
                            <section
                                className={[
                                    'pb-block',
                                    block.hidden && 'is-hidden',
                                    isOpen && 'is-open',
                                    dragging === index && 'dragging',
                                    dropTarget === index && 'drop-target',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                                draggable={dragging === index}
                                onDragStart={(e) => {
                                    e.dataTransfer.effectAllowed = 'move';
                                }}
                                onDragEnd={() => {
                                    setDragging(null);
                                    setDropTarget(null);
                                }}
                                onDragOver={(e) => {
                                    if (dragging !== null) {
                                        e.preventDefault();
                                        setDropTarget(index);
                                    }
                                }}
                                onDragLeave={() => setDropTarget(null)}
                                onDrop={(e) => {
                                    e.preventDefault();
                                    if (dragging !== null && dragging !== index) move(dragging, index);
                                    setDragging(null);
                                    setDropTarget(null);
                                }}
                            >
                                <div className="pb-head">
                                    <span
                                        className="pb-handle"
                                        title="Drag to reorder"
                                        aria-hidden="true"
                                        onMouseDown={() => setDragging(index)}
                                    >
                                        ⋮⋮
                                    </span>
                                    <span className="pb-block-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: iconSvg }} />
                                    </span>
                                    <button type="button" className="pb-title" aria-expanded={isOpen} onClick={() => toggle(block.id)}>
                                        <b>{def.label}</b>
                                        <span>{summary(block) || def.description}</span>
                                    </button>
                                    {anchor ? <code className="pb-anchor-pill" title="Section anchor link">{anchor}</code> : null}
                                    {block.hidden ? <span className="badge gray">Hidden</span> : null}
                                    <div className="pb-tools">
                                        <Tool label="↑" title="Move up" disabled={index === 0} onClick={() => move(index, index - 1)} />
                                        <Tool label="↓" title="Move down" disabled={index === blocks.length - 1} onClick={() => move(index, index + 1)} />
                                        <Tool
                                            label={block.hidden ? '◌' : '◉'}
                                            title={block.hidden ? 'Show on page' : 'Hide from page'}
                                            onClick={() => setBlock(index, { ...block, hidden: !block.hidden })}
                                        />
                                        <Tool
                                            label="⧉"
                                            title="Duplicate section"
                                            onClick={() => {
                                                const copy = { ...clone(block), id: uid() };
                                                const next = [...blocks];
                                                next.splice(index + 1, 0, copy);
                                                setOpen((current) => new Set(current).add(copy.id));
                                                update(next);
                                            }}
                                        />
                                        <Tool
                                            label="✕"
                                            title="Delete section"
                                            danger
                                            onClick={() => {
                                                if (window.confirm(`Delete this ${def.label} block?`)) {
                                                    update(blocks.filter((_, i) => i !== index));
                                                }
                                            }}
                                        />
                                    </div>
                                </div>
                                {isOpen ? (
                                    <div className="pb-body">
                                        <Fields
                                            fields={def.fields}
                                            data={block.data}
                                            icons={icons}
                                            uploadUrl={uploadUrl}
                                            onChange={(data) => setBlock(index, { ...block, data })}
                                        />
                                    </div>
                                ) : null}
                            </section>
                            <button type="button" className="pb-add-between" title="Insert a block here" onClick={() => openPalette(index + 1)}>
                                +
                            </button>
                        </div>
                    );
                })}
            </div>

            {blocks.length === 0 ? (
                <div className="pb-empty">
                    <p>This page has no blocks yet. Add a single block or start from a ready-made section preset.</p>
                    {presets?.length ? (
                        <div className="pb-empty-presets">
                            {presets.map((preset) => (
                                <button key={preset.key} type="button" className="btn btn-light btn-sm" onClick={() => applyPreset(preset, 'replace')}>
                                    ✦ {preset.label}
                                </button>
                            ))}
                        </div>
                    ) : null}
                </div>
            ) : null}

            <button type="button" className="btn btn-light pb-add" onClick={() => openPalette(blocks.length)}>
                + Add block
            </button>

            {/* Block Library Palette Modal */}
            <dialog className="pb-palette" ref={dialogRef}>
                <div className="pb-palette-head">
                    <div>
                        <h2>Add a section or block</h2>
                        <p className="pb-palette-sub">Pick from {Object.keys(definitions).length} customizable section designs and content blocks.</p>
                    </div>
                    <button type="button" className="pb-tool" aria-label="Close" onClick={() => dialogRef.current?.close()}>
                        ✕
                    </button>
                </div>

                <div className="pb-palette-controls">
                    <input
                        type="search"
                        className="pb-palette-search"
                        placeholder="Search blocks (e.g. pricing, hero, FAQ, gallery, testimonials)…"
                        value={paletteSearch}
                        onChange={(e) => setPaletteSearch(e.target.value)}
                    />
                    <div className="pb-palette-tabs" role="tablist">
                        {Object.entries(categories).map(([key, label]) => (
                            <button
                                key={key}
                                type="button"
                                className={`pb-palette-tab${paletteCat === key ? ' is-active' : ''}`}
                                onClick={() => setPaletteCat(key)}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </div>

                <div className="pb-palette-grid">
                    {paletteEntries.length ? (
                        paletteEntries.map(([type, def]) => {
                            const catKey = blockCategory(type, def);
                            const catLabel = categories[catKey] || catKey;
                            const iconSvg = icons[def.icon] || icons.sparkle;
                            return (
                                <button type="button" key={type} className="pb-palette-item" onClick={() => add(type)}>
                                    <div className="pb-palette-item-top">
                                        <span className="pb-palette-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: iconSvg }} />
                                        </span>
                                        <small className="pb-palette-cat">{catLabel}</small>
                                    </div>
                                    <b>{def.label}</b>
                                    <span>{def.description}</span>
                                </button>
                            );
                        })
                    ) : (
                        <div className="pb-palette-empty">No blocks match “{paletteSearch}”.</div>
                    )}
                </div>
            </dialog>

            {/* Multi-block Section Presets Modal */}
            <dialog className="pb-palette" ref={presetDialogRef}>
                <div className="pb-palette-head">
                    <div>
                        <h2>Section presets &amp; page layouts</h2>
                        <p className="pb-palette-sub">Insert a curated multi-section flow or replace the current blocks.</p>
                    </div>
                    <button type="button" className="pb-tool" aria-label="Close" onClick={() => presetDialogRef.current?.close()}>
                        ✕
                    </button>
                </div>
                <div className="pb-preset-list">
                    {(presets || []).map((preset) => (
                        <div key={preset.key} className="pb-preset-card">
                            <div className="pb-preset-info">
                                <b>{preset.label}</b>
                                <p>{preset.description}</p>
                                <div className="pb-preset-chips">
                                    {(preset.types || []).map((t, i) => (
                                        <span key={i}>{definitions[t]?.label || t}</span>
                                    ))}
                                </div>
                            </div>
                            <div className="pb-preset-actions">
                                <button type="button" className="btn btn-light btn-sm" onClick={() => applyPreset(preset, 'append')}>
                                    + Append sections
                                </button>
                                <button type="button" className="btn btn-sm" onClick={() => applyPreset(preset, 'replace')}>
                                    Use as page layout
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </dialog>

            {/* JSON Import / Export Modal */}
            <dialog className="pb-palette" ref={jsonDialogRef}>
                <div className="pb-palette-head">
                    <div>
                        <h2>Import / Export page blocks JSON</h2>
                        <p className="pb-palette-sub">Copy this JSON to back up or duplicate blocks across pages, or paste JSON below to import.</p>
                    </div>
                    <button type="button" className="pb-tool" aria-label="Close" onClick={() => jsonDialogRef.current?.close()}>
                        ✕
                    </button>
                </div>
                <div className="pb-json-body">
                    <textarea
                        className="mono"
                        rows={12}
                        value={jsonText}
                        onChange={(e) => {
                            setJsonText(e.target.value);
                            setJsonError('');
                        }}
                    />
                    {jsonError ? <div className="err">{jsonError}</div> : null}
                    <div className="actions" style={{ justifyContent: 'space-between', marginTop: 12 }}>
                        <button
                            type="button"
                            className="btn btn-light btn-sm"
                            onClick={() => navigator.clipboard?.writeText(jsonText)}
                        >
                            Copy JSON to clipboard
                        </button>
                        <div className="actions">
                            <button type="button" className="btn btn-light btn-sm" onClick={() => jsonDialogRef.current?.close()}>
                                Cancel
                            </button>
                            <button type="button" className="btn btn-sm" onClick={importJson}>
                                Apply JSON
                            </button>
                        </div>
                    </div>
                </div>
            </dialog>
        </>
    );
}

function Fields({ fields, data, onChange, icons, uploadUrl }) {
    return fields.map((field) => (
        <FieldEditor
            key={field.name}
            field={field}
            value={data[field.name]}
            icons={icons}
            uploadUrl={uploadUrl}
            onChange={(value) => onChange({ ...data, [field.name]: value })}
        />
    ));
}

function MarkdownToolbar({ textareaRef, value, onChange }) {
    const insert = (prefix, suffix = '', sample = 'text') => {
        const el = textareaRef.current;
        const current = String(value ?? '');
        if (!el) {
            onChange(`${current}${prefix}${sample}${suffix}`);
            return;
        }
        const start = el.selectionStart ?? current.length;
        const end = el.selectionEnd ?? current.length;
        const selected = current.slice(start, end) || sample;
        const next = current.slice(0, start) + prefix + selected + suffix + current.slice(end);
        onChange(next);
        requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
        });
    };

    return (
        <div className="pb-md-bar" role="toolbar" aria-label="Formatting">
            <button type="button" title="Heading 2" onClick={() => insert('\n## ', '\n', 'Section heading')}>H2</button>
            <button type="button" title="Heading 3" onClick={() => insert('\n### ', '\n', 'Subheading')}>H3</button>
            <button type="button" title="Bold" onClick={() => insert('**', '**', 'bold')}><b>B</b></button>
            <button type="button" title="Italic" onClick={() => insert('*', '*', 'italic')}><i>I</i></button>
            <button type="button" title="Inline code" onClick={() => insert('`', '`', 'code')}>{'<>'}</button>
            <button type="button" title="Link" onClick={() => insert('[', '](https://)', 'link text')}>Link</button>
            <button type="button" title="Bullet list" onClick={() => insert('\n- ', '', 'List item')}>• List</button>
            <button type="button" title="Quote" onClick={() => insert('\n> ', '', 'Quote')}>“ Quote</button>
        </div>
    );
}

function IconPickerField({ id, field, value, icons, onChange }) {
    const [openGrid, setOpenGrid] = useState(false);
    const current = value && icons[value] ? value : 'sparkle';

    return (
        <div className="field pb-field">
            <label htmlFor={id}>{field.label}</label>
            <div className="pb-icon-row">
                <button
                    type="button"
                    className="pb-icon-preview"
                    title="Click to pick icon visually"
                    onClick={() => setOpenGrid((v) => !v)}
                >
                    <svg viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: icons[current] || icons.sparkle }} />
                </button>
                <select id={id} value={current} onChange={(e) => onChange(e.target.value)}>
                    {Object.keys(icons).map((name) => (
                        <option key={name} value={name}>
                            {name}
                        </option>
                    ))}
                </select>
                <button type="button" className="btn btn-light btn-sm" onClick={() => setOpenGrid((v) => !v)}>
                    {openGrid ? 'Hide icons' : 'Browse'}
                </button>
            </div>
            {openGrid ? (
                <div className="pb-icon-grid">
                    {Object.entries(icons).map(([name, svg]) => (
                        <button
                            key={name}
                            type="button"
                            className={`pb-icon-cell${current === name ? ' is-selected' : ''}`}
                            title={name}
                            onClick={() => {
                                onChange(name);
                                setOpenGrid(false);
                            }}
                        >
                            <svg viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: svg }} />
                            <span>{name}</span>
                        </button>
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function FieldEditor({ field, value, onChange, icons, uploadUrl }) {
    const [id] = useState(() => `f-${uid()}`);
    const textareaRef = useRef(null);
    let help = field.help;
    let control;

    switch (field.type) {
        case 'textarea':
        case 'markdown':
            control = (
                <>
                    {field.type === 'markdown' ? <MarkdownToolbar textareaRef={textareaRef} value={value} onChange={onChange} /> : null}
                    <textarea
                        ref={textareaRef}
                        id={id}
                        rows={field.type === 'markdown' ? 8 : 3}
                        className={field.type === 'markdown' ? 'mono' : undefined}
                        value={value ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                    />
                </>
            );
            break;
        case 'number':
            control = (
                <input
                    id={id}
                    type="number"
                    min={field.min}
                    max={field.max}
                    value={value ?? field.min ?? 0}
                    onChange={(e) => onChange(Number(e.target.value))}
                />
            );
            break;
        case 'select':
            control = (
                <select id={id} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                    {Object.entries(field.options).map(([optionValue, text]) => (
                        <option key={optionValue} value={optionValue}>
                            {text}
                        </option>
                    ))}
                </select>
            );
            break;
        case 'color':
            control = (
                <div className="pb-color-input">
                    <input
                        type="color"
                        value={/^#[0-9a-fA-F]{6}$/.test(value || '') ? value : field.default || '#5b3df5'}
                        onChange={(e) => onChange(e.target.value)}
                    />
                    <input
                        id={id}
                        type="text"
                        placeholder={field.default || '#5b3df5'}
                        value={value ?? ''}
                        onChange={(e) => onChange(e.target.value)}
                    />
                </div>
            );
            break;
        case 'toggle':
            return (
                <div className="field pb-field">
                    <label className="check">
                        <input type="checkbox" checked={!!value} onChange={(e) => onChange(e.target.checked)} /> {field.label}
                    </label>
                </div>
            );
        case 'list':
            control = <ListInput id={id} value={value} onChange={onChange} />;
            help = help || 'One per line.';
            break;
        case 'icon':
            return <IconPickerField id={id} field={field} value={value} icons={icons} onChange={onChange} />;
        case 'image':
            return <ImageField id={id} field={field} value={value} onChange={onChange} uploadUrl={uploadUrl} />;
        case 'repeater':
            return <Repeater field={field} rows={Array.isArray(value) ? value : []} onChange={onChange} icons={icons} uploadUrl={uploadUrl} />;
        case 'url':
            control = (
                <input
                    id={id}
                    type="text"
                    placeholder="https://…, /page, #section"
                    value={value ?? ''}
                    onChange={(e) => onChange(e.target.value.trim())}
                />
            );
            help = help || '@github or @marketplace use the links from Settings → General.';
            break;
        default:
            control = <input id={id} type="text" value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
    }

    return (
        <div className={`field pb-field pb-${field.type}`}>
            <label htmlFor={id}>{field.label}</label>
            {control}
            {help ? <div className="help">{help}</div> : null}
        </div>
    );
}

/** A textarea with one item per line; keeps the text as typed while editing. */
function ListInput({ id, value, onChange }) {
    const items = Array.isArray(value) ? value : [];
    const [text, setText] = useState(items.join('\n'));

    return (
        <textarea
            id={id}
            rows={Math.min(8, Math.max(3, items.length + 1))}
            value={text}
            onChange={(e) => {
                setText(e.target.value);
                onChange(
                    e.target.value
                        .split('\n')
                        .map((s) => s.trim())
                        .filter(Boolean),
                );
            }}
        />
    );
}

function ImageField({ id, field, value, onChange, uploadUrl }) {
    const [status, setStatus] = useState('');
    const storage = document.querySelector('meta[name="storage-url"]')?.content ?? '/storage';

    const upload = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        setStatus('Uploading…');
        const body = new FormData();
        body.append('image', file);
        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                body,
            });
            const json = await response.json();
            if (!response.ok) throw new Error(json.message || 'Upload failed');
            onChange(json.path);
            setStatus('Uploaded.');
        } catch (error) {
            setStatus(error.message);
        } finally {
            e.target.value = '';
        }
    };

    return (
        <div className="field pb-field">
            <label htmlFor={id}>{field.label}</label>
            {value ? (
                <div className="pb-image-preview">
                    <img src={`${storage.replace(/\/$/, '')}/${value}`} alt="" />
                </div>
            ) : null}
            <div className="actions">
                <input id={id} type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={upload} />
                {value ? (
                    <button type="button" className="btn btn-light btn-sm" onClick={() => onChange('')}>
                        Remove image
                    </button>
                ) : null}
            </div>
            <div className="help">{status}</div>
        </div>
    );
}

function Repeater({ field, rows, onChange, icons, uploadUrl }) {
    const max = field.max || 20;
    const [collapsed, setCollapsed] = useState(() => new Set());

    const blank = () =>
        Object.fromEntries(
            field.fields.map((f) => [
                f.name,
                f.type === 'list'
                    ? []
                    : f.type === 'toggle'
                      ? false
                      : f.type === 'number'
                        ? f.min ?? 5
                        : f.type === 'select'
                          ? Object.keys(f.options)[0]
                          : f.type === 'icon'
                            ? 'sparkle'
                            : '',
            ]),
        );

    const moveRow = (from, to) => {
        const next = [...rows];
        next.splice(to, 0, ...next.splice(from, 1));
        onChange(next);
    };

    const duplicateRow = (index) => {
        if (rows.length >= max) return;
        const next = [...rows];
        next.splice(index + 1, 0, clone(rows[index]));
        onChange(next);
    };

    const toggleRow = (index) =>
        setCollapsed((curr) => {
            const next = new Set(curr);
            next.has(index) ? next.delete(index) : next.add(index);
            return next;
        });

    return (
        <div className="field pb-field">
            <div className="pb-repeater-header">
                <span className="label">
                    {field.label} <small className="pb-count-inline">({rows.length}/{max})</small>
                </span>
            </div>
            <div className="pb-repeater">
                {rows.map((row, i) => {
                    const isCollapsed = collapsed.has(i);
                    const rowTitle = row.title || row.name || row.question || row.label || row.author || row.phase || row.value || 'Item';
                    return (
                        <div className="pb-row" key={i}>
                            <div className="pb-row-head">
                                <button type="button" className="pb-row-toggle" onClick={() => toggleRow(i)}>
                                    <b>{`${i + 1}. ${String(rowTitle).slice(0, 56)}`}</b>
                                </button>
                                <div className="pb-tools">
                                    <Tool label="↑" title="Move up" disabled={i === 0} onClick={() => moveRow(i, i - 1)} />
                                    <Tool label="↓" title="Move down" disabled={i === rows.length - 1} onClick={() => moveRow(i, i + 1)} />
                                    <Tool label="⧉" title="Duplicate item" disabled={rows.length >= max} onClick={() => duplicateRow(i)} />
                                    <Tool label="✕" title="Remove" danger onClick={() => onChange(rows.filter((_, j) => j !== i))} />
                                </div>
                            </div>
                            {!isCollapsed ? (
                                <div className="pb-row-fields">
                                    <Fields
                                        fields={field.fields}
                                        data={row}
                                        icons={icons}
                                        uploadUrl={uploadUrl}
                                        onChange={(data) => onChange(rows.map((r, j) => (j === i ? data : r)))}
                                    />
                                </div>
                            ) : null}
                        </div>
                    );
                })}
                <button type="button" className="btn btn-light btn-sm" disabled={rows.length >= max} onClick={() => onChange([...rows, blank()])}>
                    {`+ Add ${field.label.toLowerCase().replace(/s$/, '')}`}
                </button>
            </div>
        </div>
    );
}
