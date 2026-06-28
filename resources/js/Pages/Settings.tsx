import { useEffect, useMemo, useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';
import '../../css/settings.css';

interface Setting {
    key: string;
    label: string;
    description: string;
    value: number | string;
    default: number | string;
    min?: number;
    max?: number;
    unit?: string;
    type?: string;
}

interface SettingsProps {
    retentionSettings: Setting[];
    monitoringSettings: Setting[];
    mqttSettings: Setting[];
}

type SettingsGroup = 'mqtt' | 'retention' | 'monitoring';

export default function Settings({ retentionSettings, monitoringSettings, mqttSettings }: SettingsProps) {
    const { flash } = usePage<PageProps>().props;
    const [savingGroup, setSavingGroup] = useState<SettingsGroup | null>(null);
    const allSettings = useMemo(
        () => [...retentionSettings, ...monitoringSettings, ...mqttSettings],
        [retentionSettings, monitoringSettings, mqttSettings],
    );

    const initialData = useMemo(
        () => Object.fromEntries(allSettings.map((setting) => [setting.key, String(setting.value)])),
        [allSettings],
    );

    const { data, setData, put, processing, errors, transform } = useForm<Record<string, string>>(initialData);

    useEffect(() => {
        if (! flash.success) {
            return;
        }

        const toastTimer = window.setTimeout(() => {
            window.smToast?.(flash.success ?? 'Settings berhasil disimpan', 'success');
        }, 0);

        return () => window.clearTimeout(toastTimer);
    }, [flash.success]);

    const submitSettings = (settings: Setting[], group: SettingsGroup) => {
        const payload = Object.fromEntries(settings.map((setting) => [setting.key, data[setting.key] ?? String(setting.value)]));

        setSavingGroup(group);
        transform(() => ({
            ...payload,
            _settings_group: group,
        }));
        put('/settings', {
            preserveScroll: true,
            onFinish: () => {
                setSavingGroup(null);
                transform((values) => values);
            },
        });
    };

    const renderSaveButton = (settings: Setting[], group: SettingsGroup, label: string) => (
        <button type="button" className="btn btn-primary settings-save" disabled={processing} onClick={() => submitSettings(settings, group)}>
            <i className="fa-solid fa-floppy-disk"></i>
            <span>{savingGroup === group ? 'Saving...' : label}</span>
        </button>
    );

    const adjust = (setting: Setting, step: number) => {
        const current = Number(data[setting.key] || setting.value);
        const next = Math.min(setting.max ?? Number(current + step), Math.max(setting.min ?? Number(current - step), current + step));
        setData(setting.key, String(next));
    };

    const iconFor = (setting: Setting) => {
        if (setting.key.includes('temperature') || setting.key.includes('sensor')) return 'fa-temperature-half';
        if (setting.key.includes('notification')) return 'fa-bell';
        if (setting.key.includes('device_check')) return 'fa-satellite-dish';
        if (setting.key.includes('mqtt')) return 'fa-network-wired';
        return 'fa-clock-rotate-left';
    };

    const isTextSetting = (setting: Setting) => setting.type === 'text' || setting.type === 'password';

    const renderSettings = (settings: Setting[]) => (
        <div className="settings-list">
            {settings.map((setting) => {
                const textSetting = isTextSetting(setting);

                return (
                    <div className="retention-row" key={setting.key}>
                        <div className="retention-main">
                            <span className="retention-icon">
                                <i className={`fa-solid ${iconFor(setting)}`}></i>
                            </span>
                            <div>
                                <p className="retention-title">{setting.label}</p>
                                <p className="retention-desc">{setting.description}</p>
                            </div>
                        </div>

                        <div className={`retention-control${textSetting ? ' retention-control--text' : ''}`}>
                            {textSetting ? (
                                <label className="setting-input-wrap">
                                    <input
                                        className="setting-input"
                                        type={setting.type === 'password' ? 'password' : 'text'}
                                        aria-label={setting.label}
                                        value={data[setting.key] ?? String(setting.value)}
                                        onChange={(e) => setData(setting.key, e.target.value)}
                                        autoComplete="off"
                                    />
                                </label>
                            ) : (
                                <>
                                    <button type="button" className="step-btn" onClick={() => adjust(setting, -1)} aria-label={`Decrease ${setting.label}`}>
                                        <i className="fa-solid fa-minus"></i>
                                    </button>
                                    <label className="days-input-wrap">
                                        <input
                                            className="days-input"
                                            type="number"
                                            aria-label={setting.label}
                                            min={setting.min}
                                            max={setting.max}
                                            value={data[setting.key] ?? String(setting.value)}
                                            onChange={(e) => setData(setting.key, e.target.value)}
                                        />
                                        <span>{setting.unit ?? 'days'}</span>
                                    </label>
                                    <button type="button" className="step-btn" onClick={() => adjust(setting, 1)} aria-label={`Increase ${setting.label}`}>
                                        <i className="fa-solid fa-plus"></i>
                                    </button>
                                </>
                            )}
                        </div>

                        {(setting.min !== undefined || setting.max !== undefined) && (
                            <div className="retention-meta">
                                {setting.min !== undefined && <span>Min {setting.min}</span>}
                                {setting.max !== undefined && <span>Max {setting.max}</span>}
                            </div>
                        )}

                        {errors[setting.key] && <p className="settings-error">{errors[setting.key]}</p>}
                    </div>
                );
            })}
        </div>
    );

    return (
        <AppLayout title="Settings" subtitle="System retention & monitoring">
            <Head title="Settings" />

            <form className="settings-form" onSubmit={(e) => e.preventDefault()}>
                <div className="settings-grid">
                    <div className="settings-panel settings-panel--column">
                        <div className="settings-head settings-head--compact">
                            <div>
                                <p className="settings-eyebrow"><i className="fa-solid fa-network-wired"></i> Connection</p>
                                <h2>MQTT Broker</h2>
                            </div>
                        </div>

                        <section className="settings-section">
                            {renderSettings(mqttSettings)}
                        </section>

                        <div className="settings-panel-actions">
                            {renderSaveButton(mqttSettings, 'mqtt', 'Save MQTT')}
                        </div>
                    </div>

                    <div className="settings-panel settings-panel--column">
                        <div className="settings-head settings-head--compact">
                            <div>
                                <p className="settings-eyebrow"><i className="fa-solid fa-clock-rotate-left"></i> Storage</p>
                                <h2>Cleanup Retention</h2>
                            </div>
                        </div>

                        <section className="settings-section">
                            {renderSettings(retentionSettings)}
                        </section>

                        <div className="settings-panel-actions">
                            {renderSaveButton(retentionSettings, 'retention', 'Save Retention')}
                        </div>
                    </div>

                    <div className="settings-panel settings-panel--column">
                        <div className="settings-head settings-head--compact">
                            <div>
                                <p className="settings-eyebrow"><i className="fa-solid fa-satellite-dish"></i> Watchdog</p>
                                <h2>Device Monitoring</h2>
                            </div>
                        </div>

                        <section className="settings-section">
                            {renderSettings(monitoringSettings)}
                        </section>

                        <div className="settings-panel-actions">
                            {renderSaveButton(monitoringSettings, 'monitoring', 'Save Monitoring')}
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
