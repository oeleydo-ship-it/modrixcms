/**
 * Reusing a section: save it to the library as a copy, or make it a global
 * component so one edit updates every page. For a section that already is a
 * global component instance, this is where to edit the component or detach
 * the section from it.
 */

import { useState } from 'react';
import { ExternalLink, Link2, Link2Off, Save } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Input } from '@shared/ui/Input';
import { Select } from '@shared/ui/Select';
import { useToast } from '@shared/ui/Toast';
import type { SectionData } from '@renderer';
import {
    GLOBAL_COMPONENT_TYPES,
    createGlobalComponent,
    mintGlobalComponentInstance,
    saveSectionToLibrary,
    type GlobalComponentType,
} from '../api';
import { useBuilderMode } from '../builderMode';
import { useBuilderStore } from '../store';
import { ControlGroup, PanelNote } from './controls';

type Mode = 'idle' | 'library' | 'global';

function announceLibraryChange(): void {
    window.dispatchEvent(new CustomEvent('fnl:library-changed'));
}

export function SectionReuse({ section }: { section: SectionData }) {
    const toast = useToast();
    const builder = useBuilderMode();
    const [mode, setMode] = useState<Mode>('idle');
    const [name, setName] = useState(section.name ?? '');
    const [type, setType] = useState<GlobalComponentType>('header');
    const [busy, setBusy] = useState(false);

    // The component being edited cannot be turned into another component.
    if (builder.kind === 'component') {
        return null;
    }

    if (section.globalComponentId) {
        const componentId = section.globalComponentId;

        return (
            <ControlGroup title="Global component">
                <PanelNote>
                    <span className="inline-flex items-center gap-1.5 font-medium">
                        <Link2 className="h-3.5 w-3.5" aria-hidden /> This section is a live copy of a global component.
                    </span>{' '}
                    Its content comes from the component and is replaced when the page saves, so edit the component
                    itself — every page using it updates. Hiding it on a device still works here.
                </PanelNote>
                <Button
                    size="sm"
                    variant="primary"
                    leadingIcon={<ExternalLink className="h-3.5 w-3.5" />}
                    onClick={() => window.open(`/builder/components/${componentId}`, '_blank', 'noopener')}
                >
                    Edit component
                </Button>
                <Button
                    size="sm"
                    leadingIcon={<Link2Off className="h-3.5 w-3.5" />}
                    onClick={() => {
                        useBuilderStore.getState().patchSection(section.id, { globalComponentId: null });
                        toast.success('Section detached', 'It is now an ordinary section you can edit on this page.');
                    }}
                >
                    Detach from component
                </Button>
            </ControlGroup>
        );
    }

    const saveToLibrary = async (): Promise<void> => {
        setBusy(true);
        try {
            await saveSectionToLibrary(name.trim(), section);
            announceLibraryChange();
            toast.success('Saved to your library', 'Insert it on any page from the Library tab.');
            setMode('idle');
        } catch (error) {
            toast.error('Could not save the section', error instanceof Error ? error.message : undefined);
        } finally {
            setBusy(false);
        }
    };

    const makeGlobal = async (): Promise<void> => {
        setBusy(true);
        try {
            const component = await createGlobalComponent(name.trim(), type, section);
            const instance = await mintGlobalComponentInstance(component.id);
            // Keep this page's per-device visibility on the new instance.
            useBuilderStore.getState().replaceSection(section.id, { ...instance, visibility: section.visibility });
            announceLibraryChange();
            toast.success('Global component created', `“${component.name}” can now be inserted on any page from the Library tab.`);
            setMode('idle');
        } catch (error) {
            toast.error('Could not create the component', error instanceof Error ? error.message : undefined);
        } finally {
            setBusy(false);
        }
    };

    return (
        <ControlGroup title="Reuse this section">
            {mode === 'idle' && (
                <>
                    <Button size="sm" leadingIcon={<Save className="h-3.5 w-3.5" />} onClick={() => setMode('library')}>
                        Save to library
                    </Button>
                    {/* Modrix CMS has no global components: saved sections are copies. */}
                    <PanelNote>
                        A saved section is a copy you can add to any page from the Saved tab.
                    </PanelNote>
                </>
            )}

            {mode !== 'idle' && (
                <form
                    className="flex flex-col gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        if (name.trim().length >= 2) {
                            void (mode === 'library' ? saveToLibrary() : makeGlobal());
                        }
                    }}
                >
                    <Input
                        label={mode === 'library' ? 'Name in the library' : 'Component name'}
                        size="sm"
                        value={name}
                        autoFocus
                        placeholder={mode === 'library' ? 'Testimonials band' : 'Site header'}
                        onChange={(event) => setName(event.currentTarget.value)}
                    />
                    {mode === 'global' && (
                        <Select
                            label="Kind"
                            size="sm"
                            options={GLOBAL_COMPONENT_TYPES}
                            value={type}
                            onChange={(event) => setType(event.currentTarget.value as GlobalComponentType)}
                        />
                    )}
                    <div className="flex gap-2">
                        <Button type="submit" size="sm" variant="primary" loading={busy} disabled={name.trim().length < 2}>
                            {mode === 'library' ? 'Save' : 'Create component'}
                        </Button>
                        <Button type="button" size="sm" onClick={() => setMode('idle')}>
                            Cancel
                        </Button>
                    </div>
                </form>
            )}
        </ControlGroup>
    );
}
