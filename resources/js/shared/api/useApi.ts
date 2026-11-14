import {
    useMutation,
    useQuery,
    useQueryClient,
    type UseMutationOptions,
    type UseMutationResult,
    type UseQueryOptions,
    type UseQueryResult,
} from '@tanstack/react-query';
import type { AxiosRequestConfig } from 'axios';
import { deleteResource, getResource, patchResource, postResource } from './client';
import { asProblem } from './queryClient';
import type { ProblemResponse } from './types';

/**
 * Query key factory. Keys are arrays so a workspace switch can invalidate a
 * whole subtree with `queryClient.removeQueries({ queryKey: ['workspace'] })`.
 */
export const queryKeys = {
    all: ['api'] as const,
    dashboardStats: (range: string) => ['api', 'dashboard', 'stats', range] as const,
    funnels: (params?: Record<string, unknown>) => ['api', 'funnels', params ?? {}] as const,
    funnel: (id: string) => ['api', 'funnels', id] as const,
    contacts: (params?: Record<string, unknown>) => ['api', 'contacts', params ?? {}] as const,
    workspaces: () => ['api', 'workspaces'] as const,
    notifications: () => ['api', 'notifications'] as const,
} as const;

export type ApiQueryOptions<T> = Omit<UseQueryOptions<T, ProblemResponse, T>, 'queryKey' | 'queryFn'>;

/**
 * `useQuery` bound to the API client: unwraps the `{ data }` envelope and types
 * the error as `ProblemResponse`, so screens can branch on `error.code` —
 * including the 402 upgrade codes — without any casting.
 */
export function useApi<T>(
    queryKey: readonly unknown[],
    url: string,
    config?: AxiosRequestConfig,
    options?: ApiQueryOptions<T>,
): UseQueryResult<T, ProblemResponse> {
    return useQuery<T, ProblemResponse, T>({
        queryKey,
        queryFn: () => getResource<T>(url, config),
        ...options,
    });
}

export type ApiMutationOptions<T, Body> = Omit<
    UseMutationOptions<T, ProblemResponse, Body>,
    'mutationFn'
>;

type MutationMethod = 'post' | 'patch' | 'delete';

/**
 * `useMutation` bound to the API client. Pass `invalidates` to refresh the
 * affected query subtrees once the write succeeds.
 */
export function useApiMutation<T, Body = unknown>(
    method: MutationMethod,
    url: string | ((body: Body) => string),
    options?: ApiMutationOptions<T, Body> & { invalidates?: ReadonlyArray<readonly unknown[]> },
): UseMutationResult<T, ProblemResponse, Body> {
    const queryClient = useQueryClient();
    const { invalidates, onSuccess, ...rest } = options ?? {};

    return useMutation<T, ProblemResponse, Body>({
        mutationFn: async (body: Body) => {
            const resolved = typeof url === 'function' ? url(body) : url;
            if (method === 'post') {
                return postResource<T, Body>(resolved, body);
            }
            if (method === 'patch') {
                return patchResource<T, Body>(resolved, body);
            }
            await deleteResource(resolved);
            return undefined as T;
        },
        onSuccess: async (data, variables, onMutateResult, context) => {
            await Promise.all(
                (invalidates ?? []).map((queryKey) => queryClient.invalidateQueries({ queryKey })),
            );
            await onSuccess?.(data, variables, onMutateResult, context);
        },
        ...rest,
    });
}

/** Field errors for a form, or `{}` when the failure was not a 422. */
export function fieldErrors(error: unknown): Record<string, string> {
    const problem = asProblem(error);
    if (!problem?.errors) {
        return {};
    }
    return Object.fromEntries(
        Object.entries(problem.errors).map(([field, messages]) => [field, messages[0] ?? '']),
    );
}
