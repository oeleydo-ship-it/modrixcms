/**
 * Choosing which popup a button or popup-trigger block opens, by name rather
 * than by pasting an id. A block pointing at a deleted popup keeps its value
 * and says so.
 */

import { useEffect, useState } from 'react';
import { Select } from '@shared/ui/Select';
import { listPopups, type PopupOption } from '../api';
import { PanelNote } from './controls';

export function PopupPicker({ value, onChange }: { value: string; onChange: (popupId: string | null) => void }) {
    const [popups, setPopups] = useState<PopupOption[] | null>(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let cancelled = false;
        listPopups()
            .then((result) => {
                if (!cancelled) {
                    setPopups(result);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setFailed(true);
                    setPopups([]);
                }
            });
        return () => {
            cancelled = true;
        };
    }, []);

    if (popups === null) {
        return <Select label="Popup" size="sm" options={[{ value: '', label: 'Loading popups…' }]} value="" disabled onChange={() => undefined} />;
    }

    const known = value === '' || popups.some((popup) => popup.id === value);
    const selected = popups.find((popup) => popup.id === value);

    return (
        <>
            <Select
                label="Popup"
                size="sm"
                hint="Popups are created under Popups in the dashboard."
                options={[
                    { value: '', label: 'Choose a popup' },
                    ...(!known ? [{ value, label: 'A popup that no longer exists' }] : []),
                    ...popups.map((popup) => ({ value: popup.id, label: popup.status === 'published' ? popup.name : `${popup.name} (${popup.status})` })),
                ]}
                value={value}
                onChange={(event) => onChange(event.currentTarget.value === '' ? null : event.currentTarget.value)}
            />
            {failed && <PanelNote>Your popups could not be loaded. Reload the builder to try again.</PanelNote>}
            {!failed && popups.length === 0 && <PanelNote>This workspace has no popups yet. Create one under Popups, then pick it here.</PanelNote>}
            {!known && <PanelNote>That popup was deleted, so this opens nothing.</PanelNote>}
            {selected && selected.status !== 'published' && <PanelNote>That popup is not published, so nothing opens on the live page yet.</PanelNote>}
        </>
    );
}
