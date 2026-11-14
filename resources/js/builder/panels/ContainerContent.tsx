/**
 * Container content settings.
 *
 * A container is also how a column holds columns: its "Columns" layout splits
 * it into side-by-side containers, each of which takes blocks — or another
 * Columns container. Switching layout goes through the store rather than a
 * plain props write, because it restructures the container's children.
 */

import { Input } from '@shared/ui/Input';
import { Select } from '@shared/ui/Select';
import { containerColumnSpans, type BlockData } from '@renderer';
import { useBuilderStore } from '../store';
import type { ContainerLayout } from '../tree';
import { OptionGroup, PanelNote } from './controls';
import { propText, type PropsPatch } from './BlockContentFields';
import { ColumnPresetGrid, presetKeyForSpans } from './RowSettings';

const LAYOUTS: ReadonlyArray<{ value: ContainerLayout; label: string }> = [
    { value: 'stack', label: 'Stack (vertical)' },
    { value: 'row', label: 'Row (horizontal)' },
    { value: 'grid', label: 'Grid' },
    { value: 'columns', label: 'Columns (nested)' },
];

type StackBelow = 'mobile' | 'tablet' | 'none';

const STACK_OPTIONS: ReadonlyArray<{ value: StackBelow; label: string }> = [
    { value: 'none', label: 'Never' },
    { value: 'mobile', label: 'On mobile' },
    { value: 'tablet', label: 'Tablet down' },
];

function layoutOf(props: Record<string, unknown>): ContainerLayout {
    const value = props['layout'];
    return value === 'row' || value === 'grid' || value === 'columns' ? value : 'stack';
}

export function ContainerContent({ block, set }: { block: BlockData; set: PropsPatch }) {
    const layout = layoutOf(block.props);
    const spans = containerColumnSpans(block.props);
    const stack = block.props['stackBelow'] === 'tablet' || block.props['stackBelow'] === 'none'
        ? (block.props['stackBelow'] as StackBelow)
        : 'mobile';

    return (
        <>
            <Select
                label="Layout"
                size="sm"
                options={[...LAYOUTS]}
                value={layout}
                onChange={(event) =>
                    useBuilderStore.getState().setContainerLayout(block.id, layoutOf({ layout: event.currentTarget.value }))
                }
            />

            {layout === 'columns' && (
                <>
                    <ColumnPresetGrid
                        activeKey={presetKeyForSpans(spans)}
                        onChange={(next) => useBuilderStore.getState().setContainerColumns(block.id, next)}
                    />
                    <PanelNote>
                        Each column is its own container: click it on the canvas to add blocks, style it, or split it
                        into more columns. Content from a removed column moves into the last one.
                    </PanelNote>
                    <OptionGroup
                        label="Stack columns"
                        value={stack}
                        options={STACK_OPTIONS}
                        onChange={(next) => set({ stackBelow: next })}
                    />
                </>
            )}

            <Input
                label={layout === 'columns' ? 'Gutter' : 'Gap'}
                size="sm"
                placeholder={layout === 'columns' ? '24px' : '16px'}
                value={propText(block.props, 'gap')}
                onChange={(event) => set({ gap: event.currentTarget.value })}
            />
        </>
    );
}
