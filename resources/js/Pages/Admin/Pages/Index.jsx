import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/cms/admin/AdminLayout';
import { DangerButton, ErrorFlash, Pager } from '@/cms/admin/forms';

export default function PagesIndex({ pages, filters }) {
    const { errors } = usePage().props;
    const [q, setQ] = useState(filters.q ?? '');

    return (
        <AdminLayout
            title="Pages"
            subtitle="Every page, including the home page, is built from blocks."
            actions={<>
                <Link className="btn btn-light" href={route('admin.pages.create', { template: 'landing' })}>New landing page</Link>
                <Link className="btn" href={route('admin.pages.create')}>New page</Link>
            </>}
        >
            <ErrorFlash errors={errors} />
            <div className="panel">
                <form className="toolbar" onSubmit={(e) => { e.preventDefault(); router.get(route('admin.pages.index'), { q: q || undefined }, { preserveState: true }); }}>
                    <input type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search pages…" />
                    <button className="btn btn-light" type="submit">Search</button>
                </form>

                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Title</th><th>Status</th><th>Menu</th><th>Updated</th><th></th></tr></thead>
                        <tbody>
                            {pages.data.length ? pages.data.map((page) => (
                                <tr key={page.id}>
                                    <td className="title">
                                        <Link href={route('admin.pages.edit', page.id)}>{page.title}</Link>
                                        {page.is_home ? <> <span className="badge purple">Home page</span></> : null}
                                        <div className="sub">{page.is_home ? '/' : `/${page.slug}`} · {page.block_count} blocks</div>
                                    </td>
                                    <td><span className={`badge ${page.is_published ? 'green' : 'gray'}`}>{page.is_published ? 'Published' : 'Draft'}</span></td>
                                    <td>{page.show_in_menu ? `Yes (${page.menu_order})` : '—'}</td>
                                    <td className="sub">{page.updated}</td>
                                    <td>
                                        <div className="actions">
                                            {page.is_published ? <a className="btn btn-light btn-sm" href={page.url} target="_blank" rel="noopener">View</a> : null}
                                            <Link className="btn btn-light btn-sm" href={route('admin.pages.edit', page.id)}>Edit</Link>
                                            {!page.is_home ? <DangerButton url={route('admin.pages.destroy', page.id)} confirm={`Delete “${page.title}”? This can’t be undone.`}>Delete</DangerButton> : null}
                                        </div>
                                    </td>
                                </tr>
                            )) : <tr><td colSpan={5} className="empty">No pages found.</td></tr>}
                        </tbody>
                    </table>
                </div>
                <Pager paginator={pages} />
            </div>
        </AdminLayout>
    );
}
