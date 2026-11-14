import { QueryClient } from '@tanstack/react-query';
import { isProblemResponse, type ProblemResponse } from './types';

/**
 * Never retry something the server told us not to repeat: auth, permission,
 * validation and plan-limit failures are all terminal for the same request.
 */
const TERMINAL_STATUSES = new Set([400, 401, 402, 403, 404, 409, 422]);

export function shouldRetry(failureCount: number, error: unknown): boolean {
    if (isProblemResponse(error) && TERMINAL_STATUSES.has(error.status)) {
        return false;
    }
    return failureCount < 2;
}

export function createQueryClient(): QueryClient {
    return new QueryClient({
        defaultOptions: {
            queries: {
                // Dashboard numbers are refreshed by Reverb events, not polling.
                staleTime: 30_000,
                gcTime: 5 * 60_000,
                refetchOnWindowFocus: false,
                retry: shouldRetry,
            },
            mutations: {
                retry: false,
            },
        },
    });
}

/** Type-narrowing helper for `useQuery`/`useMutation` error values. */
export function asProblem(error: unknown): ProblemResponse | null {
    return isProblemResponse(error) ? error : null;
}
