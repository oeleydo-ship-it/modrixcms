import { useMemo, useState } from 'react';
import { Sparkles } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Modal } from '@shared/ui/Modal';
import { Select } from '@shared/ui/Select';
import { Textarea } from '@shared/ui/Textarea';
import { useToast } from '@shared/ui/Toast';
import { normaliseDocument, PageRenderer, serialiseDocument, type PageDocument } from '@renderer';
import { useBuilderStore } from '../store';

const REDESIGN_GOALS = [
    {
        label: 'Modernize full layout',
        prompt: 'Redesign the full page with stronger visual hierarchy, clear section spacing, sharper headlines, and prominent primary calls to action while preserving our core content.',
    },
    {
        label: 'Upgrade copy for conversions',
        prompt: 'Keep the overall structure of this page, rewrite all sections around customer outcomes, trust proof, and clear calls to action.',
    },
    {
        label: 'Add social proof & FAQ',
        prompt: 'Refresh the hero and main sections of this page and add customer proof metrics, testimonials, and a concise FAQ section.',
    },
    {
        label: 'Editorial refresh',
        prompt: 'Restyle the full page into a clean, minimal editorial layout with crisp copy, balanced feature cards, and a closing CTA.',
    },
];

const TONE_OPTIONS = [
    { value: 'Modern & clear', label: 'Modern & clear' },
    { value: 'Warm editorial', label: 'Warm editorial' },
    { value: 'Bold commerce', label: 'Bold commerce' },
    { value: 'Minimal studio', label: 'Minimal studio' },
    { value: 'Consulting & trust', label: 'Consulting & trust' },
];

