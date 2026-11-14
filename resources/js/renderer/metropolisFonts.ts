/** Local open-source font files; usable by templates and the builder font picker. */
export const METROPOLIS_FONTS = [300, 400, 500, 600].map(weight =>
    `@font-face{font-family:Metropolis;src:url('/templates/stratum/metropolis-${weight}.woff2') format('woff2');font-weight:${weight};font-style:normal;font-display:swap}`,
).join('\n');
