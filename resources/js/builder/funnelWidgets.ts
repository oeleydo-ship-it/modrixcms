import type { BlockData, BlockType, PageDocument } from '@renderer';
import type { BuilderFunnel, BuilderFunnelStep } from './funnelContext';

export const FUNNEL_WIDGETS = [
    { id: 'continue', label: 'Next step', description: 'Continue to the next published step', type: 'button' },
    { id: 'back', label: 'Previous step', description: 'Return to the preceding step', type: 'button' },
    { id: 'lead', label: 'Lead form', description: 'Capture details, then continue', type: 'form' },
    { id: 'optin', label: 'Email opt-in', description: 'Capture an email, then continue', type: 'email_optin' },
    { id: 'progress', label: 'Step progress', description: 'Follow this page’s funnel position', type: 'progress_bar' },
    { id: 'checkout', label: 'Checkout', description: 'Take payment, then continue', type: 'checkout' },
    { id: 'upsell', label: 'Accept offer', description: 'Charge the card saved at checkout', type: 'button' },
    { id: 'decline', label: 'Decline offer', description: 'Skip the offer and continue', type: 'button' },
] as const satisfies ReadonlyArray<{ id: string; label: string; description: string; type: BlockType }>;

export type FunnelWidgetId = typeof FUNNEL_WIDGETS[number]['id'];

export function isFunnelWidgetId(value: unknown): value is FunnelWidgetId {
    return FUNNEL_WIDGETS.some(widget => widget.id === value);
}

export function orderedSteps(funnel: BuilderFunnel): BuilderFunnelStep[] {
    return [...funnel.steps].sort((a, b) => a.position - b.position);
}

export function adjacentStep(funnel: BuilderFunnel, offset: -1 | 1): BuilderFunnelStep | null {
    const steps = orderedSteps(funnel);
    const index = steps.findIndex(step => step.id === funnel.currentStepId);
    return index < 0 ? null : steps[index + offset] ?? null;
}

/** Native block props: save, undo, templates and the public renderer use the same document. */
export function funnelWidgetConfig(id: FunnelWidgetId, funnel: BuilderFunnel): {
    type: BlockType; name: string; props: Record<string, unknown>;
} | null {
    const widget = FUNNEL_WIDGETS.find(item => item.id === id);
    if (!widget) return null;
    if (id === 'back' && !adjacentStep(funnel, -1)) return null;
    if (['continue', 'upsell', 'decline'].includes(id) && !adjacentStep(funnel, 1)) return null;

    const props: Record<string, unknown> = (() => {
        switch (id) {
            case 'continue': return { label: 'Continue', action: 'next_step', targetStepId: null, fullWidth: true, iconRight: 'arrow-right' };
            case 'back': return { label: 'Back', action: 'next_step', targetStepId: adjacentStep(funnel, -1)?.id, variant: 'secondary', iconLeft: 'arrow-left' };
            case 'lead':
            case 'optin': return { submitLabel: 'Continue', successAction: 'next_step', targetStepId: null, successMessage: 'Thanks — we got it.' };
            case 'progress': return { source: 'funnel_step', label: 'Step {current} of {total}', showPercentage: true };
            case 'checkout': return { submitLabel: 'Complete order', saveCardForUpsells: true, showOrderSummary: true };
            case 'upsell': return { label: 'Yes, add this to my order', action: 'upsell_purchase', productId: null, targetStepId: null, fullWidth: true };
            case 'decline': return { label: 'No thanks, continue', action: 'next_step', targetStepId: null, variant: 'link' };
        }
    })();
    return { type: widget.type, name: widget.label, props };
}

export function funnelStepOptions(funnel: BuilderFunnel | null, targetId = '') {
    const steps = funnel ? orderedSteps(funnel) : [];
    return [
        { value: '', label: 'The next published step' },
        ...steps.flatMap((step, index) => step.id === funnel?.currentStepId ? [] : [{ value: step.id, label: `${index + 1}. ${step.name}` }]),
        ...(targetId && !steps.some(step => step.id === targetId && step.id !== funnel?.currentStepId)
            ? [{ value: targetId, label: 'Unavailable step — choose another' }] : []),
    ];
}

export interface FunnelConnection {
    blockId: string;
    label: string;
    messages: string[];
    issues: string[];
}

/** Check stored connections, including nested blocks. This does not claim live payment readiness. */
export function funnelConnections(document: PageDocument, funnel: BuilderFunnel): FunnelConnection[] {
    const connections: FunnelConnection[] = [];
    const destination = (props: Record<string, unknown>): { message?: string; issue?: string } => {
        const targetId = typeof props['targetStepId'] === 'string' ? props['targetStepId'] : '';
        const target = targetId ? funnel.steps.find(step => step.id === targetId) : adjacentStep(funnel, 1);
        if (!target) return { issue: targetId ? 'Destination step no longer exists.' : 'There is no following step. Choose a destination or change the action.' };
        if (target.id === funnel.currentStepId) return { issue: 'This action returns to the current step.' };
        return { message: targetId ? `Goes to ${target.name}` : `Next: ${target.name} (skips unpublished steps)` };
    };
    const visit = (blocks: BlockData[]) => {
        for (const block of blocks) {
            const props = block.props;
            const messages: string[] = [], issues: string[] = [];
            const capture = ['form', 'email_optin', 'phone_input'].includes(block.type);
            const button = block.type === 'button' && ['next_step', 'upsell_purchase'].includes(String(props['action']));
            const progress = block.type === 'progress_bar' && props['source'] === 'funnel_step';
            const checkout = block.type === 'checkout';
            const booking = block.type === 'booking' || block.type === 'calendar';
            if (capture || button || progress || checkout || booking) {
                if (capture) {
                    if (typeof props['formId'] !== 'string' || !props['formId']) issues.push('Connect a form in block settings.');
                    else messages.push('Form selected');
                }
                if (booking) {
                    if (!props['serviceId']) issues.push('Choose a booking service and configure staff availability.');
                    else messages.push('Booking service selected');
                }
                if (button || (capture && props['successAction'] === 'next_step') || (booking && props['confirmationAction'] === 'next_step')) {
                    const result = destination(props);
                    if (result.message) messages.push(result.message);
                    if (result.issue) {
                        // A final-step form deliberately shows its fallback message after capture.
                        if ((capture || booking) && !props['targetStepId'] && !adjacentStep(funnel, 1)) messages.push('Final step: shows the fallback message');
                        else issues.push(result.issue);
                    }
                }
                if (props['action'] === 'upsell_purchase' && !props['productId']) issues.push('Choose the offer product. A previous checkout must save the card.');
                if (checkout) {
                    if (!Array.isArray(props['productIds']) || !props['productIds'].some(id => typeof id === 'string' && id !== '')) issues.push('Choose checkout products in block settings.');
                    else messages.push('Products selected');
                    messages.push(adjacentStep(funnel, 1) ? 'After payment: next published step' : 'After payment: order confirmation');
                }
                if (progress) messages.push('Counts this step among published funnel steps');
                connections.push({ blockId: block.id, label: block.name || block.type.replace(/_/g, ' '), messages, issues });
            }
            visit(block.children ?? []);
        }
    };
    for (const section of document.sections) for (const row of section.rows) for (const column of row.columns) visit(column.blocks);
    return connections;
}
