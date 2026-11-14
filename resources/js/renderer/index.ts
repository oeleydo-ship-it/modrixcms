/**
 * `@renderer` — the shared page renderer package.
 *
 * Imported by the builder canvas and by the public page shell. It must never
 * import from `@builder` or `@dashboard`; the dependency arrow points one way.
 */

export { PageRenderer, documentCss, type PageRendererProps, type RendererSlots } from './PageRenderer';
export { blockRegistry, componentFor, INLINE_EDITABLE } from './registry';
export { formatMoney, normaliseCommerce, type CommerceProduct, type CommerceRuntime } from './commerce';
export {
    FunnelProvider,
    normaliseForms,
    normaliseFunnelRuntime,
    resolveButtonAction,
    useFunnel,
    type FunnelRuntime,
    type PublicFormDefinition,
    type PublicFormField,
    type ResolvedAction,
} from './funnel';
export { FONT_FAMILIES, FONT_FAMILY_GROUPS, fontOptionFor, googleFontsImport, type FontFamilyOption } from './fonts';
export { ICON_NAMES, containerColumnSpans } from './blocks/content';
export type { BlockComponent, BlockComponentProps } from './blocks/shared';
export { Placeholder } from './blocks/shared';
export {
    CURRENT_SCHEMA_VERSION,
    blankDocument,
    createBlock,
    createColumn,
    createRow,
    createSection,
    defaultProps,
    newId,
    normaliseBlock,
    normaliseColumn,
    normaliseDocument,
    normaliseRow,
    normaliseSection,
    normaliseSettings,
    serialiseDocument,
} from './document';
export {
    columnExtra,
    isEmptyResponsive,
    isOverriddenAt,
    kebab,
    nodeCss,
    nodeSelector,
    resolveStyle,
    spanAt,
    styleValueToCss,
    toResponsive,
    valueAt,
    withBreakpoint,
} from './style';
export { RESPONSIVE_PROP_VARS, columnsFromGrid, gridForColumns, propVar, responsiveProp } from './responsiveProps';
export * from './types';
