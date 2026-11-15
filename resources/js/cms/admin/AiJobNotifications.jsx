import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { dismiss, dismissFinished, useAiJobs } from './aiJobs';

/** Seconds since a job started, ticking while it runs. */
function Elapsed({ since }) {
    const [now, setNow] = useState(Date.now());
    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 1000);
        return () => window.clearInterval(timer);
    }, []);
    const seconds = Math.max(0, Math.round((now - since) / 1000));

    return <span className="ai-note-time">{seconds < 60 ? `${seconds}s` : `${Math.floor(seconds / 60)}m ${seconds % 60}s`}</span>;
}

function Note({ item }) {
    const writer = item.kind === 'writer';

    if (item.state === 'queued') {
        return (
            <div className="ai-note queued">
                <span className="ai-note-icon" aria-hidden="true">○</span>
                <div className="ai-note-body"><strong>Waiting in line</strong><span>“{item.topic}”</span></div>
            </div>
        );
    }

    if (item.state === 'active') {
        return (
            <div className="ai-note active" role="status">
                <span className="ai-note-spinner" aria-hidden="true" />
                <div className="ai-note-body">
                    <strong>The AI agent is generating your post</strong>
                    <span>“{item.topic}” — {writer ? 'it will appear in the editor when it is done.' : 'it will be ready for you to publish shortly.'}</span>
                    {item.startedAt ? <Elapsed since={item.startedAt} /> : null}
                </div>
            </div>
        );
    }

    if (item.state === 'error') {
        return (
            <div className="ai-note error" role="alert">
                <span className="ai-note-icon" aria-hidden="true">!</span>
                <div className="ai-note-body"><strong>Could not write “{item.topic}”</strong><span>{item.message}</span></div>
                <button type="button" className="ai-note-close" aria-label="Dismiss" onClick={() => dismiss(item.id)}>×</button>
            </div>
        );
    }

    return (
        <div className="ai-note done" role="status">
            <span className="ai-note-icon" aria-hidden="true">✓</span>
            <div className="ai-note-body">
                <strong>{writer ? 'Your AI draft is in the editor' : item.status === 'Published' ? 'Your post is live' : 'Your post is ready to publish'}</strong>
                <span>{item.title}{item.words ? ` · ${item.words} words` : ''}</span>
                {!writer && item.editUrl ? (
                    <span className="ai-note-actions">
                        <Link href={item.editUrl} onClick={() => dismiss(item.id)}>{item.status === 'Published' ? 'Open' : 'Review & publish'}</Link>
                        {item.viewUrl && item.status === 'Published' ? <a href={item.viewUrl} target="_blank" rel="noopener">View</a> : null}
                    </span>
                ) : <span className="ai-note-actions">Read it through and press Save.</span>}
            </div>
            <button type="button" className="ai-note-close" aria-label="Dismiss" onClick={() => dismiss(item.id)}>×</button>
        </div>
    );
}

/** Bottom-right notifications for AI posts being written, in this tab, on any admin screen. */
export default function AiJobNotifications() {
    const { jobs, notes } = useAiJobs();
    const items = [...notes, ...jobs];

    if (items.length === 0) {
        return null;
    }

    const total = jobs.length;
    const finished = jobs.filter((job) => job.state === 'done' || job.state === 'error').length;

    return (
        <div className="ai-notes" aria-live="polite">
            {total > 1 ? (
                <div className="ai-notes-head">
                    <span>AI posts · {finished} of {total} done</span>
                    {finished > 0 ? <button type="button" className="link-btn" onClick={dismissFinished}>Clear finished</button> : null}
                </div>
            ) : null}
            <div className="ai-notes-list">
                {items.map((item) => <Note key={item.id} item={item} />)}
            </div>
        </div>
    );
}
