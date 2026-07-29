import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:91
 * @route '/pulse/business'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/pulse/business',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:91
 * @route '/pulse/business'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:91
 * @route '/pulse/business'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:91
 * @route '/pulse/business'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:91
 * @route '/pulse/business'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;

const business = {
    store: Object.assign(store, store),
};

export default business;
