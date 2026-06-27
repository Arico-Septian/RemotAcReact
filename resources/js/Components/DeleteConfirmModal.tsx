import { createPortal } from 'react-dom';

interface DeleteConfirmModalProps {
    open: boolean;
    onCancel: () => void;
    onConfirm: () => void;
    busy?: boolean;
    title?: string;
    message?: string;
    confirmLabel?: string;
    cancelLabel?: string;
}

export default function DeleteConfirmModal({
    open,
    onCancel,
    onConfirm,
    busy = false,
    title = 'Apakah Anda Yakin?',
    message = 'Data yang dihapus tidak dapat dikembalikan!',
    confirmLabel = 'Ya, hapus!',
    cancelLabel = 'Batal',
}: DeleteConfirmModalProps) {
    if (!open) {
        return null;
    }

    return createPortal(
        <div
            role="presentation"
            onClick={(e) => {
                if (e.target === e.currentTarget && !busy) {
                    onCancel();
                }
            }}
            style={{
                position: 'fixed',
                inset: 0,
                zIndex: 120,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: 16,
                background: 'rgba(0,0,0,0.48)',
            }}
        >
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="delete-confirm-title"
                aria-describedby="delete-confirm-message"
                style={{
                    width: 'min(360px, calc(100vw - 32px))',
                    background: '#ffffff',
                    borderRadius: 16,
                    boxShadow: '0 20px 54px rgba(0,0,0,0.24)',
                    padding: '28px 24px 22px',
                    textAlign: 'center',
                    color: '#2f3742',
                }}
            >
                <div
                    aria-hidden="true"
                    style={{
                        width: 58,
                        height: 58,
                        margin: '0 auto 18px',
                        borderRadius: '50%',
                        border: '2px solid #d8a067',
                        color: '#d8a067',
                        display: 'inline-flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: 30,
                        fontWeight: 400,
                        lineHeight: 1,
                    }}
                >
                    !
                </div>
                <h2 id="delete-confirm-title" style={{ margin: '0 0 10px', fontSize: 22, lineHeight: 1.25, fontWeight: 700, color: '#333b46' }}>
                    {title}
                </h2>
                <p id="delete-confirm-message" style={{ margin: '0 0 22px', fontSize: 13, lineHeight: 1.45, color: '#8b95a1' }}>
                    {message}
                </p>
                <div style={{ display: 'flex', justifyContent: 'center', gap: 8 }}>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={busy}
                        style={{
                            minWidth: 86,
                            height: 34,
                            border: 0,
                            borderRadius: 6,
                            background: '#3085d6',
                            color: '#ffffff',
                            fontSize: 12,
                            fontWeight: 700,
                            cursor: busy ? 'not-allowed' : 'pointer',
                            opacity: busy ? 0.72 : 1,
                        }}
                    >
                        {busy ? 'Menghapus...' : confirmLabel}
                    </button>
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={busy}
                        style={{
                            minWidth: 58,
                            height: 34,
                            border: 0,
                            borderRadius: 6,
                            background: '#dd4b39',
                            color: '#ffffff',
                            fontSize: 12,
                            fontWeight: 700,
                            cursor: busy ? 'not-allowed' : 'pointer',
                            opacity: busy ? 0.72 : 1,
                        }}
                    >
                        {cancelLabel}
                    </button>
                </div>
            </div>
        </div>,
        document.body,
    );
}
