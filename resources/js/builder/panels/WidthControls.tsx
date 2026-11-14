import { ControlGroup, TextControl, PanelNote } from './controls';
import type { StyleControlFactory } from './styleGroups';

export function WidthControls({ id, control, title = 'Width' }: { id: string; control: StyleControlFactory; title?: string }) {
    return <ControlGroup title={title}>
        <TextControl id={`${id}-width`} label="Width" placeholder="100%" context={control('width')} />
        <TextControl id={`${id}-max-width`} label="Maximum width" placeholder="none or 1200px" context={control('maxWidth')} />
        <TextControl id={`${id}-min-width`} label="Minimum width" placeholder="0px" context={control('minWidth')} />
        <PanelNote>Use %, px or rem. Width is relative to the parent container. Smaller screen overrides are independent.</PanelNote>
    </ControlGroup>;
}
