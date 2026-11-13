import BlockList from '@/cms/site/BlockList';

/** Any page built with the page builder, including the home page. */
export default function Page({ blocks, preview }) {
    return (
        <>
            {preview ? <div className="preview-bar">Preview — not saved. Close this tab to keep editing.</div> : null}
            <BlockList blocks={blocks} />
        </>
    );
}
