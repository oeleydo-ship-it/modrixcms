import { Link } from '@inertiajs/react';
import PostCard from '@/cms/site/PostCard';

export default function BlogIndex({ posts, category, categories, allUrl }) {
    return (
        <>
            <section className="page-hero">
                <div className="wrap">
                    <p className="eyebrow">Blog</p>
                    <h1>{category?.name ?? 'News & guides'}</h1>
                    {categories.length ? (
                        <nav className="cat-filter" aria-label="Categories">
                            <Link href={allUrl} className={!category ? 'on' : undefined}>All</Link>
                            {categories.map((item) => (
                                <Link key={item.slug} href={item.url} className={category?.slug === item.slug ? 'on' : undefined}>{item.name}</Link>
                            ))}
                        </nav>
                    ) : null}
                </div>
            </section>

            <section className="section section-tight">
                <div className="wrap">
                    {posts.data.length === 0 ? (
                        <p className="empty-note">No posts here yet.</p>
                    ) : (
                        <>
                            <div className="post-grid">{posts.data.map((post) => <PostCard key={post.id} post={post} />)}</div>
                            {posts.prev_page_url || posts.next_page_url ? (
                                <div className="pager">
                                    {posts.prev_page_url ? <Link className="btn btn-ghost btn-sm" href={posts.prev_page_url} rel="prev">← Newer</Link> : null}
                                    {posts.next_page_url ? <Link className="btn btn-ghost btn-sm" href={posts.next_page_url} rel="next">Older →</Link> : null}
                                </div>
                            ) : null}
                        </>
                    )}
                </div>
            </section>
        </>
    );
}
