import { Link, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { DangerButton, ErrorFlash, Field } from '@/cms/admin/forms';

function CategoryRow({ category }) {
    const form = useForm({ name: category.name, slug: category.slug });

    return (
        <tr>
            <td colSpan={2}>
                <form className="actions" onSubmit={(e) => { e.preventDefault(); form.put(route('admin.categories.update', category.id), { preserveScroll: true }); }}>
                    <input type="text" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} aria-label="Name" required style={{ maxWidth: 200 }} />
                    <input type="text" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} aria-label="URL" style={{ maxWidth: 180 }} />
                    <button className="btn btn-light btn-sm" type="submit" disabled={form.processing}>Save</button>
                </form>
            </td>
            <td><Link href={route('admin.posts.index', { category: category.id })}>{category.posts_count}</Link></td>
            <td><DangerButton url={route('admin.categories.destroy', category.id)} confirm={`Delete “${category.name}”? Its posts stay, without a category.`}>Delete</DangerButton></td>
        </tr>
    );
}

export default function CategoriesIndex({ categories }) {
    const { errors } = usePage().props;
    const form = useForm({ name: '', slug: '' });

    return (
        <AdminLayout title="Categories" subtitle="Group blog posts by topic." actions={<Link className="btn btn-light" href={route('admin.posts.index')}>← Blog posts</Link>}>
            <div className="grid-2">
                <div className="panel">
                    <div className="table-wrap">
                        <table>
                            <thead><tr><th>Name</th><th>URL</th><th>Posts</th><th></th></tr></thead>
                            <tbody>
                                {categories.length ? categories.map((category) => <CategoryRow key={category.id} category={category} />)
                                    : <tr><td colSpan={4} className="empty">No categories yet.</td></tr>}
                            </tbody>
                        </table>
                    </div>
                    <div style={{ marginTop: 14 }}><ErrorFlash errors={errors} /></div>
                </div>

                <div className="panel">
                    <h2>Add category</h2>
                    <p className="hint">Shown on the blog as a filter.</p>
                    <form onSubmit={(e) => { e.preventDefault(); form.post(route('admin.categories.store'), { preserveScroll: true, onSuccess: () => form.reset() }); }}>
                        <Field form={form} name="name" label="Name" required />
                        <Field form={form} name="slug" label="URL" help="Optional." />
                        <button className="btn" type="submit" disabled={form.processing}>Add category</button>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
