/**
 * Montserrat and PT Sans, served from this app rather than a font CDN, so a
 * published page makes no third-party request. Montserrat is one variable file
 * covering 400–700; PT Sans ships a file per weight.
 */
export const SPAZIO_FONTS =
    "@font-face{font-family:'Montserrat';font-style:normal;font-weight:400 700;font-display:swap;src:url(/fonts/templates/a5ec54920b589ebe2af3.woff2) format('woff2')}" +
    "@font-face{font-family:'PT Sans';font-style:normal;font-weight:400;font-display:swap;src:url(/fonts/templates/a922c0987319ee73a964.woff2) format('woff2')}" +
    "@font-face{font-family:'PT Sans';font-style:normal;font-weight:700;font-display:swap;src:url(/fonts/templates/e8c04e18e1097ca2d37d.woff2) format('woff2')}";
