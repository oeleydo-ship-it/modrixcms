/**
 * Choosing products from the Commerce catalogue in block settings.
 *
 * Checkout blocks sell one or more products; one-click upsell buttons charge
 * exactly one. Prices shown here are for orientation only — the server prices
 * every checkout and upsell itself.
 */

import { useEffect, useState } from 'react';
import { Select } from '@shared/ui/Select';
import { Checkbox } from '@shared/ui/Checkbox';
import { listProducts, type CatalogProduct } from '../api';
import { PanelNote } from './controls';

function money(amount: number, currency: string): string {
    try {
        return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(amount / 100);
    } catch {
        return `${(amount / 100).toFixed(2)} ${currency}`;
    }
}

function useCatalog() {
    const [products, setProducts] = useState<CatalogProduct[] | null>(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        let cancelled = false;
        listProducts()
            .then((result) => {
                if (!cancelled) {
                    setProducts(result);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setError(true);
                    setProducts([]);
                }
            });
        return () => {
            cancelled = true;
        };
    }, []);

    return { products, error };
}

const label = (product: CatalogProduct) =>
    `${product.name} — ${money(product.salePrice ?? product.price, product.currency)}${product.status !== 'active' ? ' (not active)' : ''}`;

export function ProductSelect({
    value,
    onChange,
    label: fieldLabel = 'Product',
}: {
    value: string;
    onChange: (productId: string | null) => void;
    label?: string;
}) {
    const { products, error } = useCatalog();

    if (products === null) {
        return <PanelNote>Loading your products…</PanelNote>;
    }

    if (products.length === 0) {
        return (
            <PanelNote>
                {error ? 'Products could not be loaded.' : 'No products yet. Create one in Commerce → Products first.'}
            </PanelNote>
        );
    }

    return (
        <Select
            label={fieldLabel}
            size="sm"
            options={[{ value: '', label: 'Choose a product' }, ...products.map((product) => ({ value: product.id, label: label(product) }))]}
            value={value}
            onChange={(event) => onChange(event.currentTarget.value || null)}
        />
    );
}

export function ProductMultiSelect({
    value,
    onChange,
}: {
    value: string[];
    onChange: (productIds: string[]) => void;
}) {
    const { products, error } = useCatalog();

    if (products === null) {
        return <PanelNote>Loading your products…</PanelNote>;
    }

    if (products.length === 0) {
        return (
            <PanelNote>
                {error ? 'Products could not be loaded.' : 'No products yet. Create one in Commerce → Products first.'}
            </PanelNote>
        );
    }

    return (
        <fieldset className="flex flex-col gap-1.5">
            <legend className="mb-1 text-[length:var(--text-xs)] font-medium text-[var(--color-text)]">Products sold</legend>
            {products.map((product) => (
                <Checkbox
                    key={product.id}
                    label={label(product)}
                    checked={value.includes(product.id)}
                    onChange={(event) =>
                        onChange(
                            event.currentTarget.checked
                                ? [...value, product.id]
                                : value.filter((id) => id !== product.id),
                        )
                    }
                />
            ))}
        </fieldset>
    );
}
