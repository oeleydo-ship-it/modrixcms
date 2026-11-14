import type { PageDocument } from '@renderer';

/** Move an existing section to a seam index without replacing any node IDs. */
export function reorderSection(document: PageDocument, id: string, before: number): PageDocument {
    const from = document.sections.findIndex(section => section.id === id);
    if (from < 0 || !Number.isFinite(before)) return document;
    const seam = Math.max(0, Math.min(document.sections.length, Math.trunc(before)));
    const to = seam > from ? seam - 1 : seam;
    if (to === from) return document;
    const sections = [...document.sections];
    const [section] = sections.splice(from, 1);
    if (!section) return document;
    sections.splice(to, 0, section);
    return { ...document, sections };
}
