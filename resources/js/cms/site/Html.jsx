/**
 * Prints HTML prepared by the server (Markdown already stripped of raw HTML and unsafe links,
 * or icons from the fixed set). Never pass it text typed by visitors.
 */
export default function Html({ as: Tag = 'div', html, ...props }) {
    return <Tag {...props} dangerouslySetInnerHTML={{ __html: html ?? '' }} />;
}

export function Icon({ svg }) {
    return <svg viewBox="0 0 24 24" dangerouslySetInnerHTML={{ __html: svg ?? '' }} />;
}
