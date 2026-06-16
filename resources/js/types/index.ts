export interface AuthUser {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'operator' | 'user';
    avatar_url: string | null;
}

export interface PageProps {
    auth: {
        user: AuthUser | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
    [key: string]: unknown;
}

export interface AcStatus {
    power: 'ON' | 'OFF' | null;
    mode: string | null;
    set_temperature: number | null;
    fan_speed: string | null;
    swing: string | null;
}

export interface AcUnit {
    id: number;
    ac_number: number;
    brand: string | null;
    status: AcStatus | null;
}

export interface Room {
    id: number;
    name: string;
    floor: number | null;
    device_id: string | null;
    device_status: 'online' | 'offline';
    temperature: number | null;
    last_temperature: number | null;
    ac_units_count: number;
}

export interface OverviewRoom {
    id: number;
    name: string;
    floor: string;
    device_id: string | null;
    device_status: 'online' | 'offline';
    temperature: number | null;
    last_temperature: number | null;
    temperature_is_offline: boolean;
    ac_units_count: number;
    ac_active_count: number;
    ac_idle_count: number;
}

export interface FuzzyResult {
    status_pendinginan?: string;
    [key: string]: unknown;
}

export interface FuzzyDecision {
    action?: string;
    setpoint_before?: number | string;
    setpoint_after?: number | string;
    [key: string]: unknown;
}

export interface ManageRoom {
    id: number;
    name: string;
    floor: string;
    device_id: string | null;
    device_status: 'online' | 'offline';
    temperature: number | null;
    last_temperature: number | null;
    temperature_is_offline: boolean;
    delta_t: number;
    fuzzy: FuzzyResult | null;
    decision: FuzzyDecision | null;
    ac_active_count: number;
    ac_idle_count: number;
    ac_units_count: number;
}

export interface ManagedUser {
    id: number;
    name: string;
    email: string | null;
    avatar_url: string | null;
    role: 'admin' | 'operator' | 'user';
    is_online: boolean;
    is_self: boolean;
}

export interface ProfileUser {
    name: string;
    role: string;
    avatar_url: string | null;
    has_avatar: boolean;
    joined: string | null;
    last_login: string;
}

export interface ActivityLogRow {
    id: number;
    user_name: string;
    user_avatar: string | null;
    room: string | null;
    ac: string | null;
    badge_label: string;
    badge_class: string;
    time: string | null;
    date: string | null;
}

export interface NotificationListItem {
    id: number;
    title: string;
    message: string | null;
    link: string | null;
    is_unread: boolean;
    is_deletable: boolean;
    time_ago: string;
    time_full: string;
}

export interface AcControlUnit {
    id: number;
    ac_number: number;
    name: string;
    brand: string | null;
    power: 'ON' | 'OFF';
    set_temperature: number;
    mode: string;
    fan_speed: string;
    swing: string;
    timer_on: string | null;
    timer_off: string | null;
}

export interface AcStatusCard {
    id: number;
    ac_number: number;
    label: string | null;
    power: string;
    set_temperature: number;
    mode: string;
    fan_speed: string;
    swing: string;
    timer_on: string | null;
    timer_off: string | null;
}

export interface ActivityLog {
    id: number;
    user_name: string;
    user_initial: string;
    user_id: number | null;
    user_avatar: string | null;
    raw_activity: string;
    description: string;
    icon: string;
    tone: string;
    room: string | null;
    ac: string;
    time: string | null;
    time_human: string | null;
}
