const RESERVED_SEGMENTS = ['admin', 'central', 'up'];

export function detectTenantSlug() {
    const segment = window.location.pathname.split('/').filter(Boolean)[0];

    return segment && !RESERVED_SEGMENTS.includes(segment) ? segment : null;
}

export function tenantBasePath() {
    const slug = detectTenantSlug();

    return slug ? `/${slug}` : '';
}
