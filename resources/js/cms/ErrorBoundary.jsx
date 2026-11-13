import { Component } from 'react';

/**
 * Keeps a broken theme or plugin component from blanking the whole page:
 * renders `fallback` (or nothing) instead, and logs the error.
 */
export default class ErrorBoundary extends Component {
    state = { error: null };

    static getDerivedStateFromError(error) {
        return { error };
    }

    componentDidCatch(error) {
        console.error(`[ModrixCMS] ${this.props.label ?? 'A component'} failed to render.`, error);
    }

    componentDidUpdate(previous) {
        if (previous.resetKey !== this.props.resetKey && this.state.error) {
            this.setState({ error: null });
        }
    }

    render() {
        return this.state.error ? (this.props.fallback ?? null) : this.props.children;
    }
}
