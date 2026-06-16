import type { AxiosInstance } from 'axios';

declare global {
    interface Window {
        axios: AxiosInstance;
        Echo: any;
        Pusher: any;
        Chart: any;
        smToast?: (message: string, type?: 'success' | 'error' | 'info') => void;
    }
}

export {};
