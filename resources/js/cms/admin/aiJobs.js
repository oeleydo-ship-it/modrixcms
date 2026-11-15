import { useSyncExternalStore } from 'react';
import { router } from '@inertiajs/react';

/**
 * The AI post queue and its notifications, kept outside any page so a run carries on while the
 * admin moves between screens (Inertia keeps this module alive). Each post is one request, so a
 * long list never hits a server time limit.
 */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export async function postJson(routeName, body) {
    const response = await fetch(route(routeName), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = json?.errors ? Object.values(json.errors).flat()[0] : null;
        throw new Error(firstError || json?.message || 'The AI request failed.');
    }

    return json.data;
}

let state = { jobs: [], notes: [] };
let running = false;
let nextId = 1;
const listeners = new Set();

const emit = (next) => {
    state = next;
    listeners.forEach((listener) => listener());
};
const subscribe = (listener) => {
    listeners.add(listener);
    return () => listeners.delete(listener);
};

const patchJob = (id, patch) => emit({ ...state, jobs: state.jobs.map((job) => (job.id === id ? { ...job, ...patch } : job)) });

export const isBusy = () => state.jobs.some((job) => job.state === 'queued' || job.state === 'active');

if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', (event) => {
        if (isBusy()) {
            event.preventDefault();
            event.returnValue = 'The AI is still writing your posts. Leaving now stops it.';
        }
    });
}

/** A one-off notification (the editor's AI writer uses it). Returns its id. */
export function notify(note) {
    const id = nextId++;
    emit({ ...state, notes: [...state.notes, { id, ...note }] });
    return id;
}

export function updateNote(id, patch) {
    emit({ ...state, notes: state.notes.map((note) => (note.id === id ? { ...note, ...patch } : note)) });
}

export function dismiss(id) {
    emit({ jobs: state.jobs.filter((job) => job.id !== id), notes: state.notes.filter((note) => note.id !== id) });
}

export function dismissFinished() {
    emit({ ...state, jobs: state.jobs.filter((job) => job.state === 'queued' || job.state === 'active') });
}

/**
 * Queues one post per topic and starts writing them.
 *
 * @param {string[]} topics
 * @param {(topic: string, index: number) => object} payloadFor  the request body for each topic
 */
export function enqueuePosts(topics, payloadFor) {
    const jobs = topics.map((topic, index) => ({ id: nextId++, topic, payload: payloadFor(topic, index), state: 'queued' }));
    emit({ ...state, jobs: [...state.jobs, ...jobs] });
    void work();
}

async function work() {
    if (running) {
        return;
    }
    running = true;

    let job;
    while ((job = state.jobs.find((item) => item.state === 'queued'))) {
        patchJob(job.id, { state: 'active', startedAt: Date.now() });
        try {
            const saved = await postJson('admin.posts.ai.auto', job.payload);
            patchJob(job.id, { state: 'done', title: saved.title, status: saved.status, editUrl: saved.edit_url, words: saved.words, viewUrl: saved.view_url });
            // Show the new post in the list straight away when the list is open.
            if (window.location.pathname.replace(/\/$/, '').endsWith('/admin/posts')) {
                router.reload({ only: ['posts'] });
            }
        } catch (err) {
            patchJob(job.id, { state: 'error', message: err.message });
        }
    }

    running = false;
}

export function useAiJobs() {
    return useSyncExternalStore(subscribe, () => state, () => state);
}
