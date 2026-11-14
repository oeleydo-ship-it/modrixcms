import { useEffect, useRef, useState, type InputHTMLAttributes } from 'react';

type BufferedStyleInputProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'defaultValue' | 'onChange'> & {
    value: string | number;
    onValueChange: (value: string) => void;
};

/** Keep drag events out of the document store, history and autosave pipeline. */
export function BufferedStyleInput({ value, onValueChange, onBlur, onPointerUp, onKeyUp, ...props }: BufferedStyleInputProps) {
    const [draft, setDraft] = useState(String(value));
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const pending = useRef<(() => void) | null>(null);

    const flush = () => {
        if (timer.current !== null) clearTimeout(timer.current);
        timer.current = null;
        const commit = pending.current;
        pending.current = null;
        commit?.();
    };

    useEffect(() => {
        // An external reset/undo takes precedence over a queued drag value.
        if (timer.current !== null) clearTimeout(timer.current);
        timer.current = null;
        pending.current = null;
        setDraft(String(value));
    }, [value]);

    useEffect(() => () => {
        // Preserve the last chosen value if the properties panel closes.
        if (timer.current !== null) clearTimeout(timer.current);
        const commit = pending.current;
        pending.current = null;
        commit?.();
    }, []);

    return <input
        {...props}
        value={draft}
        onChange={(event) => {
            const next = event.currentTarget.value;
            setDraft(next);
            if (timer.current !== null) clearTimeout(timer.current);
            // Capture the original node's callback, not the next selected node.
            pending.current = () => onValueChange(next);
            timer.current = setTimeout(flush, 160);
        }}
        onPointerUp={(event) => { flush(); onPointerUp?.(event); }}
        onKeyUp={(event) => { flush(); onKeyUp?.(event); }}
        onBlur={(event) => { flush(); onBlur?.(event); }}
    />;
}
