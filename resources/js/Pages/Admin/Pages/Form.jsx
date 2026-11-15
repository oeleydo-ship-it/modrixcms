import { useEffect, useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Check, ErrorFlash, Field, slugify } from '@/cms/admin/forms';
import PageBuilder, { normaliseBlocks } from '@/cms/admin/PageBuilder';

export default function PageForm({ page, builder }) {
    const { errors } = usePage().props;
    const exists = page.id !== null;
    const [slugTouched, setSlugTouched] = useState(Boolean(page.slug));
    const [dirty, setDirty] = useState(false);
    const form = useForm({
        title: page.title ?? '',
        slug: page.slug ?? '',
        blocks: normaliseBlocks(page.blocks, builder.blocks),
        is_published: page.is_published,
        is_home: page.is_home,
        show_in_menu: page.show_in_menu,
        show_site_header: page.show_site_header ?? true,
        show_site_footer: page.show_site_footer ?? true,
        menu_order: page.menu_order ?? 0,
        meta_title: page.meta_title ?? '',
        meta_description: page.meta_description ?? '',
    });

    // Warn before leaving with unsaved changes.
    useEffect(() => {
        if (!dirty) return undefined;
        const beforeUnload = (e) => e.preventDefault();
        const removeInertia = router.on('before', (event) => {
            if (event.detail.visit.method === 'get' && !window.confirm('Leave this page? Your changes aren’t saved.')) {
                event.preventDefault();
            }
        });
        window.addEventListener('beforeunload', beforeUnload);
        return () => { window.removeEventListener('beforeunload', beforeUnload); removeInertia(); };
    }, [dirty]);

    const submit = (e) => {
        e?.preventDefault();
        form.transform((data) => ({ ...data, blocks: JSON.stringify(data.blocks) }));
        const options = { preserveScroll: true, onSuccess: () => setDirty(false) };
        exists ? form.put(route('admin.pages.update', page.id), options) : form.post(route('admin.pages.store'), options);
    };

    const preview = () => {
        const post = document.createElement('form');
        post.method = 'POST';
        post.action = route('admin.pages.preview');
        post.target = '_blank';
        const fields = { _token: document.querySelector('meta[name="csrf-token"]').content, blocks: JSON.stringify(form.data.blocks), title: form.data.title };
        Object.entries(fields).forEach(([name, value]) => {
            const input = document.createElement('input');
            Object.assign(input, { type: 'hidden', name, value });
            post.append(input);
        });
        document.body.append(post);
        post.submit();
        post.remove();
    };

    const change = (name, value) => { setDirty(true); form.setData(name, value); };

    return (
        <AdminLayout
            title={exists ? 'Edit page' : 'New page'}
            subtitle={page.is_home ? 'This is the home page of the site.' : 'Build the page from blocks.'}
            actions={<>
                <Link className="btn btn-light" href={route('admin.pages.index')}>← All pages</Link>
                <button className="btn btn-light" type="button" onClick={preview}>Preview</button>
                {exists && page.is_published && page.url ? <a className="btn btn-light" href={page.url} target="_blank" rel="noopener">View page</a> : null}
                <button className="btn" type="button" onClick={submit} disabled={form.processing}>{exists ? 'Save page' : 'Create page'}</button>
            </>}
        >
            <ErrorFlash errors={errors} keys={['blocks', 'page']} />
            <form onSubmit={submit}>
                <div className="grid-2 builder-layout">
                    <div>
                        <div className="panel">
                            <div className="row">
                                <Field form={form} name="title" label="Page name" help="Shown in the admin and in menus.">
                                    <input id="field-title" type="text" value={form.data.title} placeholder="e.g. Home" required onChange={(e) => {
                                        setDirty(true);
                                        const title = e.target.value;
                                        form.setData((data) => ({ ...data, title, slug: slugTouched ? data.slug : slugify(title) }));
                                    }} />
                                </Field>
                                <Field form={form} name="slug" label="URL" help={page.is_home ? 'The home page is always shown at /.' : 'Leave empty to build it from the name.'}>
                                    <div className="slug-prefix"><span>/</span><input id="field-slug" type="text" value={form.data.slug} placeholder="page-slug" autoComplete="off" onChange={(e) => { setSlugTouched(e.target.value !== ''); change('slug', e.target.value); }} /></div>
                                </Field>
                            </div>
                        </div>

                        {page.editor === 'builder' ? (
                            <div className="panel">
                                <h2>Visual builder</h2>
                                <p className="hint">This page is designed in the visual builder: sections, columns and blocks on a live canvas, with device previews, History and Publish.</p>
                                {!page.builder_live && page.switch_back_url ? (
                                    <p className="hint"><strong>Visitors still see the block version</strong> of this page until you publish it from the builder.</p>
                                ) : null}
                                <div className="actions">
                                    <a className="btn" href={page.builder_url}>Open builder</a>
                                    {page.switch_back_url ? (
                                        <button className="btn btn-light" type="button" onClick={() => {
                                            if (window.confirm('Go back to the block editor? Visitors will see the block version again. Your visual-builder design is kept if you switch later.')) {
                                                router.post(page.switch_back_url);
                                            }
                                        }}>Switch back to block editor</button>
                                    ) : null}
                                </div>
                            </div>
                        ) : (
                        <>
                        <ConvertPanel page={page} dirty={dirty} />
                        <div className="panel pb-panel">
                            <h2>Page builder</h2>
                            <p className="hint">Add blocks, apply ready-made section presets, drag ⋮⋮ to reorder, or click a block to customize its fields and layout options.</p>
                            <PageBuilder
                                blocks={form.data.blocks}
                                onChange={(blocks) => change('blocks', blocks)}
                                definitions={builder.blocks}
                                icons={builder.icons}
                                categories={builder.categories}
                                presets={builder.presets}
                                uploadUrl={route('admin.builder.images')}
                            />
                        </div>
                        </>
                        )}
                    </div>

                    <div className="builder-side">
                        <div className="panel">
                            <h2>Publish</h2>
                            <p className="hint">Drafts are only visible here.</p>
                            <Check form={form} name="is_published" label="Published" />
                            <Check form={form} name="is_home" label="Use as the home page" />
                            <Check form={form} name="show_in_menu" label="Show in site menu" />
                            {page.editor === 'builder' ? <>
                                <Check form={form} name="show_site_header" label="Show the site header" />
                                <Check form={form} name="show_site_footer" label="Show the site footer" />
                            </> : null}
                            <Field form={form} name="menu_order" label="Menu position" type="number" min={0} />
                            <button className="btn" type="submit" disabled={form.processing}>{exists ? 'Save page' : 'Create page'}</button>
                        </div>
                        <div className="panel">
                            <h2>SEO</h2>
                            <p className="hint">How the page shows in search results.</p>
                            <Field form={form} name="meta_title" label="Meta title" maxLength={200} help="Defaults to the page name." />
                            <Field form={form} name="meta_description" label="Meta description" type="textarea" maxLength={300} rows={3} />
                        </div>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}

/**
 * Offers the visual builder from the block editor: convert this page (its blocks become
 * builder sections in the theme's colours, and are kept so it can switch back), or, for
 * a page not saved yet, start a new page in the builder instead.
 */
function ConvertPanel({ page, dirty }) {
    const convert = () => {
        const message = page.has_builder_design
            ? 'Switch this page to the visual builder? Your earlier builder design is still there.'
            : 'Convert this page to the visual builder? Its blocks become editable sections in your theme’s colours. Visitors keep seeing the current version until you publish from the builder, and you can switch back.';

        if (dirty) {
            window.alert('Save your changes first, then convert.');
            return;
        }

        if (window.confirm(message)) {
            router.post(page.convert_url);
        }
    };

    return (
        <div className="panel convert-panel">
            <h2>Design it visually</h2>
            {page.convert_url ? (
                <>
                    <p className="hint">Edit this page on a live canvas with sections, columns, device previews and themed designs.</p>
                    <button className="btn" type="button" onClick={convert}>
                        {page.has_builder_design ? 'Switch to visual builder' : 'Convert to visual builder'}
                    </button>
                </>
            ) : (
                <>
                    <p className="hint">Prefer a live canvas? Start this page in the visual builder instead.</p>
                    <button className="btn" type="button" onClick={() => router.post(route('admin.pages.store-builder'))}>Start in visual builder</button>
                </>
            )}
        </div>
    );
}
