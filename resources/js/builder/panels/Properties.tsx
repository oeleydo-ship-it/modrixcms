import { BodyWidthSettings } from './BodyWidthSettings';
/**
 * The properties panel — a router, nothing more.
 *
 * Each node kind owns its own panel, because a section, a row, a column and a
 * block have almost nothing in common beyond a style bag: showing one generic
 * form for all four is what made the old panel useless for every one of them.
 *
 * Style edits stay scoped to the active breakpoint — writing a value at Mobile
 * leaves Desktop alone, and every control says whether what you are looking at
 * is inherited or set here. Content props are breakpoint-independent.
 */

import { EmptyState } from '@shared/ui/EmptyState';
import { Button } from '@shared/ui/Button';
import type {
    BlockData,
    Breakpoint,
    NodeRef,
    PageDocument,
    StyleData,
    VisibilityData,
} from '@renderer';
import { useBuilderStore } from '../store';
import { findColumn, findColumnRow, findRow, findSection, locateBlock, sectionContaining } from '../tree';
import { SectionSettings } from './SectionSettings';
import { RowSettings } from './RowSettings';
import { ColumnSettings } from './ColumnSettings';
import { BlockSettings } from './BlockSettings';

export interface PropertiesProps {
    document: PageDocument;
    selected: NodeRef | null;
    breakpoint: Breakpoint;
    onStyleChange: (ref: NodeRef, style: StyleData) => void;
    onVisibilityChange: (ref: NodeRef, visibility: VisibilityData) => void;
    onPropsChange: (blockId: string, patch: Record<string, unknown>) => void;
    onRename: (blockId: string, name: string) => void;
    onDuplicate: (blockId: string) => void;
    onDelete: (blockId: string) => void;
}

/** How many headings on the page claim to be the page title. */
function countH1(document: PageDocument): number {
    let total = 0;

    const walk = (blocks: BlockData[]): void => {
        for (const block of blocks) {
            if (block.type === 'heading' && String(block.props['level'] ?? '').toLowerCase() === 'h1') {
                total += 1;
            }
            walk(block.children ?? []);
        }
    };

    for (const section of document.sections) {
        for (const row of section.rows) {
            for (const column of row.columns) {
                walk(column.blocks);
            }
        }
    }
    return total;
}

export function Properties({
    document,
    selected,
    breakpoint,
    onStyleChange,
    onVisibilityChange,
    onPropsChange,
    onRename,
    onDuplicate,
    onDelete,
}: PropertiesProps) {
    // Structural edits — a section's width, a row's stacking, a column's span —
    // are not styles, so they go straight to the store rather than through the
    // style/visibility props this panel was originally given.
    const patchSection = useBuilderStore((state) => state.patchSection);
    const patchRow = useBuilderStore((state) => state.patchRow);
    const patchColumn = useBuilderStore((state) => state.patchColumn);
    const setColumnLayout = useBuilderStore((state) => state.setColumnLayout);
    const deleteSection = useBuilderStore((state) => state.deleteSection);
    const deleteRow = useBuilderStore((state) => state.deleteRow);

    if (!selected) return <BodyWidthSettings document={document} breakpoint={breakpoint} />;

    const owner = sectionContaining(document, selected);
    if (selected.kind !== 'section' && owner?.globalComponentId) {
        return <EmptyState
            variant="bare"
            title="This layer belongs to a global component"
            description="Select the component to open its editor or detach this instance before changing its content."
            action={<Button size="sm" onClick={() => useBuilderStore.getState().select({ kind: 'section', id: owner.id })}>Select component</Button>}
        />;
    }

    if (selected.kind === 'section') {
        const section = findSection(document, selected.id);
        if (!section) {
            return <Missing kind="section" />;
        }
        return (
            <SectionSettings
                section={section}
                breakpoint={breakpoint}
                onStyleChange={(style) => onStyleChange(selected, style)}
                onVisibilityChange={(visibility) => onVisibilityChange(selected, visibility)}
                onPatch={(patch) => patchSection(section.id, patch)}
                onDelete={() => deleteSection(section.id)}
            />
        );
    }

    if (selected.kind === 'row') {
        const row = findRow(document, selected.id);
        if (!row) {
            return <Missing kind="row" />;
        }
        return (
            <RowSettings
                row={row}
                breakpoint={breakpoint}
                onStyleChange={(style) => onStyleChange(selected, style)}
                onVisibilityChange={(visibility) => onVisibilityChange(selected, visibility)}
                onPatch={(patch) => patchRow(row.id, patch)}
                onLayoutChange={(spans) => setColumnLayout(row.id, spans)}
                onDelete={() => deleteRow(row.id)}
            />
        );
    }

    if (selected.kind === 'column') {
        const column = findColumn(document, selected.id);
        if (!column) {
            return <Missing kind="column" />;
        }
        return (
            <ColumnSettings
                column={column}
                row={findColumnRow(document, column.id)}
                breakpoint={breakpoint}
                onStyleChange={(style) => onStyleChange(selected, style)}
                onVisibilityChange={(visibility) => onVisibilityChange(selected, visibility)}
                onPatch={(patch) => patchColumn(column.id, patch)}
            />
        );
    }

    const located = locateBlock(document, selected.id);
    if (!located) {
        return <Missing kind="block" />;
    }

    return (
        <BlockSettings
            block={located.block}
            breakpoint={breakpoint}
            h1Count={countH1(document)}
            onStyleChange={(style) => onStyleChange(selected, style)}
            onVisibilityChange={(visibility) => onVisibilityChange(selected, visibility)}
            onPropsChange={onPropsChange}
            onRename={onRename}
            onDuplicate={onDuplicate}
            onDelete={onDelete}
        />
    );
}

/** The selection points at something that is no longer in the document. */
function Missing({ kind }: { kind: string }) {
    return (
        <EmptyState
            variant="bare"
            title={`That ${kind} is gone`}
            description="It was deleted or undone. Pick something else on the canvas."
        />
    );
}
