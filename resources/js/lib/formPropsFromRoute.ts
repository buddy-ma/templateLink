type FormRoute = {
    url?: string;
    action?: string;
    method: string;
};

/**
 * Normalize a Wayfinder route definition for Inertia `<Form v-bind="…">`.
 */
export function formPropsFromRoute(route: FormRoute): {
    action: string;
    method: string;
} {
    return {
        action: route.action ?? route.url ?? '',
        method: route.method,
    };
}
