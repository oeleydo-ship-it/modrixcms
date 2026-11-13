import { useRef, useState } from 'react';

/**
 * Page builder: edits a page's list of blocks. Block types (core and plugin) and their
 * fields come from the server (PageBuilder::forEditor). Everything the admin types is
 * rendered as text; only the fixed icon set is inserted as markup.
 */

const uid = () => Math.random().toString(36).slice(2, 10);
const clone = (value) => JSON.parse(JSON.stringify(value));
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

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
    const d = block.data;
    const text = d.title || d.label || d.caption || String(d.content || '').replace(/[#*_>`-]/g, '').trim() || '';

    return String(text).split('\n')[0].slice(0, 80);
}

function Tool({ label, title, disabled, onClick, danger }) {
    return <button type="button" className={`pb-tool${danger ? ' danger' : ''}`} title={title} aria-label={title} disabled={disabled} onClick={onClick}>{label}</button>;
}

export default function PageBuilder({ blocks, onChange, definitions, icons, uploadUrl }) {
    const [open, setOpen] = useState(() => new Set(blocks.length <= 3 ? blocks.map((b) => b.id) : []));
    const [palette, setPalette] = useState(null);
    const [dragging, setDragging] = useState(null);
    const [dropTarget, setDropTarget] = useState(null);
    const dialog = useRef(null);

    const update = (next) => onChange([...next]);
    const toggle = (id) => setOpen((current) => {
        const next = new Set(current);
        next.has(id) ? next.delete(id) : next.add(id);
        return next;
    });

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
        setPalette(position);
        dialog.current?.showModal();
    };

    const add = (type) => {
        const block = { id: uid(), type, hidden: false, data: clone(definitions[type].defaults) };
        const next = [...blocks];
        next.splice(palette, 0, block);
        setOpen((current) => new Set(current).add(block.id));
        dialog.current?.close();
        update(next);
    };

    return (
        <>
            <div className="pb-list">
                {blocks.map((block, index) => {
                    const def = definitions[block.type];
                    const isOpen = open.has(block.id);

                    return (
                        <div className="pb-item" key={block.id}>
                            <section
                                className={['pb-block', block.hidden && 'is-hidden', dragging === index && 'dragging', dropTarget === index && 'drop-target'].filter(Boolean).join(' ')}
                                draggable={dragging === index}
                                onDragStart={(e) => { e.dataTransfer.effectAllowed = 'move'; }}
                                onDragEnd={() => { setDragging(null); setDropTarget(null); }}
                                onDragOver={(e) => { if (dragging !== null) { e.preventDefault(); setDropTarget(index); } }}
                                onDragLeave={() => setDropTarget(null)}
                                onDrop={(e) => { e.preventDefault(); if (dragging !== null && dragging !== index) move(dragging, index); setDragging(null); setDropTarget(null); }}
                            >
                                <div className="pb-head">
                                    <span className="pb-handle" title="Drag to move" aria-hidden="true" onMouseDown={() => setDragging(index)}>⋮⋮</span>
                                    <button type="button" className="pb-title" aria-expanded={isOpen} onClick={() => toggle(block.id)}>
                                        <b>{def.label}</b><span>{summary(block)}</span>
                                    </button>
                                    {block.hidden ? <span className="badge gray">Hidden</span> : null}
                                    <div className="pb-tools">
                                        <Tool label="↑" title="Move up" disabled={index === 0} onClick={() => move(index, index - 1)} />
                                        <Tool label="↓" title="Move down" disabled={index === blocks.length - 1} onClick={() => move(index, index + 1)} />
                                        <Tool label={block.hidden ? '◌' : '◉'} title={block.hidden ? 'Show on page' : 'Hide from page'} onClick={() => setBlock(index, { ...block, hidden: !block.hidden })} />
                                        <Tool label="⧉" title="Duplicate" onClick={() => {
                                            const copy = { ...clone(block), id: uid() };
                                            const next = [...blocks];
                                            next.splice(index + 1, 0, copy);
                                            setOpen((current) => new Set(current).add(copy.id));
                                            update(next);
                                        }} />
                                        <Tool label="✕" title="Delete" danger onClick={() => {
                                            if (window.confirm(`Delete this ${def.label} block?`)) {
                                                update(blocks.filter((_, i) => i !== index));
                                            }
                                        }} />
                                    </div>
                                </div>
                                {isOpen ? (
                                    <div className="pb-body">
                                        <Fields fields={def.fields} data={block.data} icons={icons} uploadUrl={uploadUrl}
                                            onChange={(data) => setBlock(index, { ...block, data })} />
                                    </div>
                                ) : null}
                            </section>
                            <button type="button" className="pb-add-between" title="Add a block here" onClick={() => openPalette(index + 1)}>+</button>
                        </div>
                    );
                })}
            </div>

            {blocks.length === 0 ? <div className="pb-empty"><p>This page has no blocks yet.</p></div> : null}
            <button type="button" className="btn btn-light pb-add" onClick={() => openPalette(blocks.length)}>+ Add block</button>

            <dialog className="pb-palette" ref={dialog}>
                <div className="pb-palette-head">
                    <h2>Add a block</h2>
                    <button type="button" className="pb-tool" aria-label="Close" onClick={() => dialog.current?.close()}>✕</button>
                </div>
                <div className="pb-palette-grid">
                    {Object.entries(definitions).map(([type, def]) => (
                        <button type="button" key={type} className="pb-palette-item" onClick={() => add(type)}>
                            <b>{def.label}</b><span>{def.description}</span>
                        </button>
                    ))}
                </div>
            </dialog>
        </>
    );
}

