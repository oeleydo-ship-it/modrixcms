import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AdminLayout, { ReferenceEmptyArt } from '@/cms/admin/AdminLayout';
import { DangerButton, Pager } from '@/cms/admin/forms';
import { AutoBlogPanel } from '@/cms/admin/PostAi';

export function PostStatus({ status }) {
    const tone = { Published: 'green', Scheduled: 'purple' }[status] ?? 'gray';

    return <span className={`badge ${tone}`}>{status}</span>;
}

export default function PostsIndex({ posts, categories, filters, aiConfigured }) {
    const [autoOpen, setAutoOpen] = useState(false);
    const [q, setQ] = useState(filters.q ?? '');
    const [category, setCategory] = useState(filters.category ?? '');

    const filter = (e) => {
        e.preventDefault();
        router.get(route('admin.posts.index'), { q: q || undefined, category: category || undefined }, { preserveState: true });
    };

    return (
        <AdminLayout
            title="Blog posts"
            subtitle="Write, schedule and publish articles."
            actions={<>
                <button type="button" className="btn btn-light" onClick={() => setAutoOpen((open) => !open)}>✦ AI auto blog</button>
                <Link className="btn btn-light" href={route('admin.categories.index')}>Categories</Link>
                <Link className="btn" href={route('admin.posts.create')}>New post</Link>
            </>}
        >
            {autoOpen ? <AutoBlogPanel categories={categories} aiConfigured={aiConfigured} onClose={() => setAutoOpen(false)} /> : null}

            <div className="panel">
                <form className="toolbar" onSubmit={filter}>
                    <input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search posts…" />
                    <select value={category} onChange={(e) => setCategory(e.target.value)}>
                        <option value="">All categories</option>
                        {categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}
                    </select>
                    <button className="btn btn-light" type="submit">Filter</button>
                </form>

                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                            {posts.data.length ? posts.data.map((post) => (
                                <tr key={post.id}>
                                    <td className="title"><Link href={route('admin.posts.edit', post.id)}>{post.title}</Link><div className="sub">/blog/{post.slug}</div></td>
                                    <td>{post.category ?? '—'}</td>
                                    <td><PostStatus status={post.status} /></td>
                                    <td className="sub">{post.date ?? '—'}</td>
                                    <td>
                                        <div className="actions">
                                            {post.status === 'Published' ? <a className="btn btn-light btn-sm" href={post.url} target="_blank" rel="noopener">View</a> : null}
                                            <Link className="btn btn-light btn-sm" href={route('admin.posts.edit', post.id)}>Edit</Link>
                                            <DangerButton url={route('admin.posts.destroy', post.id)} confirm={`Delete “${post.title}”? This can’t be undone.`}>Delete</DangerButton>
                                        </div>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan={5} className="empty">
                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '24px 0' }}>
                                            <ReferenceEmptyArt />
                                            <strong style={{ color: 'var(--ink)', fontSize: 15, marginBottom: 4 }}>Your blog posts will show here</strong>
                                            <span style={{ maxWidth: 320, marginBottom: 14 }}>No posts found. This is where you’ll write, schedule, and publish articles for your store.</span>
                                            <Link className="btn" href={route('admin.posts.create')}>Create post</Link>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Pager paginator={posts} />
            </div>
        </AdminLayout>
    );
}
