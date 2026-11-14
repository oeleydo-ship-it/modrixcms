/**
 * Payments on published funnel pages.
 *
 * Card details never touch this code or the platform: Stripe's Payment Element
 * collects them in its own iframe, and Stripe.js is loaded from js.stripe.com
 * as PCI rules require. What this module does is the choreography around it —
 * create the server-priced checkout, wait for its payment intent, confirm, and
 * remember the paid checkout so a later upsell step can charge the saved card
 * with one click.
 *
 * Imports nothing from the builder, like the rest of the renderer.
 */

export interface CommerceProduct {
    id: string;
    checkoutUrl?: string | null;
    name: string;
    /** Minor units (cents). */
    price: number;
    compareAt: number | null;
    currency: string;
    description: string | null;
    image: string | null;
    imageAlt: string | null;
    requiresShipping: boolean;
    /** Set for subscription products. */
    recurring: { interval: 'day' | 'week' | 'month' | 'year'; intervalCount: number; trialDays: number | null } | null;
}

/** "/ month", "every 3 months", "/ year". */
export function formatInterval(recurring: NonNullable<CommerceProduct['recurring']>): string {
    return recurring.intervalCount === 1 ? `/ ${recurring.interval}` : `every ${recurring.intervalCount} ${recurring.interval}s`;
}

export interface CommerceRuntime {
    workspace: string;
    checkoutCurrency: string;
    publishableKey: string | null;
    stripeAccount: string | null;
    /** A connected Stripe account that can take charges, and a publishable key. */
    ready: boolean;
    products: Record<string, CommerceProduct>;
}

export function normaliseCommerce(raw: unknown): CommerceRuntime | null {
    if (typeof raw !== 'object' || raw === null) {
        return null;
    }
    const source = raw as Record<string, unknown>;
    if (typeof source['workspace'] !== 'string') {
        return null;
    }

    const products: Record<string, CommerceProduct> = {};
    const rawProducts = source['products'];
    if (typeof rawProducts === 'object' && rawProducts !== null) {
        for (const [id, value] of Object.entries(rawProducts as Record<string, unknown>)) {
            if (typeof value !== 'object' || value === null) {
                continue;
            }
            const product = value as Record<string, unknown>;
            if (typeof product['price'] !== 'number' || typeof product['currency'] !== 'string') {
                continue;
            }
            products[id] = {
                id,
                checkoutUrl: typeof product['checkout_url'] === 'string' ? product['checkout_url'] : null,
                name: typeof product['name'] === 'string' ? product['name'] : 'Product',
                price: product['price'],
                compareAt: typeof product['compare_at'] === 'number' ? product['compare_at'] : null,
                currency: product['currency'],
                description: typeof product['description'] === 'string' && product['description'] !== '' ? product['description'] : null,
                image: typeof product['image'] === 'string' && product['image'] !== '' ? product['image'] : null,
                imageAlt: typeof product['image_alt'] === 'string' ? product['image_alt'] : null,
                requiresShipping: product['requires_shipping'] === true,
                recurring: parseRecurring(product['recurring']),
            };
        }
    }

    const key = typeof source['publishable_key'] === 'string' ? source['publishable_key'] : null;
    const account = typeof source['stripe_account'] === 'string' ? source['stripe_account'] : null;

    return {
        workspace: source['workspace'],
        checkoutCurrency: typeof source['checkout_currency'] === 'string' ? source['checkout_currency'].toUpperCase() : 'USD',
        publishableKey: key,
        stripeAccount: account,
        ready: source['ready'] === true && key !== null && account !== null,
        products,
    };
}

/* -------------------------------------------------------------------------- */
/* Money                                                                       */
/* -------------------------------------------------------------------------- */

const ZERO_DECIMAL = new Set(['BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF']);
const THREE_DECIMAL = new Set(['BHD', 'JOD', 'KWD', 'OMR', 'TND']);