function Fields({ fields, data, onChange, icons, uploadUrl }) {
    return fields.map((field) => (
        <FieldEditor key={field.name} field={field} value={data[field.name]} icons={icons} uploadUrl={uploadUrl}
            onChange={(value) => onChange({ ...data, [field.name]: value })} />
    ));
}

function FieldEditor({ field, value, onChange, icons, uploadUrl }) {
    const [id] = useState(() => `f-${uid()}`);
    let help = field.help;
    let control;

    switch (field.type) {
        case 'textarea':
        case 'markdown':
            control = <textarea id={id} rows={field.type === 'markdown' ? 8 : 3} className={field.type === 'markdown' ? 'mono' : undefined} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
            break;
        case 'number':
            control = <input id={id} type="number" min={field.min} max={field.max} value={value ?? field.min ?? 0} onChange={(e) => onChange(Number(e.target.value))} />;
            break;
        case 'select':
            control = (
                <select id={id} value={value ?? ''} onChange={(e) => onChange(e.target.value)}>
                    {Object.entries(field.options).map(([optionValue, text]) => <option key={optionValue} value={optionValue}>{text}</option>)}
                </select>
            );
            break;
        case 'toggle':
            return (
                <div className="field pb-field">
                    <label className="check"><input type="checkbox" checked={!!value} onChange={(e) => onChange(e.target.checked)} /> {field.label}</label>
                </div>
            );
        case 'list':
            control = <ListInput id={id} value={value} onChange={onChange} />;
            help = help || 'One per line.';
            break;
        case 'icon':
            return (
                <div className="field pb-field">
                    <label htmlFor={id}>{field.label}</label>
                    <div className="pb-icon-row">
                        <span className="pb-icon-preview"><svg viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: icons[value] || icons.sparkle }} /></span>
                        <select id={id} value={value ?? 'sparkle'} onChange={(e) => onChange(e.target.value)}>
                            {Object.keys(icons).map((name) => <option key={name} value={name}>{name}</option>)}
                        </select>
                    </div>
                </div>
            );
        case 'image':
            return <ImageField id={id} field={field} value={value} onChange={onChange} uploadUrl={uploadUrl} />;
        case 'repeater':
            return <Repeater field={field} rows={Array.isArray(value) ? value : []} onChange={onChange} icons={icons} uploadUrl={uploadUrl} />;
        case 'url':
            control = <input id={id} type="text" placeholder="https://…, /page, #section" value={value ?? ''} onChange={(e) => onChange(e.target.value.trim())} />;
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
        <textarea id={id} rows={Math.min(8, Math.max(3, items.length + 1))} value={text} onChange={(e) => {
            setText(e.target.value);
            onChange(e.target.value.split('\n').map((s) => s.trim()).filter(Boolean));
        }} />
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
            const response = await fetch(uploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body });
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
            {value ? <div className="pb-image-preview"><img src={`${storage.replace(/\/$/, '')}/${value}`} alt="" /></div> : null}
            <div className="actions">
                <input id={id} type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={upload} />
                {value ? <button type="button" className="btn btn-light btn-sm" onClick={() => onChange('')}>Remove image</button> : null}
            </div>
            <div className="help">{status}</div>
        </div>
    );
}

function Repeater({ field, rows, onChange, icons, uploadUrl }) {
    const max = field.max || 20;
    const blank = () => Object.fromEntries(field.fields.map((f) => [
        f.name,
        f.type === 'list' ? [] : f.type === 'toggle' ? false : f.type === 'select' ? Object.keys(f.options)[0] : f.type === 'icon' ? 'sparkle' : '',
    ]));
    const moveRow = (from, to) => {
        const next = [...rows];
        next.splice(to, 0, ...next.splice(from, 1));
        onChange(next);
    };

    return (
        <div className="field pb-field">
            <span className="label">{field.label}</span>
            <div className="pb-repeater">
                {rows.map((row, i) => (
                    <div className="pb-row" key={i}>
                        <div className="pb-row-head">
                            <b>{`${i + 1}. ${row.title || row.label || row.value || ''}`}</b>
                            <div className="pb-tools">
                                <Tool label="↑" title="Move up" disabled={i === 0} onClick={() => moveRow(i, i - 1)} />
                                <Tool label="↓" title="Move down" disabled={i === rows.length - 1} onClick={() => moveRow(i, i + 1)} />
                                <Tool label="✕" title="Remove" danger onClick={() => onChange(rows.filter((_, j) => j !== i))} />
                            </div>
                        </div>
                        <div className="pb-row-fields">
                            <Fields fields={field.fields} data={row} icons={icons} uploadUrl={uploadUrl}
                                onChange={(data) => onChange(rows.map((r, j) => (j === i ? data : r)))} />
                        </div>
                    </div>
                ))}
                <button type="button" className="btn btn-light btn-sm" disabled={rows.length >= max} onClick={() => onChange([...rows, blank()])}>
                    {`+ Add ${field.label.toLowerCase().replace(/s$/, '')}`}
                </button>
            </div>
        </div>
    );
}
