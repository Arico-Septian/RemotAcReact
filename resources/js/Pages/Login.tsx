import { FormEvent, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import type { PageProps } from '@/types';
import '../../css/login.css';

const features = [
    { ic: 'c', icon: 'fa-bolt', text: 'Real-time updates via MQTT + WebSocket' },
    { ic: 'm', icon: 'fa-brain', text: 'Fuzzy logic auto-adjusts AC setpoints' },
    { ic: 'l', icon: 'fa-shield-halved', text: 'Role-based access: Admin, Operator, User' },
];

function BrandFeatures() {
    return (
        <>
            {features.map((f) => (
                <div className="feat" key={f.ic}>
                    <span className={`feat-ic ${f.ic}`}><i className={`fa-solid ${f.icon}`}></i></span>
                    <span className="feat-text">{f.text}</span>
                </div>
            ))}
        </>
    );
}

export default function Login() {
    const { flash } = usePage<PageProps>().props;
    const [showPw, setShowPw] = useState(false);
    const [capsOn, setCapsOn] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    const firstError = Object.values(errors)[0] as string | undefined;
    const alertMsg = flash.error || firstError;

    return (
        <>
            <Head title="Sign In" />
            <main className="page">
                {/* Brand (left) */}
                <aside className="login-brand">
                    <div className="brand-inner">
                        <div className="b-logo">
                            <div className="b-mark"><i className="fa-solid fa-snowflake"></i></div>
                            <div>
                                <span className="b-name">SmartAC</span>
                                <span className="b-tag">IoT Control System</span>
                            </div>
                        </div>
                        <div className="b-content">
                            <h1 className="b-headline">
                                <span className="plain">Intelligent</span>
                                <span className="plain">climate control</span>
                                <span className="grad">for server rooms.</span>
                            </h1>
                            <p className="b-desc">
                                Monitor temperatures, control AC units remotely, and automate climate responses — all from one real-time dashboard.
                            </p>
                            <div className="b-status">
                                <span className="s-dot"></span>
                                System online &amp; monitoring
                            </div>
                        </div>
                        <div className="b-feats">
                            <BrandFeatures />
                        </div>
                    </div>
                </aside>

                <div className="divider"></div>

                {/* Form (right) */}
                <section className="form-side">
                    <div className="brand-summary">
                        <div className="bs-logo">
                            <div className="b-mark"><i className="fa-solid fa-snowflake"></i></div>
                            <div>
                                <span className="b-name">SmartAC</span>
                                <span className="b-tag">IoT Control System</span>
                            </div>
                        </div>
                        <h1 className="bs-headline">
                            <span className="plain">Intelligent climate control</span>
                            <span className="grad">for server rooms.</span>
                        </h1>
                        <p className="bs-desc">Monitor temperatures, control AC units remotely, and automate climate responses — all from one real-time dashboard.</p>
                        <div className="b-status">
                            <span className="s-dot"></span>
                            System online &amp; monitoring
                        </div>
                        <div className="bs-feats">
                            <BrandFeatures />
                        </div>
                    </div>

                    <div className="form-body">
                        <div className="form-card">
                            <p className="eyebrow">Sign In</p>
                            <h2 className="form-title">Welcome <span className="hi">back.</span></h2>
                            <p className="form-desc">Sign in with your account to access the SmartAC dashboard.</p>

                            {alertMsg && (
                                <div className="alert" role="alert">
                                    <i className="fa-solid fa-circle-exclamation"></i>
                                    <span>{alertMsg}</span>
                                </div>
                            )}

                            <form onSubmit={submit} noValidate style={{ marginTop: 8 }}>
                                <div className="fields">
                                    <div>
                                        <label className="field-lbl" htmlFor="username">
                                            <span>Username</span>
                                            <span className="hint">3 – 20 characters</span>
                                        </label>
                                        <div className="input-wrap">
                                            <span className="input-ic"><i className="fa-regular fa-user"></i></span>
                                            <input
                                                id="username"
                                                type="text"
                                                name="name"
                                                required
                                                autoFocus
                                                autoComplete="username"
                                                minLength={3}
                                                maxLength={20}
                                                placeholder="Enter your username"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="field-lbl" htmlFor="password">
                                            <span>Password</span>
                                        </label>
                                        <div className="input-wrap">
                                            <span className="input-ic"><i className="fa-solid fa-lock"></i></span>
                                            <input
                                                id="password"
                                                type={showPw ? 'text' : 'password'}
                                                name="password"
                                                required
                                                autoComplete="current-password"
                                                placeholder="Enter your password"
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                onKeyUp={(e) => setCapsOn(e.getModifierState('CapsLock'))}
                                            />
                                            <button type="button" className="input-btn" onClick={() => setShowPw((v) => !v)} aria-label="Toggle password">
                                                <i className={`fa-solid ${showPw ? 'fa-eye-slash' : 'fa-eye'}`}></i>
                                            </button>
                                        </div>
                                        {capsOn && (
                                            <p className="caps-warn" style={{ display: 'flex' }}>
                                                <i className="fa-solid fa-triangle-exclamation"></i> Caps Lock is on
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <button type="submit" className="btn-submit" disabled={processing} style={{ marginTop: 8 }}>
                                    <span>{processing ? 'Signing in…' : 'Sign In'}</span>
                                    <i className="fa-solid fa-arrow-right" style={{ fontSize: 11 }}></i>
                                </button>
                            </form>

                            <p className="form-note">
                                <i className="fa-solid fa-circle-info"></i>
                                Accounts are managed by your system administrator.
                            </p>
                        </div>
                    </div>

                    <div className="form-foot">
                        <span className="secure-pill">
                            <i className="fa-solid fa-lock"></i>
                            Encrypted connection
                        </span>
                        <span className="copy">© {new Date().getFullYear()} SmartAC</span>
                    </div>
                </section>
            </main>
        </>
    );
}
