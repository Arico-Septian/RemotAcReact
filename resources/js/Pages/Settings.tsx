import { FormEvent, useMemo } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import '../../css/settings.css';

interface NumericSetting {
    key: string;
    label: string;
    description: string;
    value: number;
    default: number;
    min: number;
    max: number;
    unit?: string;
}

interface SettingsProps {
    retentionSettings: NumericSetting[];
    monitoringSettings: NumericSetting[];
}

export default function Settings({ retentionSettings, monitoringSettings }: SettingsProps) {
    const allSettings = useMemo(
        () => [...retentionSettings, ...monitoringSettings],
        [retentionSettings, monitoringSettings],
    );

    const initialData = useMemo(
        () => Object.fromEntries(allSettings.map((setting) => [setting.key, String(setting.value)])),
        [allSettings],
    );

    const { data, setData, put, processing, errors, recentlySuccessful } = useForm<Record<string, string>>(initialData);

    const submit = (e: FormEvent) => {
        e.preventDefault();

        put('/settings', {
            preserveScroll: true,
            onSuccess: () => window.smToast?.('Settings berhasil disimpan', 'success'),
        });
    };

    const adjust = (setting: NumericSetting, step: number) => {
        const current = Number(data[setting.key] || setting.value);
        const next = Math.min(setting.max, Math.max(setting.min, current + step));
        setData(setting.key, String(next));
    };

    const iconFor = (setting: NumericSetting) => {
        if (setting.key.includes('temperature') || setting.key.includes('sensor')) return 'fa-temperature-half';
        if (setting.key.includes('notification')) return 'fa-bell';
        if (setting.key.includes('device_check')) return 'fa-satellite-dish';
        return 'fa-clock-rotate-left';
    };

    const renderSettings = (settings: NumericSetting[]) => (
        <div className="settings-list">
            {settings.map((setting) => (
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

                    <div className="retention-control">
                        <button type="button" className="step-btn" onClick={() => adjust(setting, -1)} aria-label={`Decrease ${setting.label}`}>
                            <i className="fa-solid fa-minus"></i>
                        </button>
                        <label className="days-input-wrap">
                            <input
                                className="days-input"
                                type="number"
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
                    </div>

                    <div className="retention-meta">
                        <span>Min {setting.min}</span>
                        <span>Max {setting.max}</span>
                    </div>

                    {errors[setting.key] && <p className="settings-error">{errors[setting.key]}</p>}
                </div>
            ))}
        </div>
    );

    return (
        <AppLayout title="Settings" subtitle="System retention & monitoring">
            <Head title="Settings" />

            <form className="settings-panel" onSubmit={submit}>
                <div className="settings-head">
                    <div>
                        <p className="settings-eyebrow"><i className="fa-solid fa-sliders"></i> System Config</p>
                        <h2>Retention & Monitoring</h2>
                    </div>
                    <button type="submit" className="btn btn-primary settings-save" disabled={processing}>
                        <i className="fa-solid fa-floppy-disk"></i>
                        <span>{processing ? 'Saving...' : 'Save Settings'}</span>
                    </button>
                </div>

                <section className="settings-section">
                    <div className="settings-section-head">
                        <p>Cleanup Retention</p>
                    </div>
                    {renderSettings(retentionSettings)}
                </section>

                <section className="settings-section">
                    <div className="settings-section-head">
                        <p>Device Monitoring</p>
                    </div>
                    {renderSettings(monitoringSettings)}
                </section>

            </form>
        </AppLayout>
    );
}
