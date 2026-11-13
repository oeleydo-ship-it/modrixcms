/*
 * Components the core shares with themes and plugins (window.ModrixCMS.ui).
 * Within 1.x these are only added to, never removed.
 */
export { default as AdminLayout } from './admin/AdminLayout';
export { Badge, Check, confirmVisit, DangerButton, ErrorFlash, Field, FileField, Pager, slugify } from './admin/forms';
export { default as SiteLayout, Brand, Footer, Nav } from './site/Layout';
export { default as BlockList } from './site/BlockList';
export { default as PostCard } from './site/PostCard';
export { default as Html, Icon } from './site/Html';
export { default as ErrorBoundary } from './ErrorBoundary';
export * as coreBlocks from './site/blocks';
