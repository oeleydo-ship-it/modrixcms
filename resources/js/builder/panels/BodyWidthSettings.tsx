import { useId } from 'react';
import type { Breakpoint, PageDocument, StyleData } from '@renderer';
import { useBuilderStore } from '../store';
import { BackgroundGroup, TypographyGroup, SpacingGroup, makeStyleControl } from './styleGroups';
import { WidthControls } from './WidthControls';

/** The page width that follows Appearance → Theme settings → Content width. */
const THEME_WIDTH = 'var(--site-content-width, 1140px)';

function followsTheme(width: string | null | undefined): boolean {
    return !width || width.startsWith('var(--site-content-width');
}

export function BodyWidthSettings({ document, breakpoint }: { document: PageDocument; breakpoint: Breakpoint }) {
    const id = useId();
    const change = (body: StyleData) => {
        const state = useBuilderStore.getState();
        state.commit({ ...state.document, settings: { ...state.document.settings, body } });
    };
    const control = makeStyleControl(document.settings.body ?? {}, breakpoint, change);
    return <div className="flex flex-col gap-4 p-3">
        <h3 className="font-semibold">Page body</h3>
        <p className="text-xs text-slate-500">Page-wide defaults. Individual sections and blocks can override these styles.</p>
        <div className="grid grid-cols-2 gap-2">
            {(['Full width', 'Boxed'] as const).map(label => <button key={label} type="button" className="rounded-md border border-slate-200 px-3 py-2 text-sm hover:bg-slate-100" onClick={() => {
                const state = useBuilderStore.getState();
                const body = state.document.settings.body ?? {};
                change({ ...body, width: { ...body.width, [breakpoint]: '100%' }, maxWidth: { ...body.maxWidth, [breakpoint]: label === 'Boxed' ? '1200px' : 'none' } });
            }}>{label}</button>)}
        </div>
        <WidthControls id={id} control={control} title="Body width" />
        <label className="flex flex-col gap-2 text-sm">Default boxed content width
            <input className="rounded-md border border-slate-200 px-3 py-2" placeholder="Theme setting"
                value={followsTheme(document.settings.contentWidth) ? '' : (document.settings.contentWidth ?? '')} onChange={event => {
                    const state = useBuilderStore.getState();
                    const raw = event.currentTarget.value.trim();
                    // Empty means "follow Theme settings → Content width" again.
                    const contentWidth = raw === '' ? THEME_WIDTH : /^\d+(\.\d+)?$/.test(raw) ? raw + 'px' : event.currentTarget.value;
                    state.commit({ ...state.document, settings: { ...state.document.settings, contentWidth } });
                }} />
        </label>
        <p className="-mt-2 text-xs text-slate-500">
            {followsTheme(document.settings.contentWidth)
                ? 'Follows Appearance → Theme settings → Content width. Type a width (e.g. 1200px) to fix it for this page.'
                : 'Fixed for this page. Clear the field to follow the theme setting again.'}
        </p>
        <BackgroundGroup baseId={`${id}-background`} control={control} layeredImageTools={false} />
        <TypographyGroup baseId={`${id}-typography`} control={control} defaultOpen={false} />
        <SpacingGroup baseId={`${id}-spacing`} control={control} defaultOpen={false} />
        <p className="text-xs text-slate-500">The body limits the entire page, including full-width sections. Choose a section or row on the canvas to size it independently.</p>
    </div>;
}
