/**
 * The live booking flow behind the calendar and booking blocks (§23).
 *
 * Open times come from the public slots endpoint for the chosen service; the
 * visitor picks one, leaves their details and books. Times are shown in the
 * visitor's own time zone and sent back as UTC. In the builder the flow is a
 * working preview, but booking itself is switched off.
 */

import { useEffect, useMemo, useState, type CSSProperties, type FormEvent } from 'react';
import { bool, optionalStr } from '../props';
import { bookingSuccessOutcome, useFunnel } from '../funnel';
import { Placeholder, type BlockComponentProps } from './shared';

interface Slot {
    starts_at: string;
    ends_at: string;
    staff_id: string;
}

interface SlotsPayload {
    service: { id: string; name: string; description: string | null; duration_minutes: number; location: string };
    staff: { id: string; name: string }[];
    timezone: string;
    slots: Slot[];
}

const DAYS = 7;

function BookingPreview({ block }: BlockComponentProps) {
    return <div style={{ display: 'grid', gap: '16px', padding: '24px', border: '1px solid #cbd5e1', borderRadius: '12px', background: '#f8fafc', color: '#172033' }}>
        <strong>Choose a time</strong>
        <p style={{ margin: 0 }}>Preview a confirmed appointment and continue through the funnel.</p>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '12px' }}>
            {['09:00', '11:30', '14:00'].map(time => <button key={time} type="button" data-fnl-preview-next={optionalStr(block.props, 'previewNextHref') ?? '#preview-complete'} style={{ padding: '12px 18px', border: '1px solid #94a3b8', borderRadius: '8px', background: 'white', color: '#172033', cursor: 'pointer' }}>Try {time}</button>)}
        </div>
        <small>Demo times only. No appointment is created. Select a service and staff availability after installing.</small>
    </div>;
}

const fieldStyle: CSSProperties = {
    width: '100%',
    padding: '10px 12px',
    borderRadius: '8px',
    border: '1px solid var(--color-border-strong, #cbd5e1)',
    background: 'var(--color-surface, #fff)',
    color: 'inherit',
    font: 'inherit',
    boxSizing: 'border-box',
};

const primaryStyle: CSSProperties = {
    padding: '11px 20px',
    borderRadius: '8px',
    border: '1px solid transparent',
    background: 'var(--fnl-accent, #4f46e5)',
    color: '#fff',
    font: 'inherit',
    fontWeight: 600,
    cursor: 'pointer',
};

const quietStyle: CSSProperties = {
    padding: '8px 12px',
    borderRadius: '8px',
    border: '1px solid var(--color-border-strong, #cbd5e1)',
    background: 'transparent',
    color: 'inherit',
    font: 'inherit',
    cursor: 'pointer',
};

function visitorZone(): string {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    } catch {
        return 'UTC';
    }
}

