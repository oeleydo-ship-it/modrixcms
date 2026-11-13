import { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { Field, FileField, slugify } from '@/cms/admin/forms';

export default function PostForm({ post, categories, blogUrl }) {
    const exists = post.id !== null;
    const [slugTouched, setSlugTouched] = useState(Boolean(post.slug));
    const form = useForm({
        title: post.title ?? '',
        slug: post.slug ?? '',
        excerpt: post.excerpt ?? '',
        content: post.content ?? '',
        status: post.status,
        published_at: post.published_at ?? '',
        category_id: post.category_id ?? '',
        meta_description: post.meta_description ?? '',
        cover_image: null,
        remove_cover: false,
        _method: exists ? 'put' : 'post',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(exists ? route('admin.posts.update', post.id) : route('admin.posts.store'), { forceFormData: true, preserveScroll: true });
    };

    const setTitle = (value) => {
        form.setData((data) => ({ ...data, title: value, slug: slugTouched ? data.slug : slugify(value) }));
    };

    return (
        <AdminLayout
            title={exists ? 'Edit post' : 'New post'}
            actions={<>
                <Link className="btn btn-light" href={route('admin.posts.index')}>← All posts</Link>
                {exists && post.status === 'published' && post.url ? <a className="btn btn-light" href={post.url} target="_blank" rel="noopener">View post</a> : null}
            </>}
        >
            <form onSubmit={submit}>
                <div className="grid-2">
                    <div className="panel">
                        <Field form={form} name="title" label="Title" required>
                            <input id="field-title" value={form.data.title} onChange={(e) => setTitle(e.target.value)} required />
                        </Field>
                        <Field form={form} name="slug" label="URL" help="Leave empty to build it from the title.">
                            <div className="slug-prefix"><span>{blogUrl}/</span><input id="field-slug" value={form.data.slug} onChange={(e) => { setSlugTouched(e.target.value !== ''); form.setData('slug', e.target.value); }} /></div>
                        </Field>
                        <Field form={form} name="excerpt" label="Excerpt" type="textarea" rows={3} maxLength={500} help="Short summary for the blog list and social sharing." />
                        <Field form={form} name="content" label="Content" type="textarea" className="editor-field" help="Markdown: ## Heading, **bold**, *italic*, [link](https://…), - list items, `code`." >
                            <textarea id="field-content" className="editor" value={form.data.content} onChange={(e) => form.setData('content', e.target.value)} />
                        </Field>
                    </div>

                    <div>
                        <div className="panel">
                            <h2>Publish</h2>
                            <p className="hint">Scheduled posts go live on their date.</p>
                            <Field form={form} name="status" label="Status" type="select" options={{ draft: 'Draft', published: 'Published', scheduled: 'Scheduled' }} />
                            {form.data.status === 'scheduled' ? <Field form={form} name="published_at" label="Publish on" type="datetime-local" /> : null}
                            <Field form={form} name="category_id" label="Category" help={<Link href={route('admin.categories.index')}>Manage categories</Link>}>
                                <select id="field-category_id" value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)}>
                                    <option value="">No category</option>
                                    {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
                                </select>
                            </Field>
                            <button className="btn" type="submit" disabled={form.processing}>{exists ? 'Save post' : 'Create post'}</button>
                        </div>

                        <div className="panel">
                            <h2>Cover image</h2>
                            <p className="hint">JPG, PNG or WebP, up to 4 MB.</p>
                            {post.cover ? (
                                <>
                                    <div className="cover-preview"><img src={post.cover} alt="" /></div>
                                    <label className="check"><input type="checkbox" checked={form.data.remove_cover} onChange={(e) => form.setData('remove_cover', e.target.checked)} /> Remove cover</label>
                                </>
                            ) : null}
                            <FileField form={form} name="cover_image" accept="image/jpeg,image/png,image/webp" />
                        </div>

                        <div className="panel">
                            <h2>SEO</h2>
                            <Field form={form} name="meta_description" label="Meta description" type="textarea" rows={3} maxLength={300} help="Defaults to the excerpt." />
                        </div>
                    </div>
                </div>
            </form>
        </AdminLayout>
    );
}