interface GeneratedPlanPage {
    title?: string;
    outline?: string[];
    section_count?: number;
    document?: unknown;
}

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/** Full-page AI modification dialog that applies editable sections straight to the builder canvas. */
export function AiPageModal({
    open,
    pageId,
    pageTitle,
    onClose,
}: {
    open: boolean;
    pageId: string;
    pageTitle: string;
    onClose: () => void;
}) {
    const toast = useToast();
    const [prompt, setPrompt] = useState('');
    const [tone, setTone] = useState(TONE_OPTIONS[0]!.value);
    const [generating, setGenerating] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [generatedPage, setGeneratedPage] = useState<GeneratedPlanPage | null>(null);

    const previewDocument = useMemo<PageDocument | null>(() => {
        if (!generatedPage?.document) {
            return null;
        }
        return normaliseDocument(generatedPage.document);
    }, [generatedPage]);

    const handleGenerate = async (): Promise<void> => {
        if (prompt.trim().length < 10) {
            setError('Describe what to modify or redesign across this page (at least 10 characters).');
            return;
        }

        setGenerating(true);
        setError(null);

        try {
            const currentDocument = useBuilderStore.getState().document;
            const response = await fetch('/admin/ai-generator/generate', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    prompt: prompt.trim(),
                    mode: 'redesign',
                    target_page_id: Number(pageId),
                    current_document: serialiseDocument(currentDocument),
                    tone,
                }),
            });

            const json = (await response.json()) as {
                message?: string;
                data?: { pages?: GeneratedPlanPage[] };
            };

            if (!response.ok) {
                throw new Error(json?.message || 'AI page generation failed.');
            }

            const firstPage = json?.data?.pages?.[0];
            if (!firstPage?.document) {
                throw new Error('The AI response did not return a page layout. Please try again.');
            }

            setGeneratedPage(firstPage);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to modify this page with AI.');
        } finally {
            setGenerating(false);
        }
    };

    const handleApplyToCanvas = (): void => {
        if (!generatedPage?.document) {
            return;
        }

        const state = useBuilderStore.getState();
        const nextDocument = normaliseDocument(generatedPage.document);

        state.commit(nextDocument);
        state.select(null);

        toast.success(
            'AI redesign applied to canvas',
            'Every section and block remains editable. Press Ctrl+Z to undo, or click Save to keep your draft.',
        );
        onClose();
    };

    return (
        <Modal
            open={open}
            onClose={() => {
                if (!generating) {
                    onClose();
                }
            }}
            title={`Modify “${pageTitle || 'page'}” with AI`}
            description="Describe changes across the whole page. AI replaces or upgrades the layout using native builder sections and blocks so every headline, button, image, and card stays 100% editable."
            size="full"
            footer={
                <>
                    <Button variant="secondary" disabled={generating} onClick={onClose}>
                        Cancel
                    </Button>
                    <Button
                        variant={previewDocument ? 'secondary' : 'primary'}
                        loading={generating}
                        disabled={prompt.trim().length < 10}
                        onClick={() => void handleGenerate()}
                    >
                        <Sparkles aria-hidden size={14} />
                        {previewDocument ? 'Regenerate preview' : 'Generate full-page redesign'}
                    </Button>
                    {previewDocument ? (
                        <Button disabled={generating} onClick={handleApplyToCanvas}>
                            Apply to builder canvas
                        </Button>
                    ) : null}
                </>
            }
        >
            <div className="grid gap-4 lg:grid-cols-12">
                <div className="flex flex-col gap-3 lg:col-span-5">
                    <div>
                        <p className="mb-1.5 text-[length:var(--text-xs)] font-medium text-[var(--color-text-muted)]">
                            Quick goals
                        </p>
                        <div className="flex flex-wrap gap-1.5">
                            {REDESIGN_GOALS.map((goal) => (
                                <Button
                                    key={goal.label}
                                    size="xs"
                                    variant="secondary"
                                    disabled={generating}
                                    onClick={() => setPrompt(goal.prompt)}
                                >
                                    {goal.label}
                                </Button>
                            ))}
                        </div>
                    </div>

                    <Textarea
                        label="Full-page instructions"
                        rows={6}
                        value={prompt}
                        disabled={generating}
                        placeholder="e.g. Rewrite the hero with a bolder offer, turn our benefits into 3 feature cards, add customer proof stats and a 4-question FAQ while keeping our brand details…"
                        onChange={(event) => setPrompt(event.currentTarget.value)}
                    />

                    <Select
                        label="Style & tone"
                        options={TONE_OPTIONS}
                        value={tone}
                        disabled={generating}
                        onChange={(event) => setTone(event.currentTarget.value)}
                    />

                    {error ? (
                        <div
                            role="alert"
                            className="rounded-md border border-red-200 bg-red-50 p-3 text-[length:var(--text-sm)] text-red-700"
                        >
                            {error}{' '}
                            {error.toLowerCase().includes('not configured') ? (
                                <a className="font-semibold underline" href="/admin/settings/ai" target="_blank" rel="noopener">
                                    Configure AI API →
                                </a>
                            ) : null}
                        </div>
                    ) : null}

                    {generatedPage?.outline?.length ? (
                        <div className="rounded-md border border-[var(--color-border)] bg-[var(--color-surface-sunken)] p-3">
                            <p className="mb-1.5 text-[length:var(--text-xs)] font-semibold uppercase tracking-wide text-[var(--color-text-muted)]">
                                Editable sections ({generatedPage.outline.length})
                            </p>
                            <ol className="list-inside list-decimal space-y-1 text-[length:var(--text-sm)]">
                                {generatedPage.outline.map((item, idx) => (
                                    <li key={`${idx}-${item}`}>{item}</li>
                                ))}
                            </ol>
                        </div>
                    ) : null}
                </div>

                <div className="lg:col-span-7">
                    <p className="mb-1.5 text-[length:var(--text-xs)] font-medium text-[var(--color-text-muted)]">
                        Live preview before applying to canvas
                    </p>
                    <div
                        className="overflow-y-auto rounded-lg border border-[var(--color-border)] bg-[var(--site-background,#ffffff)]"
                        style={{ maxHeight: '60vh', minHeight: 320 }}
                    >
                        {previewDocument ? (
                            <PageRenderer document={previewDocument} mode="static" />
                        ) : (
                            <div className="flex min-h-[320px] items-center justify-center p-6 text-center text-[length:var(--text-sm)] text-[var(--color-text-muted)]">
                                Describe your changes on the left and click Generate full-page redesign. You can inspect every section here before applying it to the editable builder canvas.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </Modal>
    );
}
