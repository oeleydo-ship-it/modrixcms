/**
 * The one-click "Yes, add it to my order" button.
 *
 * Shared by the button block and pricing-plan buttons so both behave the same:
 * charge the card saved at checkout, show progress while the bank answers,
 * move on to the next step when the charge succeeds, and stay put with a clear
 * message when it does not.
 */

import { useState, type CSSProperties, type ReactNode } from 'react';
import { purchaseUpsell } from '../commerce';
import { useFunnel } from '../funnel';

export function UpsellButton({
    productId,
    thenHref,
    style,
    children,
    fullWidth = false,
}: {
    productId: string;
    thenHref: string | null;
    style: CSSProperties;
    children: ReactNode;
    fullWidth?: boolean;
}) {
    const runtime = useFunnel();
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const accept = async (): Promise<void> => {
        const commerce = runtime?.commerce;
        if (busy || !commerce) {
            return;
        }
        setBusy(true);
        setError(null);

        const outcome = await purchaseUpsell(commerce, productId);

        if (outcome.ok) {
            if (thenHref) {
                window.location.assign(thenHref);
                return;
            }
            setBusy(false);
            return;
        }

        setError(outcome.message);
        setBusy(false);
    };

    return (
        <span style={{ display: fullWidth ? 'block' : 'inline-flex', flexDirection: 'column', alignItems: 'inherit', gap: '8px' }}>
            <button
                type="button"
                style={{ ...style, opacity: busy ? 0.75 : 1, cursor: busy ? 'progress' : 'pointer' }}
                onClick={() => void accept()}
                aria-busy={busy}
                disabled={busy}
            >
                {busy ? 'Adding to your order…' : children}
            </button>
            {error && (
                <span role="alert" style={{ fontSize: '14px', color: '#b91c1c', fontWeight: 500 }}>
                    {error}
                </span>
            )}
        </span>
    );
}
