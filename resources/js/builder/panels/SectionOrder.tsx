import { useDraggable, useDroppable } from '@dnd-kit/core';
import { ArrowDown, ArrowUp, GripVertical } from 'lucide-react';
import type { SectionData } from '@renderer';
import { useBuilderStore } from '../store';
import { reorderSection } from '../sectionOrder';

function SectionOrderItem({ section, index, count }: { section: SectionData; index: number; count: number }) {
    const label = section.name || `Section ${index + 1}`;
    const drag = useDraggable({ id: `section-list:${section.id}`, data: { source: 'section', sectionId: section.id } });
    const drop = useDroppable({ id: `section-list-before:${section.id}` });
    const selected = useBuilderStore(state => state.selected?.kind === 'section' && state.selected.id === section.id);
    const move = (before: number) => {
        const state = useBuilderStore.getState();
        state.commit(reorderSection(state.document, section.id, before));
        state.select({ kind: 'section', id: section.id });
    };
    return <li ref={drop.setNodeRef} className={`fnl-section-order-item ${drop.isOver ? 'is-over' : ''} ${selected ? 'is-selected' : ''}`}>
        <button ref={drag.setNodeRef} {...drag.attributes} {...drag.listeners} aria-label={`Drag section: ${label}`} className="fnl-section-grip"><GripVertical size={15} /></button>
        <button className="fnl-section-order-name" onClick={() => useBuilderStore.getState().select({ kind: 'section', id: section.id })}>{index + 1}. {label}</button>
        <button disabled={index === 0} onClick={() => move(index - 1)} aria-label={`Move ${label} up`}><ArrowUp size={14} /></button>
        <button disabled={index === count - 1} onClick={() => move(index + 2)} aria-label={`Move ${label} down`}><ArrowDown size={14} /></button>
    </li>;
}

export function SectionOrder() {
    const sections = useBuilderStore(state => state.document.sections);
    return <details className="fnl-library-group" open>
        <summary>Page sections <span>{sections.length}</span></summary>
        <p className="px-2 pb-2 text-xs text-slate-500">Drag to reorder, or use the arrows.</p>
        <ol aria-label="Section order" className="fnl-section-order">{sections.map((section, index) => <SectionOrderItem key={section.id} section={section} index={index} count={sections.length} />)}</ol>
    </details>;
}