export function formatMoney(amount: number, currency: string): string {
    const code = currency.toUpperCase();
    const value = ZERO_DECIMAL.has(code) ? amount : amount / (THREE_DECIMAL.has(code) ? 1000 : 100);
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency: code }).format(value);
    } catch {
        return `${value.toFixed(ZERO_DECIMAL.has(code) ? 0 : THREE_DECIMAL.has(code) ? 3 : 2)} ${code}`;
    }
}

/* -------------------------------------------------------------------------- */
/* Stripe.js                                                                   */
/* -------------------------------------------------------------------------- */

interface StripeResult {
    error?: { message?: string };
    paymentIntent?: { status: string };
}

export interface StripeElement {
    mount: (target: HTMLElement) => void;
    destroy: () => void;
    on?: (event: string, handler: (event: { complete?: boolean }) => void) => void;
}

export interface StripeElements {
    create: (type: 'payment' | 'address', options?: Record<string, unknown>) => StripeElement;
    submit: () => Promise<{ error?: { message?: string } }>;
    update: (options: Record<string, unknown>) => void;
}

function parseRecurring(value: unknown): CommerceProduct['recurring'] {
    if (typeof value !== 'object' || value === null) {
        return null;
    }
    const source = value as Record<string, unknown>;
    const interval = source['interval'];
    if (interval !== 'day' && interval !== 'week' && interval !== 'month' && interval !== 'year') {
        return null;
    }
    return {
        interval,
        intervalCount: typeof source['interval_count'] === 'number' ? Math.max(1, source['interval_count']) : 1,
        trialDays: typeof source['trial_days'] === 'number' && source['trial_days'] > 0 ? source['trial_days'] : null,
    };
}

export interface StripeLike {
    elements: (options: Record<string, unknown>) => StripeElements;
    confirmPayment: (options: Record<string, unknown>) => Promise<StripeResult>;
    confirmSetup: (options: Record<string, unknown>) => Promise<StripeResult>;
    handleNextAction: (options: { clientSecret: string }) => Promise<StripeResult>;
}

let stripeScript: Promise<void> | null = null;

/** Load Stripe.js once from Stripe's own origin, then build a client for the account. */
export async function loadStripe(publishableKey: string, stripeAccount: string): Promise<StripeLike> {
    const w = window as unknown as { Stripe?: (key: string, options?: Record<string, unknown>) => StripeLike };

    if (!w.Stripe) {
        stripeScript ??= new Promise<void>((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3';
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => {
                stripeScript = null;
                reject(new Error('The secure payment form could not load. Check your connection and refresh.'));
            };
            document.head.appendChild(script);
        });
        await stripeScript;
    }

    if (!w.Stripe) {
        throw new Error('The secure payment form could not load.');
    }

    return w.Stripe(publishableKey, { stripeAccount });
}

/* -------------------------------------------------------------------------- */
/* API                                                                         */
/* -------------------------------------------------------------------------- */

const wait = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

async function request<T>(url: string, init: RequestInit & { token?: string } = {}): Promise<T> {
    const headers: Record<string, string> = { Accept: 'application/json' };
    if (init.body) {
        headers['Content-Type'] = 'application/json';
    }
    if (init.token) {
        headers['Authorization'] = `Bearer ${init.token}`;
    }

    const response = await fetch(url, { ...init, headers, credentials: 'omit' });
    const body = (await response.json().catch(() => ({}))) as { data?: T; message?: unknown; errors?: Record<string, string[]> };

    if (!response.ok) {
        const firstError = body.errors ? Object.values(body.errors)[0]?.[0] : undefined;
        const message = firstError ?? (typeof body.message === 'string' && body.message !== '' ? body.message : null);
        throw new Error(message ?? (response.status === 410 ? 'This checkout has expired. Please refresh the page.' : 'Something went wrong. Please try again.'));
    }

    return body.data as T;
}

export interface CheckoutHandle {
    id: string;
    token: string;
    total: number;
    currency: string;
}

