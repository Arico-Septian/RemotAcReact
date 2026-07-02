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
    success: { icon: 'fa-circle-check', accent: '#22c55e', bg: 'rgba(34,197,94,0.14)' },
    error: { icon: 'fa-circle-exclamation', accent: '#fb7185', bg: 'rgba(251,113,133,0.14)' },
    info: { icon: 'fa-circle-info', accent: '#5a93ec', bg: 'rgba(90,147,236,0.14)' },
};

const PROGRESS_COLOR = '#9ca3af';

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
                top: 80,
                right: 16,
                zIndex: 10050,
                display: 'flex',
                flexDirection: 'column',
                gap: 10,
                pointerEvents: 'none',
                maxWidth: 'calc(100vw - 32px)',
            }}
        >
            <style>
                {`
                    @keyframes sm-toast-loading {
                        from { transform: scaleX(1); }
                        to { transform: scaleX(0); }
                    }
                `}
            </style>
            {toasts.map((t) => {
                const s = STYLES[t.type];
                return (
                    <div
                        key={t.id}
                        style={{
                            position: 'relative',
                            pointerEvents: 'auto',
                            display: 'flex',
                            alignItems: 'flex-start',
                            gap: 10,
                            width: 'fit-content',
                            minWidth: 0,
                            maxWidth: 320,
                            padding: '15px 18px',
                            borderRadius: 12,
                            background: '#fff',
                            border: `1px solid ${s.accent}55`,
                            boxShadow: '0 10px 26px rgba(20, 33, 61, 0.16)',
                            transform: t.leaving ? 'translateX(16px)' : 'translateX(0)',
                            opacity: t.leaving ? 0 : 1,
                            transition: `transform ${EXIT_MS}ms ease, opacity ${EXIT_MS}ms ease`,
                            overflow: 'hidden',
                        }}
                    >
                        <span
                            style={{
                                width: 30,
                                height: 30,
                                borderRadius: 8,
                                background: s.bg,
                                color: s.accent,
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                flexShrink: 0,
                                fontSize: 14,
                            }}
                        >
                            <i className={`fa-solid ${s.icon}`}></i>
                        </span>
                        <span style={{ fontSize: 14, color: '#2f3642', lineHeight: 1.4, paddingTop: 3, fontWeight: 600 }}>{t.message}</span>
                        <span
                            aria-hidden="true"
                            style={{
                                position: 'absolute',
                                left: 0,
                                right: 0,
                                bottom: 0,
                                height: 4,
                                background: PROGRESS_COLOR,
                                transformOrigin: 'left center',
                                animation: `sm-toast-loading ${DURATION}ms linear forwards`,
                                opacity: t.leaving ? 0 : 0.95,
                                transition: `opacity ${EXIT_MS}ms ease`,
                            }}
                        />
                    </div>
                );
            })}
        </div>,
        document.body,
    );
}
