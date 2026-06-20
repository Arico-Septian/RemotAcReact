import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

type ToastType = 'success' | 'error' | 'info';

interface ToastItem {
    id: number;
    message: string;
    type: ToastType;
    leaving: boolean;
}

type Listener = (message: string, type: ToastType) => void;

let listener: Listener | null = null;
let counter = 0;

const DURATION = 3500;
const EXIT_MS = 220;

const STYLES: Record<ToastType, { icon: string; accent: string; bg: string }> = {
    success: { icon: 'fa-circle-check', accent: '#5a93ec', bg: 'rgba(90,147,236,0.14)' },
    error: { icon: 'fa-circle-exclamation', accent: '#fb7185', bg: 'rgba(251,113,133,0.14)' },
    info: { icon: 'fa-circle-info', accent: '#5a93ec', bg: 'rgba(90,147,236,0.14)' },
};

export default function ToastContainer() {
    const [toasts, setToasts] = useState<ToastItem[]>([]);
    const timers = useRef<Record<number, number>>({});

    useEffect(() => {
        listener = (message, type) => {
            const id = ++counter;
            setToasts((prev) => [...prev, { id, message, type, leaving: false }]);
            timers.current[id] = window.setTimeout(() => {
                setToasts((prev) => prev.map((t) => (t.id === id ? { ...t, leaving: true } : t)));
                window.setTimeout(() => {
                    setToasts((prev) => prev.filter((t) => t.id !== id));
                    delete timers.current[id];
                }, EXIT_MS);
            }, DURATION);
        };
        window.smToast = (message, type = 'info') => listener?.(message, type);

        return () => {
            listener = null;
            Object.values(timers.current).forEach((t) => window.clearTimeout(t));
            timers.current = {};
        };
    }, []);

    if (toasts.length === 0) return null;

    return createPortal(
        <div
            style={{
                position: 'fixed',
                bottom: 16,
                right: 16,
                zIndex: 10050,
                display: 'flex',
                flexDirection: 'column-reverse',
                gap: 10,
                pointerEvents: 'none',
                maxWidth: 'calc(100vw - 32px)',
            }}
        >
            {toasts.map((t) => {
                const s = STYLES[t.type];
                return (
                    <div
                        key={t.id}
                        style={{
                            pointerEvents: 'auto',
                            display: 'flex',
                            alignItems: 'flex-start',
                            gap: 10,
                            minWidth: 240,
                            maxWidth: 340,
                            padding: '12px 14px',
                            borderRadius: 12,
                            background: '#11141c',
                            border: `1px solid ${s.accent}55`,
                            boxShadow: '0 10px 30px rgba(0,0,0,0.35)',
                            transform: t.leaving ? 'translateX(16px)' : 'translateX(0)',
                            opacity: t.leaving ? 0 : 1,
                            transition: `transform ${EXIT_MS}ms ease, opacity ${EXIT_MS}ms ease`,
                        }}
                    >
                        <span
                            style={{
                                width: 26,
                                height: 26,
                                borderRadius: 8,
                                background: s.bg,
                                color: s.accent,
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                flexShrink: 0,
                                fontSize: 13,
                            }}
                        >
                            <i className={`fa-solid ${s.icon}`}></i>
                        </span>
                        <span style={{ fontSize: 13, color: '#fff', lineHeight: 1.4, paddingTop: 2 }}>{t.message}</span>
                    </div>
                );
            })}
        </div>,
        document.body,
    );
}
