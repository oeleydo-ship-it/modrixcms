/**
 * The data the canvas needs to preview blocks as visitors will see them.
 *
 * On a published page the server embeds a funnel runtime: the funnel's steps,
 * the products a checkout sells, the fields of every connected form. The
 * builder has none of that by default, which left a connected form showing
 * placeholder fields and a product block showing a stand-in. This hook builds
 * the same runtime from the builder's own API calls, refreshed when the page
 * starts referencing a form or product it has not loaded yet.
 *
 * Nothing here enables live behaviour: blocks still refuse to submit, charge
 * or navigate in editable mode. It only feeds what they display.
 */

import { useEffect, useMemo, useState } from 'react';
import type { FunnelRuntime, PageDocument, PublicFormDefinition } from '@renderer';
import type { CommerceProduct } from '@renderer';
import { listProducts, loadFormDefinitions } from './api';
import type { BuilderFunnel } from './funnelContext';

interface References {
    formIds: string[];
    needsProducts: boolean;
}

/** Walk every block for the form ids and product references it carries. */
function references(document: PageDocument): References {
    const formIds = new Set<string>();
    let needsProducts = false;

    const visit = (blocks: PageDocument['sections'][number]['rows'][number]['columns'][number]['blocks']): void => {
        for (const block of blocks) {
            const formId = block.props['formId'];
            if (typeof formId === 'string' && formId !== '') {
                formIds.add(formId);
            }
            if (block.type === 'product' || block.type === 'checkout' || typeof block.props['productId'] === 'string') {
                needsProducts = true;
            }
            visit(block.children ?? []);
        }
    };

    for (const section of document.sections) {
        for (const row of section.rows) {
            for (const column of row.columns) {
                visit(column.blocks);
            }
        }
    }

    return { formIds: [...formIds].sort(), needsProducts };
}

export function useCanvasRuntime(document: PageDocument, funnel: BuilderFunnel | null): FunnelRuntime {
    const refs = useMemo(() => references(document), [document]);
    const formKey = refs.formIds.join(',');

    const [forms, setForms] = useState<Record<string, PublicFormDefinition>>({});
    const [products, setProducts] = useState<Record<string, CommerceProduct> | null>(null);

    useEffect(() => {
        const missing = refs.formIds.filter((id) => !(id in forms));
        if (missing.length === 0) {
            return;
        }
        let cancelled = false;
        void loadFormDefinitions(missing).then((loaded) => {
            if (!cancelled) {
                setForms((current) => ({ ...current, ...loaded }));
            }
        });
        return () => {
            cancelled = true;
        };
        // `forms` is read, not tracked: re-running on every load would refetch
        // a form that failed to load in a loop.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [formKey]);

    useEffect(() => {
        let cancelled = false;
        const refresh = (event: Event) => {
            const formId = (event as CustomEvent<{ formId?: string }>).detail?.formId;
            if (!formId || !refs.formIds.includes(formId)) return;
            void loadFormDefinitions([formId]).then((loaded) => {
                if (!cancelled) setForms((current) => ({ ...current, ...loaded }));
            });
        };
        window.addEventListener('fnl:form-updated', refresh);
        return () => { cancelled = true; window.removeEventListener('fnl:form-updated', refresh); };
    }, [formKey]);

    useEffect(() => {
        if (!refs.needsProducts) {
            return;
        }
        let cancelled = false;
        const refresh = () => { void listProducts()
            .then((catalog) => {
                if (cancelled) {
                    return;
                }
                setProducts(
                    Object.fromEntries(
                        catalog.map((product) => [
                            product.id,
                            {
                                id: product.id,
                                name: product.name,
                                price: product.salePrice ?? product.price,
                                compareAt: product.salePrice !== null ? product.price : null,
                                currency: product.currency,
                                description: product.description ?? null,
                                image: product.image ?? null,
                                imageAlt: product.imageAlt ?? null,
                                checkoutUrl: product.checkoutUrl ?? null,
                                requiresShipping: false,
                                recurring: null,
                            },
                        ]),
                    ),
                );
            })
            .catch(() => {
                if (!cancelled) {
                    setProducts({});
                }
            }); };
        refresh();
        window.addEventListener('focus', refresh);
        return () => {
            cancelled = true;
            window.removeEventListener('focus', refresh);
        };
    }, [refs.needsProducts]);

    return useMemo<FunnelRuntime>(() => {
        // Step "URLs" are placeholders — the canvas never navigates — but their
        // order is what lets a progress bar preview this step's position.
        const stepUrls: Record<string, string> = {};
        for (const step of [...(funnel?.steps ?? [])].sort((a, b) => a.position - b.position)) {
            stepUrls[step.id] = '#';
        }

        return {
            nextStepUrl: null,
            stepUrls,
            currentStepId: funnel?.currentStepId ?? null,
            commerce:
                products === null
                    ? null
                    : { workspace: '', checkoutCurrency: Object.values(products)[0]?.currency ?? 'USD', publishableKey: null, stripeAccount: null, ready: false, products },
            forms,
        };
    }, [funnel, products, forms]);
}