export interface CartRequest {
    items: { product_id: string; quantity: number }[];
    bump_product_ids?: string[];
    coupon_code?: string | null;
    currency?: string;
}

export interface CheckoutQuote {
    currency: string;
    source_currency: string;
    exchange_rate: number | null;
    exchange_rate_date: string | null;
    lines: { product_id: string; name: string; quantity: number; unit_price: number; total: number; kind: string }[];
    subtotal: number;
    discount: number;
    shipping: number;
    tax: number;
    tax_inclusive: boolean;
    tax_label: string;
    total: number;
    requires_shipping: boolean;
    coupon: { code: string; description: string | null } | null;
}

export interface ShippingAddress {
    name?: string;
    line1: string;
    line2?: string;
    city: string;
    state?: string;
    postal_code: string;
    country: string;
}

/** The server's price for a cart — coupon, bump, shipping and tax included. */
export function quoteCart(commerce: CommerceRuntime, cart: CartRequest): Promise<CheckoutQuote> {
    return request<CheckoutQuote>(`/commerce/${encodeURIComponent(commerce.workspace)}/quote`, {
        method: 'POST',
        body: JSON.stringify(cart),
    });
}

/**
 * The signed visitor context the tracker issued for this page view, so the
 * order is attributed to this funnel and its traffic source. Read from the
 * tracker when it is loaded (it honours an identity reset), otherwise from the
 * page's tracking config.
 */
function analyticsContext(): string | undefined {
    const tracker = (window as unknown as { fnl?: { context?: () => string | undefined } }).fnl;
    if (tracker?.context) {
        return tracker.context();
    }
    try {
        const config = JSON.parse(document.getElementById('tracking-config')?.textContent ?? '{}') as { analytics_context?: unknown };
        return typeof config.analytics_context === 'string' ? config.analytics_context : undefined;
    } catch {
        return undefined;
    }
}

export function createCheckout(
    commerce: CommerceRuntime,
    email: string,
    cart: CartRequest,
    shippingAddress: ShippingAddress | null = null,
): Promise<CheckoutHandle> {
    return request<CheckoutHandle>(`/commerce/${encodeURIComponent(commerce.workspace)}/checkouts`, {
        method: 'POST',
        body: JSON.stringify({ email, ...cart, shipping_address: shippingAddress, analytics_context: analyticsContext() }),
    });
}

/** The payment intent is created in the queue; wait for its client secret. */
/**
 * The secret the Payment Element confirms, and what it confirms: a payment
 * (one-time, or a subscription's first invoice) or a setup (a free trial that
 * only saves the card).
 */
export async function waitForCheckoutIntent(
    commerce: CommerceRuntime,
    checkout: CheckoutHandle,
): Promise<{ clientSecret: string; intentType: 'payment' | 'setup' }> {
    for (let attempt = 0; attempt < 40; attempt += 1) {
        const data = await request<{ status: string; client_secret: string | null; intent_type: string | null }>(
            `/commerce/${encodeURIComponent(commerce.workspace)}/checkouts/${encodeURIComponent(checkout.id)}`,
            { token: checkout.token },
        );
        if (data.client_secret) {
            return { clientSecret: data.client_secret, intentType: data.intent_type === 'setup' ? 'setup' : 'payment' };
        }
        if (data.status === 'failed' || data.status === 'abandoned') {
            throw new Error('This checkout could not be started. Please refresh and try again.');
        }
        await wait(attempt < 10 ? 500 : 1500);
    }
    throw new Error('Payment is taking longer than expected. Please try again.');
}

export async function waitForClientSecret(commerce: CommerceRuntime, checkout: CheckoutHandle): Promise<string> {
    for (let attempt = 0; attempt < 40; attempt += 1) {
        const data = await request<{ status: string; client_secret: string | null }>(
            `/commerce/${encodeURIComponent(commerce.workspace)}/checkouts/${encodeURIComponent(checkout.id)}`,
            { token: checkout.token },
        );
        if (data.client_secret) {
            return data.client_secret;
        }
        if (data.status === 'failed' || data.status === 'abandoned') {
            throw new Error('This checkout could not be started. Please refresh and try again.');
        }
        await wait(attempt < 10 ? 500 : 1500);
    }
    throw new Error('Payment is taking longer than usual to start. Please try again in a moment.');
}

