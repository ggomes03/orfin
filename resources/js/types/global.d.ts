import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            exerciseYear: {
                selected: number;
                options: number[];
            };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
