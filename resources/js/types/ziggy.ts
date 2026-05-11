import { Config, RouteParams } from 'ziggy-js';

declare module 'ziggy-js' {
    interface Config {
        current(name: string, params?: RouteParams<string>): boolean;
    }
}

declare global {
    function route(): Config;
    function route(name: string, params?: RouteParams<typeof name> | undefined, absolute?: boolean): string;
}

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}