/* -------------------------------------------------------------------------- */
/* The paid checkout, remembered for upsells                                    */
/* -------------------------------------------------------------------------- */

const storageKey = (workspace: string) => `fnl-order:${workspace}`;

export function rememberCheckout(commerce: CommerceRuntime, checkout: CheckoutHandle): void {
    try {
        // Session storage: gone when the tab closes, never sent to a server,
        // and scoped to the funnel's own origin.
        window.sessionStorage.setItem(storageKey(commerce.workspace), JSON.stringify({ id: checkout.id, token: checkout.token }));
    } catch {
        // Storage blocked: the upsell will say the order could not be found.
    }
}

function rememberedCheckout(commerce: CommerceRuntime): { id: string; token: string } | null {
    try {
        const raw = window.sessionStorage.getItem(storageKey(commerce.workspace));
        const parsed = raw ? (JSON.parse(raw) as { id?: unknown; token?: unknown }) : null;
        return parsed && typeof parsed.id === 'string' && typeof parsed.token === 'string'
            ? { id: parsed.id, token: parsed.token }
            : null;
    } catch {
        return null;
    }
}

export type UpsellOutcome = { ok: true } | { ok: false; message: string };

/**
 * Charge the card saved at checkout for one product.
 *
 * Accepts the offer, waits for the queued charge, and runs 3-D Secure on the
 * page if the bank asks for it. Accepting the same offer again (a refresh or a
 * second click) returns the same purchase on the server, so it never charges
 * twice.
 */
export async function purchaseUpsell(commerce: CommerceRuntime, productId: string): Promise<UpsellOutcome> {
    const checkout = rememberedCheckout(commerce);
    if (!checkout) {
        return { ok: false, message: 'We could not find your order in this browser tab, so nothing was charged.' };
    }

    const base = `/commerce/${encodeURIComponent(commerce.workspace)}/checkouts/${encodeURIComponent(checkout.id)}/upsells`;

    try {
        let upsell = await request<{ id: string; status: string; client_secret: string | null; message: string | null }>(base, {
            method: 'POST',
            token: checkout.token,
            body: JSON.stringify({ product_id: productId }),
        });

        let authenticated = false;

        for (let attempt = 0; attempt < 90; attempt += 1) {
            if (upsell.status === 'succeeded') {
                return { ok: true };
            }
            if (upsell.status === 'failed') {
                return { ok: false, message: upsell.message ?? 'Your card was declined, so nothing was charged.' };
            }
            if (upsell.status === 'requires_action' && upsell.client_secret && !authenticated) {
                if (!commerce.publishableKey || !commerce.stripeAccount) {
                    return { ok: false, message: 'Your bank needs to confirm this payment, but payments are not set up.' };
                }
                authenticated = true;
                const stripe = await loadStripe(commerce.publishableKey, commerce.stripeAccount);
                const result = await stripe.handleNextAction({ clientSecret: upsell.client_secret });
                if (result.error) {
                    return { ok: false, message: result.error.message ?? 'The payment was not confirmed, so nothing was charged.' };
                }
                if (result.paymentIntent?.status === 'succeeded' || result.paymentIntent?.status === 'processing') {
                    return { ok: true };
                }
            }
            await wait(attempt < 10 ? 600 : 1500);
            upsell = await request(`${base}/${encodeURIComponent(upsell.id)}`, { token: checkout.token });
        }

        return { ok: false, message: 'This is taking longer than usual. Check your email for a receipt before trying again.' };
    } catch (error) {
        return { ok: false, message: error instanceof Error ? error.message : 'Something went wrong, so nothing was charged.' };
    }
}