/** YYYY-MM-DD of an instant in a time zone. */
function dateIn(instant: Date, zone: string): string {
    return new Intl.DateTimeFormat('en-CA', { timeZone: zone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(instant);
}

function addDays(ymd: string, days: number): string {
    const date = new Date(`${ymd}T12:00:00Z`);
    date.setUTCDate(date.getUTCDate() + days);
    return date.toISOString().slice(0, 10);
}

function dayLabel(ymd: string): string {
    return new Intl.DateTimeFormat(undefined, { timeZone: 'UTC', weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(`${ymd}T12:00:00Z`));
}

interface FlowProps {
    serviceId: string;
    staffId: string | null;
    showStaffPicker: boolean;
    successProps: Record<string, unknown>;
    preview: boolean;
}

function BookingFlow({ serviceId, staffId, showStaffPicker, successProps, preview }: FlowProps) {
    const runtime = useFunnel();
    const zone = useMemo(visitorZone, []);
    const today = useMemo(() => dateIn(new Date(), zone), [zone]);
    const [from, setFrom] = useState(today);
    const [staff, setStaff] = useState<string>(staffId ?? '');
    const [payload, setPayload] = useState<SlotsPayload | null>(null);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [reload, setReload] = useState(0);
    const [selected, setSelected] = useState<Slot | null>(null);
    const [details, setDetails] = useState({ name: '', email: '', phone: '', notes: '', website: '' });
    const [submitting, setSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState<string | null>(null);
    const [done, setDone] = useState<string | null>(null);

    useEffect(() => setStaff(staffId ?? ''), [staffId]);

    useEffect(() => {
        const controller = new AbortController();
        const query = new URLSearchParams({ from, days: String(DAYS), timezone: zone });
        if (staff !== '') {
            query.set('staff', staff);
        }
        setLoadError(null);
        fetch(`/f/booking/${encodeURIComponent(serviceId)}/slots?${query.toString()}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error(response.status === 404 ? 'This booking calendar is not available.' : 'Open times could not be loaded.');
                }
                const body = (await response.json()) as { data: SlotsPayload };
                setPayload(body.data);
            })
            .catch((error: unknown) => {
                if (!controller.signal.aborted) {
                    setLoadError(error instanceof Error ? error.message : 'Open times could not be loaded.');
                }
            });
        return () => controller.abort();
    }, [serviceId, from, staff, zone, reload]);

    const days = useMemo(() => {
        const grouped = new Map<string, Slot[]>();
        for (let i = 0; i < DAYS; i += 1) {
            grouped.set(addDays(from, i), []);
        }
        for (const slot of payload?.slots ?? []) {
            grouped.get(dateIn(new Date(slot.starts_at), zone))?.push(slot);
        }
        return [...grouped.entries()];
    }, [payload, from, zone]);

    const time = useMemo(() => new Intl.DateTimeFormat(undefined, { timeZone: zone, hour: 'numeric', minute: '2-digit' }), [zone]);
    const longTime = useMemo(() => new Intl.DateTimeFormat(undefined, { timeZone: zone, dateStyle: 'full', timeStyle: 'short' }), [zone]);

    const submit = async (event: FormEvent): Promise<void> => {
        event.preventDefault();
        if (selected === null || preview) {
            return;
        }
        setSubmitting(true);
        setSubmitError(null);
        try {
            const response = await fetch(`/f/booking/${encodeURIComponent(serviceId)}/appointments`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    starts_at: selected.starts_at,
                    staff_id: staff !== '' ? staff : selected.staff_id,
                    timezone: zone,
                    name: details.name,
                    email: details.email,
                    phone: details.phone || null,
                    notes: details.notes || null,
                    ...(details.website !== '' ? { website: details.website } : {}),
                }),
            });
            const body = (await response.json().catch(() => ({}))) as {
                data?: { message?: string; redirect_url?: string | null };
                message?: string;
                errors?: Record<string, string[]>;
            };
            if (response.status === 201) {
                const outcome = bookingSuccessOutcome(successProps, runtime, body.data ?? {});
                if (outcome.kind === 'redirect') {
                    window.location.assign(outcome.href);
                    return;
                }
                setDone(outcome.kind === 'message' ? outcome.message : 'You are booked in.');
                return;
            }
            if (body.errors?.['starts_at']) {
                setSelected(null);
                setReload((value) => value + 1);
            }
            const first = body.errors ? Object.values(body.errors)[0]?.[0] : undefined;
            setSubmitError(first ?? body.message ?? 'The booking could not be completed. Please try again.');
        } catch {
            setSubmitError('The booking could not be completed. Please check your connection and try again.');
        } finally {
            setSubmitting(false);
        }
    };

    const box: CSSProperties = { display: 'flex', flexDirection: 'column', gap: '14px', width: '100%' };

    if (done !== null) {
        return (
            <div role="status" style={{ ...box, padding: '20px', borderRadius: '10px', background: 'var(--color-success-soft, #ecfdf5)', color: 'var(--color-success-text, #065f46)' }}>
                <strong>{done}</strong>
                {selected && <span>{longTime.format(new Date(selected.starts_at))}</span>}
            </div>
        );
    }

    return (
        <div style={box}>
            {payload && (
                <div>
                    <div style={{ fontWeight: 600, fontSize: '1.1em' }}>{payload.service.name}</div>
                    <div style={{ opacity: 0.75, fontSize: '0.9em' }}>
                        {payload.service.duration_minutes} min · {payload.service.location} · Times in {zone.replace(/_/g, ' ')}
                    </div>
                    {payload.service.description && <p style={{ margin: '6px 0 0' }}>{payload.service.description}</p>}
                </div>
            )}

            {showStaffPicker && staffId === null && (payload?.staff.length ?? 0) > 1 && (
                <label style={{ display: 'flex', flexDirection: 'column', gap: '4px', fontSize: '0.9em' }}>
                    With
                    <select
                        style={fieldStyle}
                        value={staff}
                        onChange={(event) => {
                            setStaff(event.currentTarget.value);
                            setSelected(null);
                        }}
                    >
                        <option value="">Anyone available</option>
                        {payload?.staff.map((member) => (
                            <option key={member.id} value={member.id}>
                                {member.name}
                            </option>
                        ))}
                    </select>
                </label>
            )}

            {selected === null ? (
                <>
                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: '8px' }}>
                        <button type="button" style={quietStyle} disabled={from <= today} onClick={() => setFrom(addDays(from, -DAYS))}>
                            ← Earlier
                        </button>
                        <button type="button" style={quietStyle} onClick={() => setFrom(addDays(from, DAYS))}>
                            Later →
                        </button>
                    </div>
                    {loadError && <p role="alert">{loadError}</p>}
                    {!loadError && payload === null && <p>Loading open times…</p>}
                    {payload && (
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))', gap: '10px' }}>
                            {days.map(([day, slots]) => (
                                <div key={day} style={{ display: 'flex', flexDirection: 'column', gap: '6px' }}>
                                    <div style={{ fontWeight: 600, fontSize: '0.9em' }}>{dayLabel(day)}</div>
                                    {slots.length === 0 ? (
                                        <span style={{ opacity: 0.6, fontSize: '0.85em' }}>No times</span>
                                    ) : (
                                        slots.map((slot) => (
                                            <button key={`${slot.starts_at}-${slot.staff_id}`} type="button" style={quietStyle} onClick={() => setSelected(slot)}>
                                                {time.format(new Date(slot.starts_at))}
                                            </button>
                                        ))
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                    {payload && payload.slots.length === 0 && <p style={{ margin: 0 }}>No open times in these days. Try later dates.</p>}
                </>
            ) : (
                <form onSubmit={(event) => void submit(event)} style={box}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: '8px', alignItems: 'center', flexWrap: 'wrap' }}>
                        <strong>{longTime.format(new Date(selected.starts_at))}</strong>
                        <button type="button" style={quietStyle} onClick={() => setSelected(null)}>
                            Change time
                        </button>
                    </div>
                    <input style={fieldStyle} required maxLength={160} placeholder="Your name" autoComplete="name" value={details.name} onChange={(event) => setDetails({ ...details, name: event.currentTarget.value })} />
                    <input style={fieldStyle} required type="email" maxLength={255} placeholder="Email" autoComplete="email" value={details.email} onChange={(event) => setDetails({ ...details, email: event.currentTarget.value })} />
                    <input style={fieldStyle} type="tel" maxLength={40} placeholder="Phone (optional)" autoComplete="tel" value={details.phone} onChange={(event) => setDetails({ ...details, phone: event.currentTarget.value })} />
                    <textarea style={{ ...fieldStyle, minHeight: '80px' }} maxLength={2000} placeholder="Anything we should know? (optional)" value={details.notes} onChange={(event) => setDetails({ ...details, notes: event.currentTarget.value })} />
                    <input
                        type="text"
                        name="website"
                        tabIndex={-1}
                        autoComplete="off"
                        aria-hidden="true"
                        style={{ position: 'absolute', left: '-10000px', width: '1px', height: '1px' }}
                        value={details.website}
                        onChange={(event) => setDetails({ ...details, website: event.currentTarget.value })}
                    />
                    {submitError && <p role="alert" style={{ margin: 0 }}>{submitError}</p>}
                    {preview && <p style={{ margin: 0, opacity: 0.7 }}>Booking is switched off in the builder. Publish the page to take bookings.</p>}
                    <button type="submit" style={primaryStyle} disabled={submitting || preview}>
                        {submitting ? 'Booking…' : 'Confirm booking'}
                    </button>
                </form>
            )}
        </div>
    );
}

export function CalendarBlock({ block, mode }: BlockComponentProps) {
    const runtime = useFunnel();
    if (runtime?.templatePreview) return <BookingPreview block={block} mode={mode} />;
    const serviceId = optionalStr(block.props, 'serviceId');
    if (serviceId === null) {
        return mode === 'editable' ? <Placeholder label="Calendar" detail="Pick a booking service in the properties panel." /> : null;
    }
    return <BookingFlow serviceId={serviceId} staffId={null} showStaffPicker successProps={block.props} preview={mode === 'editable'} />;
}

export function BookingBlock({ block, mode }: BlockComponentProps) {
    const runtime = useFunnel();
    if (runtime?.templatePreview) return <BookingPreview block={block} mode={mode} />;
    const serviceId = optionalStr(block.props, 'serviceId');
    if (serviceId === null) {
        return mode === 'editable' ? <Placeholder label="Booking" detail="Pick a booking service in the properties panel." /> : null;
    }
    return (
        <BookingFlow
            serviceId={serviceId}
            staffId={optionalStr(block.props, 'staffId')}
            showStaffPicker={bool(block.props, 'showStaffPicker')}
            successProps={block.props}
            preview={mode === 'editable'}
        />
    );
}
