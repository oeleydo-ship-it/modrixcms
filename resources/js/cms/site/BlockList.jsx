import { usePage } from '@inertiajs/react';
import { blockComponent } from '../runtime';
import ErrorBoundary from '../ErrorBoundary';
import { coreBlocks } from './blocks';

/**
 * Draws a page's blocks. Each block gets { block, data, anchor }; data has been prepared
 * by the server (resolved links, *_html, *_svg, *_url). A block that crashes is skipped.
 */
export default function BlockList({ blocks }) {
    const { url } = usePage();

    return (blocks ?? []).map((block) => {
        const Block = blockComponent(block.type, coreBlocks);

        if (!Block) {
            return null;
        }

        return (
            <ErrorBoundary key={block.id} label={`The "${block.type}" block`} resetKey={url}>
                <Block block={block} data={block.data} anchor={block.anchor ?? undefined} />
            </ErrorBoundary>
        );
    });
}
