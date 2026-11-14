import { useMemo } from 'react';
import { useDraggable } from '@dnd-kit/core';
import { ArrowLeft, ArrowRight, CheckCircle2, CircleAlert, Gauge, Mail, Plus, ShoppingCart, FileText, type LucideIcon } from 'lucide-react';
import type { PageDocument } from '@renderer';
import type { BuilderFunnel } from '../funnelContext';
import type { DragPayload } from '../dnd';
import { FUNNEL_WIDGETS, adjacentStep, funnelConnections, funnelWidgetConfig, orderedSteps, type FunnelWidgetId } from '../funnelWidgets';

const ICONS: Record<FunnelWidgetId, LucideIcon> = {
    continue: ArrowRight, back: ArrowLeft, lead: FileText, optin: Mail,
    progress: Gauge, checkout: ShoppingCart, upsell: Plus, decline: ArrowRight,
};

function Widget({ id, funnel, onAdd }: { id: FunnelWidgetId; funnel: BuilderFunnel; onAdd: (id: FunnelWidgetId) => void }) {
    const widget = FUNNEL_WIDGETS.find(item => item.id === id)!;
    const disabled = funnelWidgetConfig(id, funnel) === null;
    const payload: DragPayload = { source: 'widget', widgetId: id };
    const { setNodeRef, listeners, attributes, isDragging } = useDraggable({ id: `widget:${id}`, data: payload, disabled });
    const Icon = ICONS[id];
    return (
        <button type="button" ref={setNodeRef} {...listeners} {...attributes} disabled={disabled}
            onClick={() => onAdd(id)} className={`fnl-funnel-widget${isDragging ? ' is-dragging' : ''}`}
            title={disabled ? (id === 'back' ? 'This is the first step.' : 'This is the final step. Add another step from the funnel dashboard.') : `Click or drag to add ${widget.label}`}>
            <Icon size={16} aria-hidden />
            <span><strong>{widget.label}</strong><small>{widget.description}</small></span>
            <Plus size={13} aria-hidden />
        </button>
    );
}

export function FunnelPanel({ funnel, document, onAdd, onOpenStep, onSelectBlock }: {
    funnel: BuilderFunnel;
    document: PageDocument;
    onAdd: (id: FunnelWidgetId) => void;
    onOpenStep: (pageId: string) => void;
    onSelectBlock: (blockId: string) => void;
}) {
    const connections = useMemo(() => funnelConnections(document, funnel), [document, funnel]);
    const issues = connections.filter(connection => connection.issues.length > 0).length;
    const next = adjacentStep(funnel, 1);
    return (
        <div className="fnl-funnel-panel">
            <section aria-label="Funnel steps">
                <h2 className="fnl-panel-title">{funnel.name}</h2>
                <p className="fnl-funnel-note">Choose a step to edit. Your current draft is saved before switching.</p>
                <ol className="fnl-funnel-steps">
                    {orderedSteps(funnel).map((step, index) => {
                        const current = step.id === funnel.currentStepId;
                        return (
                            <li key={step.id}>
                                <button type="button" className="fnl-funnel-step" aria-current={current ? 'step' : undefined}
                                    disabled={current || !step.pageId} onClick={() => step.pageId && onOpenStep(step.pageId)}
                                    title={!step.pageId ? 'This step has no editable page.' : `Edit ${step.name}`}>
                                    <span className="fnl-funnel-step-number">{index + 1}</span>
                                    <span><strong>{step.name}</strong><small>{current ? 'Editing this step' : step.type.replace(/_/g, ' ')}</small></span>
                                    {current ? <CheckCircle2 size={14} aria-hidden /> : <ArrowRight size={14} aria-hidden />}
                                </button>
                            </li>
                        );
                    })}
                </ol>
                <p className="fnl-funnel-note">{next ? `Next in sequence: ${next.name}. Live links skip unpublished steps.` : 'This is the final step. Forms can show a thank-you message.'}</p>
            </section>
            <section aria-label="Funnel widgets">
                <h2 className="fnl-panel-title">Funnel widgets</h2>
                <p className="fnl-funnel-note">Click to insert at your selection, or drag onto the page. Select the widget to configure it.</p>
                <div className="fnl-funnel-widgets">
                    {FUNNEL_WIDGETS.map(widget => <Widget key={widget.id} id={widget.id} funnel={funnel} onAdd={onAdd} />)}
                </div>
            </section>
            <section aria-label="Funnel connections">
                <h2 className="fnl-panel-title">Connections <span>{issues ? `${issues} need setup` : `${connections.length} configured`}</span></h2>
                <p className="fnl-funnel-note">Checks this page’s configured targets. Publish destination steps and connect payments before going live. A form’s own redirect takes priority.</p>
                {connections.length === 0 ? <p className="fnl-funnel-note">Add a funnel widget to connect this page.</p> : (
                    <ul className="fnl-funnel-connections">
                        {connections.map(connection => (
                            <li key={connection.blockId}>
                                <button type="button" onClick={() => onSelectBlock(connection.blockId)} className="fnl-funnel-connection">
                                    {connection.issues.length ? <CircleAlert size={15} aria-hidden /> : <CheckCircle2 size={15} aria-hidden />}
                                    <span><strong>{connection.label}</strong>
                                        {connection.issues.map(issue => <small className="fnl-funnel-issue" key={issue}>{issue}</small>)}
                                        {connection.messages.map(message => <small key={message}>{message}</small>)}
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </div>
    );
}
