import axios, {
    AxiosError,
    type AxiosInstance,
    type AxiosRequestConfig,
    type InternalAxiosRequestConfig,
} from 'axios';
import type { ApiEnvelope, ProblemCode, ProblemResponse } from './types';

const CSRF_COOKIE_PATH = '/sanctum/csrf-cookie';

function readCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(^|;\\s*)${name}=([^;]*)`));
    return match?.[2] ? decodeURIComponent(match[2]) : null;
}

/**
 * Laravel's stateful SPA flow: hit `/sanctum/csrf-cookie` once, then let axios
 * echo the `XSRF-TOKEN` cookie back in `X-XSRF-TOKEN` on every mutating call.
 * The promise is memoised so a burst of parallel mutations primes it only once.
 */
let csrfPrimer: Promise<void> | null = null;

export function ensureCsrfCookie(instance: AxiosInstance): Promise<void> {
    if (readCookie('XSRF-TOKEN')) {
        return Promise.resolve();
    }
    csrfPrimer ??= instance
        .get(CSRF_COOKIE_PATH, { baseURL: '/' })
        .then(() => undefined)
        .catch(() => undefined)
        .finally(() => {
            csrfPrimer = null;
        });
    return csrfPrimer;
}

const MUTATING = new Set(['post', 'put', 'patch', 'delete']);

function statusToCode(status: number): ProblemCode {
    switch (status) {
        case 401:
            return 'unauthenticated';
        case 403:
            return 'forbidden';
        case 404:
            return 'not_found';
        case 409:
            return 'conflict';
        case 422:
            return 'validation_failed';
        case 429:
            return 'rate_limited';
        default:
            return status >= 500 ? 'server_error' : 'server_error';
    }
}

const DEFAULT_MESSAGES: Record<ProblemCode, string> = {
    quota_exceeded: 'You have reached the limit for your current plan.',
    feature_unavailable: 'That feature is not included in your current plan.',
    validation_failed: 'Please check the highlighted fields and try again.',
    unauthenticated: 'Your session has expired. Please sign in again.',
    forbidden: 'You do not have permission to do that.',
    not_found: 'We could not find what you were looking for.',
    conflict: 'Someone else changed this while you were working on it.',
    rate_limited: 'Too many requests. Please slow down and try again shortly.',
    server_error: 'Something went wrong on our side. Please try again.',
    network_error: 'We could not reach the server. Check your connection.',
};

interface RawProblemBody {
    message?: unknown;
    errors?: unknown;
    code?: unknown;
    quota?: unknown;
    feature?: unknown;
}

/** Collapse any axios failure into the single `ProblemResponse` shape. */
export function toProblem(error: unknown): ProblemResponse {
    if (!axios.isAxiosError(error)) {
        return {
            status: 0,
            code: 'server_error',
            message: error instanceof Error ? error.message : DEFAULT_MESSAGES.server_error,
        };
    }

    const axiosError = error as AxiosError<RawProblemBody>;
    const response = axiosError.response;

    if (!response) {
        return {
            status: 0,
            code: 'network_error',
            message: DEFAULT_MESSAGES.network_error,
        };
    }

    const body = response.data ?? {};
    const bodyCode = typeof body.code === 'string' ? (body.code as ProblemCode) : null;
    const code: ProblemCode = bodyCode ?? statusToCode(response.status);
    const requestId = response.headers?.['x-request-id'];

    const problem: ProblemResponse = {
        status: response.status,
        code,
        message: typeof body.message === 'string' && body.message ? body.message : DEFAULT_MESSAGES[code],
    };

    if (body.errors && typeof body.errors === 'object') {
        problem.errors = body.errors as Record<string, string[]>;
    }
    if (body.quota && typeof body.quota === 'object') {
        problem.quota = body.quota as ProblemResponse['quota'];
    }
    if (body.feature && typeof body.feature === 'object') {
        problem.feature = body.feature as ProblemResponse['feature'];
    }
    if (typeof requestId === 'string') {
        problem.requestId = requestId;
    }

    return problem;
}

export interface ApiClientOptions {
    baseURL?: string;
    /** Called on every 401 so the shell can bounce to the login screen. */
    onUnauthenticated?: () => void;
    /** Called on every 402 so a single upgrade modal can own the response. */
    onUpgradeRequired?: (problem: ProblemResponse) => void;
}

export function createApiClient(options: ApiClientOptions = {}): AxiosInstance {
    const instance = axios.create({
        baseURL: options.baseURL ?? '/api/v1',
        withCredentials: true,
        withXSRFToken: true,
        xsrfCookieName: 'XSRF-TOKEN',
        xsrfHeaderName: 'X-XSRF-TOKEN',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    instance.interceptors.request.use(async (config: InternalAxiosRequestConfig) => {
        if (MUTATING.has((config.method ?? 'get').toLowerCase())) {
            await ensureCsrfCookie(instance);
        }
        return config;
    });

    instance.interceptors.response.use(
        (response) => response,
        (error: unknown) => {
            const problem = toProblem(error);

            if (problem.status === 401) {
                options.onUnauthenticated?.();
            }
            if (problem.status === 402) {
                options.onUpgradeRequired?.(problem);
            }

            return Promise.reject(problem);
        },
    );

    return instance;
}

/** The instance both SPAs use. Configure callbacks via `configureApi()`. */
export const api = createApiClient();

let unauthenticatedHandler: (() => void) | null = null;
let upgradeHandler: ((problem: ProblemResponse) => void) | null = null;

api.interceptors.response.use(undefined, (problem: ProblemResponse) => {
    if (problem.status === 401) {
        unauthenticatedHandler?.();
    }
    if (problem.status === 402) {
        upgradeHandler?.(problem);
    }
    return Promise.reject(problem);
});

/** Late-bind the app-level handlers once the React providers exist. */
export function configureApi(handlers: {
    onUnauthenticated?: () => void;
    onUpgradeRequired?: (problem: ProblemResponse) => void;
}): void {
    unauthenticatedHandler = handlers.onUnauthenticated ?? null;
    upgradeHandler = handlers.onUpgradeRequired ?? null;
}

/** Unwraps `{ data: T }` so callers work with `T` directly. */
export async function getResource<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.get<ApiEnvelope<T>>(url, config);
    return response.data.data;
}

export async function getRaw<T>(url: string, config?: AxiosRequestConfig): Promise<T> {
    const response = await api.get<T>(url, config);
    return response.data;
}

export async function postResource<T, Body = unknown>(
    url: string,
    body?: Body,
    config?: AxiosRequestConfig,
): Promise<T> {
    const response = await api.post<ApiEnvelope<T>>(url, body, config);
    return response.data.data;
}

export async function patchResource<T, Body = unknown>(
    url: string,
    body?: Body,
    config?: AxiosRequestConfig,
): Promise<T> {
    const response = await api.patch<ApiEnvelope<T>>(url, body, config);
    return response.data.data;
}

/** PUT, for endpoints that replace a whole resource rather than patch one. */
export async function putResource<T, Body = unknown>(
    url: string,
    body?: Body,
    config?: AxiosRequestConfig,
): Promise<T> {
    const response = await api.put<ApiEnvelope<T>>(url, body, config);
    return response.data.data;
}

export async function deleteResource(url: string, config?: AxiosRequestConfig): Promise<void> {
    await api.delete(url, config);
}
