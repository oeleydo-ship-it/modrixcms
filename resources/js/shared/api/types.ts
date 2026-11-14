/**
 * The wire contract between the Laravel API and both SPAs.
 *
 * Anything the backend returns should be describable by one of these shapes —
 * if a controller needs something else, extend this file first so the type and
 * the Resource stay in step.
 */

/** `ApiResource` / `ApiResource::collection` wrap payloads in `data`. */
export interface ApiEnvelope<T> {
    data: T;
}

export interface PaginationLinks {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
}

export interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
}

/** Laravel's `ResourceCollection` shape. */
export interface Paginated<T> {
    data: T[];
    links: PaginationLinks;
    meta: PaginationMeta;
}

/** Laravel 422 validation payload. */
export interface ValidationProblem {
    message: string;
    errors: Record<string, string[]>;
}

/**
 * Machine-readable codes the API attaches to a problem response. `quota_exceeded`
 * and `feature_unavailable` arrive with HTTP 402 from `EnforceQuota` and
 * `EnsureWorkspaceFeature` and must render an upgrade prompt rather than a
 * generic failure.
 */
export type ProblemCode =
    | 'quota_exceeded'
    | 'feature_unavailable'
    | 'validation_failed'
    | 'unauthenticated'
    | 'forbidden'
    | 'not_found'
    | 'conflict'
    | 'rate_limited'
    | 'server_error'
    | 'network_error';

/** Extra context the 402 responses carry so the UI can name the limit. */
export interface QuotaContext {
    /** e.g. "funnels", "contacts", "emails_per_month". */
    resource: string;
    limit: number | null;
    used: number;
    plan: string;
    /** Where to send the user to fix it. */
    upgrade_url?: string;
}

export interface FeatureContext {
    feature: string;
    plan: string;
    required_plan?: string;
    upgrade_url?: string;
}

/**
 * Normalised error envelope. Every failure that reaches component code is one
 * of these, whatever actually went wrong at the transport layer.
 */
export interface ProblemResponse {
    /** HTTP status, or 0 when the request never reached the server. */
    status: number;
    code: ProblemCode;
    /** Safe to display. */
    message: string;
    /** Field-keyed messages for 422s. */
    errors?: Record<string, string[]>;
    quota?: QuotaContext;
    feature?: FeatureContext;
    /** Correlation id from the `X-Request-Id` response header, for support. */
    requestId?: string;
}

export function isProblemResponse(value: unknown): value is ProblemResponse {
    return (
        typeof value === 'object' &&
        value !== null &&
        'code' in value &&
        'status' in value &&
        'message' in value
    );
}

/** True when the API refused because of plan limits, not because of a bug. */
export function isUpgradeProblem(
    problem: ProblemResponse,
): problem is ProblemResponse & { status: 402 } {
    return problem.code === 'quota_exceeded' || problem.code === 'feature_unavailable';
}
