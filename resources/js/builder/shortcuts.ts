export type BuilderShortcut = 'save' | 'undo' | 'redo' | 'duplicate' | 'delete' | 'escape' | 'left-panel' | 'right-panel';

/** Keep browser and field shortcuts intact unless the builder owns the action. */
export function builderShortcut(
    event: Pick<KeyboardEvent, 'key' | 'ctrlKey' | 'metaKey' | 'shiftKey' | 'altKey' | 'defaultPrevented' | 'isComposing'>,
    editing: boolean,
    modalOpen: boolean,
): BuilderShortcut | null {
    if (event.defaultPrevented || event.isComposing || event.altKey || modalOpen) return null;
    const mod = event.metaKey || event.ctrlKey;
    const key = event.key.toLowerCase();
    if (mod && key === 's' && !event.shiftKey) return 'save';
    if (editing) return null;
    if (mod && key === 'z') return event.shiftKey ? 'redo' : 'undo';
    if (mod && key === 'y' && !event.shiftKey) return 'redo';
    if (mod && key === 'd' && !event.shiftKey) return 'duplicate';
    if (mod && key === '[') return 'left-panel';
    if (mod && key === ']') return 'right-panel';
    if (!mod && !event.shiftKey && (key === 'delete' || key === 'backspace')) return 'delete';
    if (!mod && key === 'escape') return 'escape';
    return null;
}
