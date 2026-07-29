import {
    queryParams,
    type RouteQueryOptions,
    type RouteDefinition,
    type RouteFormDefinition,
} from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:47
 * @route '/pulse/statement'
 */
export const store = (
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/pulse/statement',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:47
 * @route '/pulse/statement'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:47
 * @route '/pulse/statement'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:47
 * @route '/pulse/statement'
 */
const storeForm = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\PulseController::store
 * @see app/Http/Controllers/PulseController.php:47
 * @route '/pulse/statement'
 */
storeForm.post = (
    options?: RouteQueryOptions,
): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
});

store.form = storeForm;

const statement = {
    store: Object.assign(store, store),
};

export default statement;
