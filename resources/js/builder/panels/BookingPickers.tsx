/**
 * Choosing the booking service (and optionally the staff member) behind a
 * calendar or booking block, by name rather than by pasting an id.
 */

import { useEffect, useState } from 'react';
import { Select } from '@shared/ui/Select';
import { listBookingServices, listBookingStaff, type BookingOption } from '../api';
import { PanelNote } from './controls';

function useOptions(load: () => Promise<BookingOption[]>): { options: BookingOption[] | null; failed: boolean } {
    const [options, setOptions] = useState<BookingOption[] | null>(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let cancelled = false;
        load()
            .then((result) => {
                if (!cancelled) {
                    setOptions(result);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setFailed(true);
                    setOptions([]);
                }
            });
        return () => {
            cancelled = true;
        };
    }, [load]);

    return { options, failed };
}

export function BookingServicePicker({ value, onChange }: { value: string; onChange: (serviceId: string | null) => void }) {
    const { options, failed } = useOptions(listBookingServices);

    if (options === null) {
        return <Select label="Service" size="sm" options={[{ value: '', label: 'Loading services…' }]} value="" disabled onChange={() => undefined} />;
    }

    const known = value === '' || options.some((option) => option.id === value);
    const selected = options.find((option) => option.id === value);

    return (
        <>
            <Select
                label="Service"
                size="sm"
                hint="Services and staff hours are set up under Booking in the dashboard."
                options={[
                    { value: '', label: 'Choose a service' },
                    ...(!known ? [{ value, label: 'A service that no longer exists' }] : []),
                    ...options.map((option) => ({ value: option.id, label: option.active ? option.name : `${option.name} (inactive)` })),
                ]}
                value={value}
                onChange={(event) => onChange(event.currentTarget.value === '' ? null : event.currentTarget.value)}
            />
            {failed && <PanelNote>Your booking services could not be loaded. Reload the builder to try again.</PanelNote>}
            {!failed && options.length === 0 && <PanelNote>This workspace has no booking services yet. Create one under Booking, then pick it here.</PanelNote>}
            {!known && <PanelNote>That service was deleted, so this block shows nothing on the live page.</PanelNote>}
            {selected && !selected.active && <PanelNote>That service is switched off, so visitors cannot book it yet.</PanelNote>}
        </>
    );
}

export function BookingStaffPicker({ serviceId, value, onChange }: { serviceId: string; value: string; onChange: (staffId: string | null) => void }) {
    const { options } = useOptions(listBookingStaff);
    const eligible = (options ?? []).filter((option) => serviceId === '' || option.serviceIds.includes(serviceId));

    return (
        <Select
            label="Staff"
            size="sm"
            disabled={options === null}
            options={[
                { value: '', label: 'Anyone who offers the service' },
                ...(value !== '' && !eligible.some((option) => option.id === value) ? [{ value, label: 'Someone who no longer offers it' }] : []),
                ...eligible.map((option) => ({ value: option.id, label: option.name })),
            ]}
            value={value}
            onChange={(event) => onChange(event.currentTarget.value === '' ? null : event.currentTarget.value)}
        />
    );
}
