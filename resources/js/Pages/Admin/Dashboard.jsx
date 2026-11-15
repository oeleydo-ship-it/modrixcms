import { Link, usePage } from '@inertiajs/react';
import AdminLayout, { ReferenceEmptyArt } from '@/cms/admin/AdminLayout';
import { PostStatus } from './Posts/Index';

export default function Dashboard({ stats, recentPosts, recentPages }) {
    const { auth } = usePage().props;

    return (
        <AdminLayout
            title="Dashboard"
            subtitle={`Welcome back, ${auth.user.name}.`}
            actions={<>
                <Link className="btn btn-light" href={route('admin.pages.create')}>New page</Link>
                <Link className="btn" href={route('admin.posts.create')}>New post</Link>
            </>}
        >
            <div className="stats">
                {Object.entries(stats).map(([label, value]) => <div key={label} className="stat"><small>{label}</small><b>{value}</b></div>)}
            </div>

            <div className="grid-2">
                <div className="panel">
                    <h2>Recent posts</h2>
                    <p className="hint">Latest edits on the blog.</p>
                    <div className="table-wrap">
                        <table>
                            <tbody>
                                {recentPosts.length ? recentPosts.map((post) => (
                                    <tr key={post.id}>
                                        <td className="title"><Link href={route('admin.posts.edit', post.id)}>{post.title}</Link><div className="sub">Updated {post.updated}</div></td>
                                        <td><PostStatus status={post.status} /></td>
                                    </tr>
                                )) : (
                                    <tr>
                                        <td className="empty">
                                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '16px 0' }}>
                                                <ReferenceEmptyArt />
                                                <strong style={{ color: 'var(--ink)', fontSize: 14, marginBottom: 4 }}>Your blog posts will show here</strong>
                                                <span>No posts yet. <Link href={route('admin.posts.create')}>Write the first one</Link>.</span>
                                                <div style={{ marginTop: 14 }}>
                                                    <Link className="btn" href={route('admin.posts.create')}>Create post</Link>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="panel">
                    <h2>Recent pages</h2>
                    <p className="hint">Latest edits on pages.</p>
                    <table>
                        <tbody>
                            {recentPages.length ? recentPages.map((page) => (
                                <tr key={page.id}><td className="title"><Link href={route('admin.pages.edit', page.id)}>{page.title}</Link><div className="sub">/{page.slug}</div></td></tr>
                            )) : (
                                <tr>
                                    <td className="empty">
                                        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '16px 0' }}>
                                            <ReferenceEmptyArt />
                                            <strong style={{ color: 'var(--ink)', fontSize: 14, marginBottom: 4 }}>Your pages will show here</strong>
                                            <span>No pages yet.</span>
                                            <div style={{ marginTop: 14 }}>
                                                <Link className="btn" href={route('admin.pages.create')}>Create page</Link>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}
