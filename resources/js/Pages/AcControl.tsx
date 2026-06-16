import { FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { AcControlUnit, PageProps } from '@/types';
import '../../css/ac-control.css';

interface AcControlProps {
    room: { id: number; name: string; device_status: 'online' | 'offline' };
    acs: AcControlUnit[];
}

const MODES: [string, string, string][] = [
    ['cool', 'fa-snowflake', 'Cool'],
    ['heat', 'fa-fire', 'Heat'],
    ['dry', 'fa-droplet', 'Dry'],
    ['fan', 'fa-fan', 'Fan'],
];
const FANS: [string, string, string][] = [
    ['auto', 'fa-rotate', 'Auto'],
    ['low', 'fa-equals', 'Low'],
    ['medium', 'fa-bars', 'Med'],
    ['high', 'fa-gauge-high', 'High'],
];
const SWINGS: [string, string, string][] = [
    ['off', 'fa-ban', 'Still'],
    ['full', 'fa-arrows-up-down', 'Full'],
    ['half', 'fa-equals', '½'],
    ['down', 'fa-arrow-down', 'Down'],
];

const swingLabel = (s: string) => ({ off: 'Still', full: 'Full', half: '½', down: 'Down' }[s] ?? s);
const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
const tempCategory = (t: number) => (t <= 20 ? 'cool' : t <= 25 ? 'warm' : 'hot');
const fillDeg = (t: number) => Math.round(((Math.max(16, Math.min(30, t)) - 16) / 14) * 360);

export default function AcControl({ room, acs: initialAcs }: AcControlProps) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user?.role === 'admin' || auth.user?.role === 'operator';

    const [acs, setAcs] = useState<AcControlUnit[]>(initialAcs);
    const [selectedId, setSelectedId] = useState<number | null>(initialAcs[0]?.id ?? null);
    const [dropdownOpen, setDropdownOpen] = useState(false);
    const [espOnline, setEspOnline] = useState(room.device_status === 'online');
    const [addModal, setAddModal] = useState(false);
    const [powerConfirm, setPowerConfirm] = useState<AcControlUnit | null>(null);
    const [timerEditId, setTimerEditId] = useState<number | null>(null);
    const dropdownRef = useRef<HTMLDivElement>(null);

    const addForm = useForm({ ac_number: '', name: '', brand: '' });

    const acIds = useMemo(() => new Set(initialAcs.map((a) => a.id)), [initialAcs]);

    // Keep selected valid if list changes
    useEffect(() => {
        setAcs(initialAcs);
        if (!initialAcs.some((a) => a.id === selectedId)) {
            setSelectedId(initialAcs[0]?.id ?? null);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initialAcs]);

    // Live reconciliation: AC status + ESP device status
    useEffect(() => {
        const refresh = async () => {
            try {
                const [acRes, devRes] = await Promise.all([
                    window.axios.get('/api/ac-status'),
                    window.axios.get('/device-status'),
                ]);
                const byId = new Map<number, Partial<AcControlUnit>>();
                (acRes.data ?? []).forEach((item: any) => {
                    const unit = item.ac_unit || item.acUnit;
                    const id = unit?.id ?? item.ac_unit_id;
                    if (!id || !acIds.has(id)) return;
                    byId.set(id, {
                        power: (item.power || 'OFF').toUpperCase() === 'ON' ? 'ON' : 'OFF',
                        set_temperature: Number(item.set_temperature ?? 24),
                        mode: (item.mode || 'cool').toLowerCase(),
                        fan_speed: (item.fan_speed || 'auto').toLowerCase(),
                        swing: (item.swing || 'off').toLowerCase(),
                    });
                });
                if (byId.size) setAcs((prev) => prev.map((a) => (byId.has(a.id) ? { ...a, ...byId.get(a.id) } : a)));

                const dev = (devRes.data ?? []).find((d: any) => Number(d.room_id) === room.id);
                if (dev) setEspOnline(dev.is_online === true || dev.status === 'online');
            } catch {
                /* keep last good state */
            }
        };
        const id = window.setInterval(refresh, 5000);
        const echo = window.Echo;
        if (echo) {
            echo.channel('device-status')
                .listen('.AcStatusUpdated', refresh)
                .listen('.DeviceStatusUpdated', refresh)
                // Timer diubah user lain -> muat ulang daftar AC (timer diformat benar oleh server).
                .listen('.AcTimerUpdated', (e: any) => {
                    if (Number(e.room_id) === room.id) router.reload({ only: ['acs'] });
                });
        }
        return () => {
            window.clearInterval(id);
            try {
                echo?.leaveChannel('device-status');
            } catch {
                /* noop */
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [room.id]);

    // Real-time sync: user lain menambah/menghapus AC unit di ruangan ini -> reload daftar AC.
    useEffect(() => {
        const echo = window.Echo;
        if (!echo) return;
        echo.channel('ac-units').listen('.AcUnitsChanged', (e: any) => {
            if (Number(e.room_id) === room.id) {
                router.reload({ only: ['acs'] });
            }
        });
        return () => {
            try {
                echo.leaveChannel('ac-units');
            } catch {
                /* noop */
            }
        };
    }, [room.id]);

    useEffect(() => {
        const onClick = (e: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(e.target as Node)) setDropdownOpen(false);
        };
        document.addEventListener('click', onClick);
        return () => document.removeEventListener('click', onClick);
    }, []);

    const patch = (id: number, p: Partial<AcControlUnit>) => setAcs((prev) => prev.map((a) => (a.id === id ? { ...a, ...p } : a)));

    // Kunci tombol per-AC-per-kontrol selama request berjalan (loading + anti-spam).
    const [pending, setPending] = useState<Record<string, boolean>>({});
    const isBusy = (id: number, field: string) => !!pending[`${id}:${field}`];

    // Fire a control POST; endpoints return back() so we ignore the (redirected) body.
    const fire = (url: string, body?: Record<string, string>, revert?: () => void, lockKey?: string) => {
        const data = body ? new URLSearchParams(body).toString() : undefined;
        if (lockKey) setPending((p) => ({ ...p, [lockKey]: true }));
        window.axios
            .post(url, data, { headers: body ? { 'Content-Type': 'application/x-www-form-urlencoded' } : undefined })
            .catch((err: any) => {
                if (revert) revert();
                const msg = err?.response?.status ? `Failed (HTTP ${err.response.status})` : 'Command failed';
                if (window.smToast) window.smToast(msg, 'error');
            })
            .finally(() => {
                if (lockKey) {
                    setPending((p) => {
                        const n = { ...p };
                        delete n[lockKey];
                        return n;
                    });
                }
            });
    };

    const setTemp = (ac: AcControlUnit, value: number) => {
        if (isBusy(ac.id, 'temp')) return;
        const v = Math.max(16, Math.min(30, value));
        if (v === ac.set_temperature) return;
        const prev = ac.set_temperature;
        patch(ac.id, { set_temperature: v });
        fire(`/ac/${ac.id}/temp/${v}`, undefined, () => patch(ac.id, { set_temperature: prev }), `${ac.id}:temp`);
    };

    const setField = (ac: AcControlUnit, field: 'mode' | 'fan_speed' | 'swing', value: string, urlSeg: string) => {
        if (isBusy(ac.id, field)) return;
        const prev = ac[field];
        patch(ac.id, { [field]: value } as Partial<AcControlUnit>);
        fire(`/ac/${ac.id}/${urlSeg}/${value}`, undefined, () => patch(ac.id, { [field]: prev } as Partial<AcControlUnit>), `${ac.id}:${field}`);
    };

    const confirmPower = () => {
        const ac = powerConfirm;
        if (!ac) return;
        setPowerConfirm(null);
        if (isBusy(ac.id, 'power')) return;
        const newPower = ac.power === 'ON' ? 'OFF' : 'ON';
        patch(ac.id, { power: newPower });
        fire(`/ac/${ac.id}/toggle`, { power: newPower }, () => patch(ac.id, { power: ac.power }), `${ac.id}:power`);
    };

    const saveTimer = (ac: AcControlUnit, on: string, off: string) => {
        patch(ac.id, { timer_on: on || null, timer_off: off || null });
        setTimerEditId(null);
        fire(`/ac/${ac.id}/schedule`, { timer_on: on, timer_off: off });
    };

    const deleteTimer = (ac: AcControlUnit) => {
        patch(ac.id, { timer_on: null, timer_off: null });
        fire(`/ac/${ac.id}/schedule`, { timer_on: '', timer_off: '' });
    };

    const deleteAc = (ac: AcControlUnit) => {
        if (!window.confirm(`Delete AC ${ac.ac_number} · ${ac.name}?`)) return;
        router.delete(`/ac/${ac.id}`, { preserveScroll: true });
    };

    const submitAdd = (e: FormEvent) => {
        e.preventDefault();
        addForm.post(`/rooms/${room.id}/ac`, {
            preserveScroll: true,
            onSuccess: () => {
                setAddModal(false);
                addForm.reset();
            },
        });
    };

    const selected = acs.find((a) => a.id === selectedId) ?? null;
    const acLabel = (ac: AcControlUnit) => `AC ${ac.ac_number} · ${ac.name}${ac.brand ? ` · ${ac.brand}` : ''}`;

    return (
        <AppLayout
            title={cap(room.name)}
            subtitle="AC control panel"
            hideHeaderUser
            headerActions={
                <span className={`pill ${espOnline ? 'pill-online' : 'pill-error'}`} style={{ justifyContent: 'center', background: 'transparent', border: 'none', boxShadow: 'none', padding: 0, fontSize: 12 }}>
                    <span style={{ fontSize: 15, fontWeight: 800, letterSpacing: '0.02em', marginRight: 5 }}>ESP</span>
                    <span style={{ fontSize: 12, fontWeight: 600 }}>{espOnline ? 'Online' : 'Offline'}</span>
                </span>
            }
        >
            <Head title={`${cap(room.name)} — AC Control`} />

            {/* Selector + actions */}
            <div className="selector-bar">
                <div className="flex items-center gap-3 min-w-0 flex-1">
                    <div className="selector" ref={dropdownRef} onClick={() => setDropdownOpen((v) => !v)}>
                        <i className="fa-solid fa-snowflake" style={{ color: '#5a93ec', fontSize: 11 }}></i>
                        <span style={{ textTransform: 'capitalize' }}>{selected ? acLabel(selected) : 'No AC'}</span>
                        <i className="fa-solid fa-chevron-down"></i>
                        {dropdownOpen && acs.length > 0 && (
                            <div id="dropdownAC" className="show" style={{ width: '100%', minWidth: 0 }}>
                                {acs.map((ac) => (
                                    <div key={ac.id} onClick={(e) => { e.stopPropagation(); setSelectedId(ac.id); setDropdownOpen(false); }} style={{ textTransform: 'capitalize', justifyContent: 'center' }}>
                                        {acLabel(ac)}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                    <span className="kbd hidden sm:inline">{room.name}</span>
                </div>
                {canManage && (
                    <div className="flex items-center gap-2">
                        {selected && (
                            <button type="button" onClick={() => deleteAc(selected)} className="btn-icon danger" title="Delete AC">
                                <i className="fa-solid fa-trash text-[10px]"></i>
                            </button>
                        )}
                        <button
                            type="button"
                            disabled={acs.length >= 15}
                            onClick={() => setAddModal(true)}
                            className={`btn btn-primary btn-sm btn-add ${acs.length >= 15 ? 'disabled' : ''}`}
                        >
                            <i className="fa-solid fa-plus text-[10px]"></i>
                            <span>Add AC</span>
                        </button>
                    </div>
                )}
            </div>

            {/* Panels */}
            {acs.length === 0 ? (
                <div className="panel">
                    <div className="empty-state">
                        <div className="empty-icon"><i className="fa-solid fa-snowflake"></i></div>
                        <p className="empty-title">No AC units</p>
                        <p className="empty-sub">Add the first AC unit to start controlling</p>
                    </div>
                </div>
            ) : (
                selected && <AcPanel
                    key={selected.id}
                    ac={selected}
                    canEditTimer={timerEditId === selected.id}
                    onSetTemp={setTemp}
                    onTogglePower={() => setPowerConfirm(selected)}
                    onSetField={setField}
                    isBusy={(field: string) => isBusy(selected.id, field)}
                    onOpenTimer={() => setTimerEditId((id) => (id === selected.id ? null : selected.id))}
                    onSaveTimer={saveTimer}
                    onDeleteTimer={deleteTimer}
                />
            )}

            {/* Power confirm modal */}
            {powerConfirm && createPortal(
                <div className="modal-backdrop is-open" onClick={(e) => e.target === e.currentTarget && setPowerConfirm(null)}>
                    <div className="modal" style={{ maxWidth: 380 }}>
                        <div className="modal-body text-center" style={{ paddingTop: 22 }}>
                            <div className="confirm-icon info"><i className="fa-solid fa-power-off"></i></div>
                            <h2 style={{ fontSize: 16, fontWeight: 600, color: 'var(--ink-0)', margin: '0 0 4px' }}>Confirm Power</h2>
                            <p className="text-sm" style={{ color: 'var(--ink-2)', margin: 0 }}>
                                Turn AC {powerConfirm.ac_number}{powerConfirm.name ? ` · ${powerConfirm.name}` : ''} {powerConfirm.power === 'ON' ? 'OFF' : 'ON'}?
                            </p>
                        </div>
                        <div className="modal-footer" style={{ paddingTop: 6 }}>
                            <button type="button" onClick={() => setPowerConfirm(null)} className="btn btn-ghost flex-1">Cancel</button>
                            <button type="button" onClick={confirmPower} className="btn btn-primary flex-1">Continue</button>
                        </div>
                    </div>
                </div>,
                document.body,
            )}

            {/* Add AC modal */}
            {canManage && addModal && createPortal(
                <div className="modal-backdrop is-open" onClick={(e) => e.target === e.currentTarget && setAddModal(false)}>
                    <div className="modal">
                        <div className="modal-header">
                            <div>
                                <p className="eyebrow"><i className="fa-solid fa-plus"></i> New</p>
                                <h2>Add AC Unit</h2>
                                <p className="sub">Register a new AC unit in this room</p>
                            </div>
                        </div>
                        <form onSubmit={submitAdd}>
                            <div className="modal-body space-y-3">
                                <div className="field">
                                    <label className="field-label">AC Number</label>
                                    <input className="input text-mono" type="number" min={1} max={15} placeholder="1" required value={addForm.data.ac_number} onChange={(e) => addForm.setData('ac_number', e.target.value)} />
                                    <p className="field-hint" style={addForm.errors.ac_number ? { fontSize: 11, color: 'var(--coral)', marginTop: 4 } : { fontSize: 11, color: '#94a3b8', marginTop: 4 }}>
                                        {addForm.errors.ac_number ?? 'A number from 1 to 15'}
                                    </p>
                                </div>
                                <div className="field">
                                    <label className="field-label">AC Name</label>
                                    <input className="input" type="text" placeholder="unit_a" required value={addForm.data.name} onChange={(e) => addForm.setData('name', e.target.value)} />
                                    <p className="field-hint" style={addForm.errors.name ? { fontSize: 11, color: 'var(--coral)', marginTop: 4 } : { fontSize: 11, color: '#94a3b8', marginTop: 4 }}>
                                        {addForm.errors.name ?? 'No spaces allowed'}
                                    </p>
                                </div>
                                <div className="field">
                                    <label className="field-label">Brand</label>
                                    <input className="input" type="text" placeholder="daikin" required value={addForm.data.brand} onChange={(e) => addForm.setData('brand', e.target.value)} />
                                    <p className="field-hint" style={addForm.errors.brand ? { fontSize: 11, color: 'var(--coral)', marginTop: 4 } : { fontSize: 11, color: '#94a3b8', marginTop: 4 }}>
                                        {addForm.errors.brand ?? 'No spaces allowed'}
                                    </p>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-ghost" onClick={() => setAddModal(false)}>Cancel</button>
                                <button type="submit" className="btn btn-primary" disabled={addForm.processing}>{addForm.processing ? 'Creating…' : 'Create AC Unit'}</button>
                            </div>
                        </form>
                    </div>
                </div>,
                document.body,
            )}
        </AppLayout>
    );
}

interface PanelProps {
    ac: AcControlUnit;
    canEditTimer: boolean;
    onSetTemp: (ac: AcControlUnit, v: number) => void;
    onTogglePower: () => void;
    onSetField: (ac: AcControlUnit, field: 'mode' | 'fan_speed' | 'swing', value: string, urlSeg: string) => void;
        isBusy: (field: string) => boolean;
        onOpenTimer: () => void;
        onSaveTimer: (ac: AcControlUnit, on: string, off: string) => void;
        onDeleteTimer: (ac: AcControlUnit) => void;
    }

        function AcPanel({ ac, canEditTimer, onSetTemp, onTogglePower, onSetField, isBusy, onOpenTimer, onSaveTimer, onDeleteTimer }: PanelProps) {
        const on = ac.power === 'ON';
        const cat = tempCategory(ac.set_temperature);
        const [timerOn, setTimerOn] = useState(ac.timer_on ?? '');
        const [timerOff, setTimerOff] = useState(ac.timer_off ?? '');
        const hasTimer = !!ac.timer_on || !!ac.timer_off;

    useEffect(() => {
        setTimerOn(ac.timer_on ?? '');
        setTimerOff(ac.timer_off ?? '');
    }, [ac.timer_on, ac.timer_off]);

    const ButtonGrid = ({ items, current, field, urlSeg }: { items: [string, string, string][]; current: string; field: 'mode' | 'fan_speed' | 'swing'; urlSeg: string }) => {
        const busy = isBusy(field);
        return (
            <div className="grid grid-cols-4 gap-2">
                {items.map(([val, icon, lbl]) => (
                    <button key={val} type="button" disabled={busy} className={`mode-btn-h ${current === val ? 'active' : ''}`} data-mode={val} onClick={() => onSetField(ac, field, val, urlSeg)}>
                        <i className={`fa-solid ${busy && current === val ? 'fa-spinner fa-spin' : icon}`}></i>
                        <span>{lbl}</span>
                    </button>
                ))}
            </div>
        );
    };

    return (
        <div className="ac-panel" data-ac-id={ac.id}>
            <div className="grid grid-cols-1 md:grid-cols-[300px_1fr] lg:grid-cols-[340px_1fr] gap-3">
                {/* LEFT: ring + power + stepper */}
                <div className={`panel ac-ring-panel temp-panel-${on ? cat : 'off'}`} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 24, padding: '32px 20px' }}>
                    <div className={`temp-ring temp-${cat} ${on ? '' : 'ring-off'}`} style={{ ['--fill-deg' as string]: `${fillDeg(ac.set_temperature)}deg` }}>
                        <div className="temp-ring-inner">
                            <p className="ring-label">AC Temp</p>
                            <div className="ring-temp">
                                <span className="temp-value">{ac.set_temperature}</span><span className="unit">°C</span>
                            </div>
                            <p className="ring-summary" style={{ textTransform: 'capitalize' }}>
                                {cap(ac.mode)} · {cap(ac.fan_speed)} · {swingLabel(ac.swing)}
                            </p>
                        </div>
                    </div>
                    <div className="ctrl-row">
                        <button type="button" className="ctrl-btn" disabled={isBusy('temp')} onClick={() => onSetTemp(ac, ac.set_temperature - 1)} title="Lower temperature" aria-label="Lower temperature">
                            <i className={`fa-solid ${isBusy('temp') ? 'fa-spinner fa-spin' : 'fa-minus'}`}></i>
                        </button>
                        <button type="button" className={`power-btn ${on ? 'on' : ''}`} disabled={isBusy('power')} onClick={onTogglePower} title="Toggle power" aria-label="Toggle power">
                            <i className={`fa-solid ${isBusy('power') ? 'fa-spinner fa-spin' : 'fa-power-off'}`}></i>
                        </button>
                        <button type="button" className="ctrl-btn" disabled={isBusy('temp')} onClick={() => onSetTemp(ac, ac.set_temperature + 1)} title="Raise temperature" aria-label="Raise temperature">
                            <i className={`fa-solid ${isBusy('temp') ? 'fa-spinner fa-spin' : 'fa-plus'}`}></i>
                        </button>
                    </div>
                    <div className="ring-chips">
                        <span className="ring-chip">16°C min</span>
                        <span className="ring-chip">30°C max</span>
                    </div>
                </div>

                {/* RIGHT */}
                <div className="flex flex-col gap-3">
                    <div className="panel">
                        <p className="eyebrow" style={{ marginBottom: 12 }}>Mode</p>
                        <ButtonGrid items={MODES} current={ac.mode} field="mode" urlSeg="mode" />
                    </div>
                    <div className="panel">
                        <p className="eyebrow" style={{ marginBottom: 12 }}>Fan Speed</p>
                        <ButtonGrid items={FANS} current={ac.fan_speed} field="fan_speed" urlSeg="fan-speed" />
                    </div>
                    <div className="panel">
                        <p className="eyebrow" style={{ marginBottom: 12 }}>Swing</p>
                        <ButtonGrid items={SWINGS} current={ac.swing} field="swing" urlSeg="swing" />
                    </div>
                    <div className="panel">
                        <div className="flex items-center justify-between mb-3">
                            <p className="eyebrow" style={{ color: 'var(--amber)', margin: 0 }}><i className="fa-solid fa-clock"></i> Set Timer</p>
                            <button type="button" onClick={onOpenTimer} className="btn btn-soft btn-xs">
                                <i className="fa-solid fa-pen text-[9px]"></i>
                                <span>Edit</span>
                            </button>
                        </div>

                        {!canEditTimer && (
                            hasTimer ? (
                                <div>
                                    <div className="timer-state">
                                        <div className={`timer-card ${ac.timer_on ? 'is-on' : ''}`}>
                                            <span className="t-icon"><i className="fa-solid fa-circle-play"></i></span>
                                            <div className="t-meta">
                                                <p className="t-label">Turn On</p>
                                                <p className={`t-value ${ac.timer_on ? '' : 'empty'}`}>{ac.timer_on ?? '—'}</p>
                                            </div>
                                        </div>
                                        <div className={`timer-card ${ac.timer_off ? 'is-off' : ''}`}>
                                            <span className="t-icon"><i className="fa-solid fa-circle-stop"></i></span>
                                            <div className="t-meta">
                                                <p className="t-label">Turn Off</p>
                                                <p className={`t-value ${ac.timer_off ? '' : 'empty'}`}>{ac.timer_off ?? '—'}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" className="timer-delete-btn mt-3" onClick={() => onDeleteTimer(ac)}>
                                        <i className="fa-solid fa-trash"></i>
                                        <span>Delete Timer</span>
                                    </button>
                                </div>
                            ) : (
                                <div className="timer-empty">
                                    <i className="fa-regular fa-clock"></i>
                                    No timer set
                                </div>
                            )
                        )}

                        {canEditTimer && (
                            <form className="timer-form" onSubmit={(e) => { e.preventDefault(); onSaveTimer(ac, timerOn, timerOff); }}>
                                <div className="grid grid-cols-2 gap-3 mb-3">
                                    <div className="field">
                                        <label className="field-label" htmlFor={`timer-on-${ac.id}`}><i className="fa-solid fa-circle-play text-[9px]" style={{ color: 'var(--mint)' }}></i> Turn ON</label>
                                        <input id={`timer-on-${ac.id}`} className="input text-mono" type="time" title="Timer ON time" aria-label="Timer ON time" value={timerOn} onChange={(e) => setTimerOn(e.target.value)} />
                                    </div>
                                    <div className="field">
                                        <label className="field-label" htmlFor={`timer-off-${ac.id}`}><i className="fa-solid fa-circle-stop text-[9px]" style={{ color: 'var(--coral)' }}></i> Turn OFF</label>
                                        <input id={`timer-off-${ac.id}`} className="input text-mono" type="time" title="Timer OFF time" aria-label="Timer OFF time" value={timerOff} onChange={(e) => setTimerOff(e.target.value)} />
                                    </div>
                                </div>
                                <button type="submit" className="btn btn-primary btn-sm save-timer-btn" style={{ width: '100%' }}>
                                    <i className="fa-solid fa-check text-[10px]"></i>
                                    <span>Save Timer</span>
                                </button>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
