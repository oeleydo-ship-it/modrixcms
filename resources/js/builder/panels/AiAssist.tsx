/**
 * "Write with AI" for the selected block.
 *
 * The author describes what the block should say; the server returns a props
 * patch (never HTML) and it is applied through the builder's normal edit path —
 * so a generation is one undo step, and it is saved by autosave through the
 * same document validation as anything typed by hand.
 *
 * Only shown for blocks that carry copy. The set mirrors
 * `App\Domain\Ai\Schema\BlockContentSchema::supportedTypes()`; if they drift,
 * the server rejects the request with a clear message rather than writing
 * anything wrong.
 */

import { useState } from 'react';
import { Sparkles } from 'lucide-react';
import { Button } from '@shared/ui/Button';
import { Select } from '@shared/ui/Select';
import { Textarea } from '@shared/ui/Textarea';
import { useToast } from '@shared/ui/Toast';
import type { BlockData, BlockType } from '@renderer';
import { generateBlockContent } from '../api';
import { useBuilderStore } from '../store';

export const AI_SUPPORTED: ReadonlySet<BlockType> = new Set<BlockType>([
    'heading',
    'paragraph',
    'button',
    'popup_trigger',
    'progress_bar',
    'image',
    'testimonial',
    'faq',
    'feature_cards',
    'pricing_table',
    'email_optin',
    'phone_input',
    'form',
    'checkout',
    'product',
    'countdown',
    'booking',
    'navigation',
    'accordion',
    'tabs',
]);

const TONES = [
    { value: '', label: 'Match the brief' },
    { value: 'friendly', label: 'Friendly' },
    { value: 'professional', label: 'Professional' },
    { value: 'bold', label: 'Bold' },
    { value: 'playful', label: 'Playful' },
    { value: 'luxury', label: 'Premium' },
    { value: 'urgent', label: 'Urgent' },
];

export function AiAssist({
    block,
    onApply,
}: {
    block: BlockData;
    onApply: (patch: Record<string, unknown>) => void;
}) {
    const toast = useToast();
    const pageId = useBuilderStore((state) => state.pageId);
    const [brief, setBrief] = useState('');
    const [tone, setTone] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    if (!AI_SUPPORTED.has(block.type)) {
        return null;
    }

    const generate = async (): Promise<void> => {
        if (brief.trim().length < 3) {
            setError('Tell the assistant a little about what this block should say.');
            return;
        }

        setBusy(true);
        setError(null);

        try {
            const patch = await generateBlockContent(pageId, {
                blockType: block.type,
                brief: brief.trim(),
                tone: tone === '' ? null : tone,
                props: block.props,
            });
            onApply(patch);
            toast.success('Content written', 'Press Ctrl+Z to undo it.');
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'The AI assistant is unavailable right now.');
        } finally {
            setBusy(false);
        }
    };

    return (
        <div className="flex flex-col gap-2 rounded-md border border-[var(--color-border)] bg-[var(--color-surface-sunken)] p-3">
            <div className="flex items-center gap-1.5 text-[length:var(--text-sm)] font-medium text-[var(--color-text)]">
                <Sparkles aria-hidden className="h-4 w-4 text-[var(--color-text-brand)]" />
                Write with AI
            </div>
            <Textarea
                label="What should it say?"
                rows={3}
                value={brief}
                placeholder="e.g. A headline for a free 5-day email course on meal prep for busy parents"
                onChange={(event) => setBrief(event.currentTarget.value)}
            />
            <Select
                label="Tone"
                size="sm"
                options={TONES}
                value={tone}
                onChange={(event) => setTone(event.currentTarget.value)}
            />
            {error && (
                <p role="alert" className="text-[length:var(--text-xs)] text-[var(--color-danger,#dc2626)]">
                    {error}
                </p>
            )}
            <Button size="sm" onClick={() => void generate()} loading={busy}>
                <Sparkles aria-hidden size={14} />
                Generate
            </Button>
        </div>
    );
}
