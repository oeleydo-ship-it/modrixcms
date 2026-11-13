import { Link } from '@inertiajs/react';

export default function PostCard({ post }) {
    return (
        <article className="post-card">
            <Link className="post-cover" href={post.url} tabIndex={-1} aria-hidden="true">
                {post.cover ? <img src={post.cover} alt="" loading="lazy" /> : <span className="post-cover-blank">{post.title.slice(0, 1)}</span>}
            </Link>
            <div className="post-body">
                <p className="post-meta">
                    {post.category ? <><Link href={post.category.url}>{post.category.name}</Link> · </> : null}
                    <time dateTime={post.date}>{post.dateLabel}</time>
                </p>
                <h3><Link href={post.url}>{post.title}</Link></h3>
                {post.excerpt ? <p>{post.excerpt}</p> : null}
            </div>
        </article>
    );
}
