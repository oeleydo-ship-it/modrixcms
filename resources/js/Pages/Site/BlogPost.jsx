import { Link, usePage } from '@inertiajs/react';
import Html from '@/cms/site/Html';
import PostCard from '@/cms/site/PostCard';

export default function BlogPost({ post, related }) {
    const { links } = usePage().props;

    return (
        <>
            <article>
                <header className="page-hero article-hero">
                    <div className="wrap narrow">
                        <p className="post-meta">
                            <Link href={links.blog}>Blog</Link>
                            {post.category ? <> · <Link href={post.category.url}>{post.category.name}</Link></> : null}
                        </p>
                        <h1>{post.title}</h1>
                        <p className="byline">
                            {post.author ? <>By {post.author} · </> : null}
                            <time dateTime={post.date}>{post.dateLong}</time>
                        </p>
                    </div>
                </header>

                {post.cover ? <div className="wrap narrow"><img className="article-cover" src={post.cover} alt="" /></div> : null}

                <Html className="wrap narrow prose" html={post.html} />
            </article>

            {related.length ? (
                <section className="section section-tight">
                    <div className="wrap">
                        <h2 className="related-title">Keep reading</h2>
                        <div className="post-grid">{related.map((item) => <PostCard key={item.id} post={item} />)}</div>
                    </div>
                </section>
            ) : null}
        </>
    );
}
