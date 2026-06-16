import { FormEvent, useMemo, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { ProfileUser } from '@/types';
import '../../css/profile.css';

interface ProfileProps {
    profileUser: ProfileUser;
}

const pwdRules = [
    { key: 'len', label: 'At least 8 characters', test: (v: string) => v.length >= 8 },
    { key: 'case', label: 'One uppercase & one lowercase letter', test: (v: string) => /[a-z]/.test(v) && /[A-Z]/.test(v) },
    { key: 'num', label: 'At least one number', test: (v: string) => /\d/.test(v) },
];

function PwToggle({ shown, onClick }: { shown: boolean; onClick: () => void }) {
    return (
        <button type="button" onClick={onClick} className="profile-eye" title="Show/hide" aria-label="Show/hide password">
            <i className={`fa-solid ${shown ? 'fa-eye-slash' : 'fa-eye'}`}></i>
        </button>
    );
}

export default function Profile({ profileUser }: ProfileProps) {
    const [avatarUrl, setAvatarUrl] = useState(profileUser.avatar_url);
    const [hasAvatar, setHasAvatar] = useState(profileUser.has_avatar);
    const [photoModal, setPhotoModal] = useState(false);
    const [pwSuccess, setPwSuccess] = useState(false);
    const [show, setShow] = useState({ current: false, next: false, confirm: false });

    const fileRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<{ url: string; meta: string; file: File } | null>(null);
    const [uploading, setUploading] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const pwdState = useMemo(() => pwdRules.map((r) => ({ ...r, ok: r.test(data.password) })), [data.password]);
    const mismatch = data.password.length > 0 && data.password_confirmation.length > 0 && data.password !== data.password_confirmation;

    const initial = (profileUser.name || '?').charAt(0).toUpperCase();

    const submitPassword = (e: FormEvent) => {
        e.preventDefault();
        setPwSuccess(false);
        post('/change-password', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setPwSuccess(true);
            },
        });
    };

    const pickFile = () => {
        setPhotoModal(false);
        fileRef.current?.click();
    };

    const MAX_AVATAR_BYTES = 5 * 1024 * 1024; // 5 MB

    const onFileSelected = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (file.size > MAX_AVATAR_BYTES) {
            const msg = `Ukuran foto maksimal 5 MB (foto ini ${(file.size / 1024 / 1024).toFixed(1)} MB).`;
            if (window.smToast) window.smToast(msg, 'error');
            else window.alert(msg);
            e.target.value = '';
            return;
        }
        const url = URL.createObjectURL(file);
        const sizeKb = (file.size / 1024).toFixed(0);
        setPreview({ url, meta: `${file.name} · ${sizeKb} KB`, file });
        e.target.value = '';
    };

    const confirmUpload = async () => {
        if (!preview) return;
        setUploading(true);
        try {
            const fd = new FormData();
            fd.append('avatar', preview.file, preview.file.name);
            // Jangan set Content-Type manual — axios otomatis menambahkan boundary multipart yang benar.
            const { data: res } = await window.axios.post('/profile/avatar', fd);
            setAvatarUrl(res.avatar_url);
            setHasAvatar(true);
            setPreview(null);
            router.reload({ only: [] }); // refresh shared auth.user avatar in sidebar
            if (window.smToast) window.smToast(res.message ?? 'Foto profil diperbarui', 'success');
        } catch (err: any) {
            const msg = err?.response?.data?.errors?.avatar?.[0] ?? err?.response?.data?.message ?? 'Gagal mengunggah foto';
            if (window.smToast) window.smToast(msg, 'error');
            else window.alert(msg);
        } finally {
            setUploading(false);
        }
    };

    const deletePhoto = () => {
        if (!window.confirm('Delete profile photo?')) return;
        router.delete('/profile/avatar', {
            preserveScroll: true,
            onSuccess: () => {
                setAvatarUrl(null);
                setHasAvatar(false);
                setPhotoModal(false);
            },
        });
    };

    const logout = () => {
        if (window.confirm('Sign out of your account?')) router.post('/logout');
    };

    return (
        <AppLayout title="My Profile" subtitle="Account & security settings">
            <Head title="My Profile" />

            <input ref={fileRef} type="file" accept="image/jpeg,image/png,image/webp" style={{ display: 'none' }} onChange={onFileSelected} title="Choose profile photo" aria-label="Choose profile photo" />

            <div className="profile-shell">
                <div className="profile-card">
                    {/* Identity */}
                    <div className="profile-id">
                        <div className="profile-av" onClick={() => setPhotoModal(true)} style={{ cursor: 'pointer' }} title="View / change photo">
                            {avatarUrl ? <img src={avatarUrl} alt={profileUser.name} className="profile-av-img" style={{ display: 'block', objectFit: 'cover' }} /> : <div className="profile-av-img">{initial}</div>}
                        </div>
                        <div className="profile-id-text">
                            <div className="profile-name-row">
                                <span className="profile-name">{profileUser.name}</span>
                            </div>
                        </div>
                    </div>

                    <div className="profile-divider"></div>

                    {/* Account info */}
                    <div className="profile-stats">
                        <div className="profile-stat">
                            <p className="profile-stat-label">Joined date</p>
                            <p className="profile-stat-value">{profileUser.joined}</p>
                        </div>
                        <div className="profile-stat">
                            <p className="profile-stat-label">Last login</p>
                            <p className="profile-stat-value">{profileUser.last_login}</p>
                        </div>
                    </div>

                    <div className="profile-divider"></div>

                    {/* Change password */}
                    <p className="profile-sec-label">Change Password</p>
                    {pwSuccess && (
                        <div className="profile-error-box" style={{ borderColor: 'rgba(52,211,153,0.4)', color: '#34d399' }}>
                            <p><i className="fa-solid fa-circle-check text-[10px]"></i> Password changed successfully.</p>
                        </div>
                    )}
                    {Object.keys(errors).length > 0 && (
                        <div className="profile-error-box">
                            {Object.values(errors).map((err, i) => (
                                <p key={i}><i className="fa-solid fa-circle-exclamation text-[10px]"></i> {err}</p>
                            ))}
                        </div>
                    )}
                    <form onSubmit={submitPassword} autoComplete="off">
                        <div className="profile-field">
                            <label className="profile-field-label">Current password</label>
                            <div className="profile-input-wrap">
                                <input className="profile-input" type={show.current ? 'text' : 'password'} placeholder="••••••••••" required autoComplete="current-password" value={data.current_password} onChange={(e) => setData('current_password', e.target.value)} />
                                <PwToggle shown={show.current} onClick={() => setShow((s) => ({ ...s, current: !s.current }))} />
                            </div>
                        </div>
                        <div className="profile-field">
                            <label className="profile-field-label">New password</label>
                            <div className="profile-input-wrap">
                                <input className="profile-input" type={show.next ? 'text' : 'password'} placeholder="Min. 8 characters" required minLength={8} autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                                <PwToggle shown={show.next} onClick={() => setShow((s) => ({ ...s, next: !s.next }))} />
                            </div>
                            <ul className="pwd-checklist">
                                {pwdState.map((r) => (
                                    <li key={r.key} className={r.ok ? 'ok' : ''}>
                                        <i className={`fa-${r.ok ? 'solid fa-circle-check' : 'regular fa-circle'}`}></i>
                                        <span>{r.label}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="profile-field">
                            <label className="profile-field-label">Confirm new password</label>
                            <div className="profile-input-wrap">
                                <input className="profile-input" type={show.confirm ? 'text' : 'password'} placeholder="Retype password" required minLength={8} autoComplete="new-password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
                                <PwToggle shown={show.confirm} onClick={() => setShow((s) => ({ ...s, confirm: !s.confirm }))} />
                            </div>
                        </div>
                        {mismatch && <p className="profile-match-hint" style={{ display: 'block' }}>New passwords do not match</p>}
                        <button type="submit" className="profile-save" disabled={processing || mismatch}>
                            <i className="fa-solid fa-floppy-disk"></i>
                            <span>{processing ? 'Saving…' : 'Save password'}</span>
                        </button>
                    </form>

                    <div className="profile-divider"></div>

                    {/* Sign out */}
                    <div className="profile-signout">
                        <div>
                            <p className="profile-signout-title">Sign out</p>
                            <p className="profile-signout-sub">End the login session.</p>
                        </div>
                        <button type="button" className="profile-logout" onClick={logout}>
                            <i className="fa-solid fa-right-from-bracket"></i>
                            <span>Logout</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Avatar preview modal */}
            {preview && (
                <div style={{ position: 'fixed', inset: 0, zIndex: 10000, background: 'rgba(7,16,31,0.72)', backdropFilter: 'blur(6px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }} onClick={(e) => e.target === e.currentTarget && setPreview(null)}>
                    <div style={{ maxWidth: 360, width: '100%', background: 'var(--panel-1)', border: '1px solid var(--line)', borderRadius: 20, padding: 22 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 14 }}>
                            <span style={{ width: 34, height: 34, borderRadius: 10, background: 'rgba(59,111,212,0.15)', border: '1px solid rgba(59,111,212,0.30)', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', color: '#6ea8ff' }}>
                                <i className="fa-solid fa-camera"></i>
                            </span>
                            <div>
                                <h3 style={{ margin: 0, fontSize: 16, fontWeight: 700, color: 'var(--ink-0)' }}>Confirm Profile Photo</h3>
                                <p style={{ margin: '2px 0 0', fontSize: 12, color: 'var(--ink-3)' }}>Check preview before uploading</p>
                            </div>
                        </div>
                        <div style={{ display: 'flex', justifyContent: 'center', marginBottom: 14 }}>
                            <img src={preview.url} alt="Preview" style={{ width: 160, height: 160, borderRadius: 16, objectFit: 'cover', border: '1px solid var(--line)', background: 'var(--panel-2)' }} />
                        </div>
                        <p style={{ margin: '0 0 14px', fontSize: 12, color: 'var(--ink-3)', textAlign: 'center' }}>{preview.meta}</p>
                        <div style={{ display: 'flex', gap: 8 }}>
                            <button type="button" className="btn btn-ghost" style={{ flex: 1 }} onClick={() => setPreview(null)} disabled={uploading}>Cancel</button>
                            <button type="button" className="btn btn-primary" style={{ flex: 1 }} onClick={confirmUpload} disabled={uploading}>
                                <i className="fa-solid fa-cloud-arrow-up text-[11px]"></i>
                                <span>{uploading ? 'Uploading…' : 'Upload'}</span>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Photo viewer modal */}
            {photoModal && (
                <div style={{ position: 'fixed', inset: 0, zIndex: 10000, background: 'rgba(7,16,31,0.78)', backdropFilter: 'blur(6px)', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 16 }} onClick={(e) => e.target === e.currentTarget && setPhotoModal(false)}>
                    <div style={{ maxWidth: 320, width: '100%', background: 'var(--panel-1)', border: '1px solid var(--line)', borderRadius: 20, padding: 22, textAlign: 'center' }}>
                        <div style={{ display: 'flex', justifyContent: 'center', marginBottom: 14 }}>
                            {avatarUrl ? (
                                <img src={avatarUrl} alt={profileUser.name} style={{ width: 200, height: 200, borderRadius: 20, objectFit: 'cover', border: '1px solid var(--line)' }} />
                            ) : (
                                <div style={{ width: 200, height: 200, borderRadius: 20, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 72, fontWeight: 800, color: '#fff', background: 'var(--panel-3)' }}>{initial}</div>
                            )}
                        </div>
                        <p style={{ margin: '0 0 16px', fontSize: 16, fontWeight: 700, color: 'var(--ink-0)' }}>{profileUser.name}</p>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                            <button type="button" onClick={pickFile} className="btn btn-primary" style={{ width: '100%' }}>
                                <i className="fa-solid fa-camera text-[12px]"></i>
                                <span>{hasAvatar ? 'Change photo' : 'Upload photo'}</span>
                            </button>
                            {hasAvatar && (
                                <button type="button" onClick={deletePhoto} className="btn btn-soft" style={{ width: '100%', color: '#ff5577', borderColor: 'rgba(255,85,119,0.3)' }}>
                                    <i className="fa-solid fa-trash text-[12px]"></i>
                                    <span>Delete photo</span>
                                </button>
                            )}
                            <button type="button" onClick={() => setPhotoModal(false)} className="btn btn-ghost" style={{ width: '100%' }}>Close</button>
                        </div>
                        <p style={{ margin: '12px 0 0', fontSize: 11, color: 'var(--ink-4)' }}>JPG, PNG, WEBP · maks 5 MB</p>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
