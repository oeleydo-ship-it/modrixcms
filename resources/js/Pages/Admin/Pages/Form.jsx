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
                                    <input id="field-title" value={form.data.title} required onChange={(e) => {
                                        setDirty(true);
                                        const title = e.target.value;
                                        form.setData((data) => ({ ...data, title, slug: slugTouched ? data.slug : slugify(title) }));
                                    }} />
                                </Field>
                                <Field form={form} name="slug" label="URL" help={page.is_home ? 'The home page is always shown at /.' : 'Leave empty to build it from the name.'}>
                                    <div className="slug-prefix"><span>/</span><input id="field-slug" value={form.data.slug} onChange={(e) => { setSlugTouched(e.target.value !== ''); change('slug', e.target.value); }} /></div>
                                </Field>
                            </div>
                        </div>

                        {page.editor === 'builder' ? (
                            <div className="panel">
                                <h2>Visual builder</h2>
                                <p className="hint">This page is designed in the visual builder: sections, columns and blocks on a live canvas, with device previews, History and Publish.</p>
                                <a className="btn" href={page.builder_url}>Open builder</a>
                            </div>
                        ) : (
                        <div className="panel pb-panel">
                            <h2>Page builder</h2>
                            <p className="hint">Add blocks, drag ⋮⋮ to reorder, click a block to edit it. Hidden blocks stay here but aren’t shown on the site.</p>
                            <PageBuilder blocks={form.data.blocks} onChange={(blocks) => change('blocks', blocks)}
                                definitions={builder.blocks} icons={builder.icons} uploadUrl={route('admin.builder.images')} />
                        </div>
                        )}
                    </div>

                    <div className="builder-side">
                        <div className="panel">
                            <h2>Publish</h2>
                            <p className="hint">Drafts are only visible here.</p>
                            <Check form={form} name="is_published" label="Published" />
                            <Check form={form} name="is_home" label="Use as the home page" />
                            <Check form={form} name="show_in_menu" label="Show in site menu" />
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
