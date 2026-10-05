import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
const index6ee27ee88c23d53ec4fe4f870340d04a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

index6ee27ee88c23d53ec4fe4f870340d04a.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index6ee27ee88c23d53ec4fe4f870340d04a.url = (options?: RouteQueryOptions) => {
    return index6ee27ee88c23d53ec4fe4f870340d04a.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index6ee27ee88c23d53ec4fe4f870340d04a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index6ee27ee88c23d53ec4fe4f870340d04a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
const index6ee27ee88c23d53ec4fe4f870340d04aForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index6ee27ee88c23d53ec4fe4f870340d04aForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index6ee27ee88c23d53ec4fe4f870340d04aForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index6ee27ee88c23d53ec4fe4f870340d04a.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index6ee27ee88c23d53ec4fe4f870340d04a.form = index6ee27ee88c23d53ec4fe4f870340d04aForm
/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
const index3c300d59e31a5d246705b6d43698eef1 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

index3c300d59e31a5d246705b6d43698eef1.definition = {
    methods: ["get","head"],
    url: '/admin/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
index3c300d59e31a5d246705b6d43698eef1.url = (options?: RouteQueryOptions) => {
    return index3c300d59e31a5d246705b6d43698eef1.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
index3c300d59e31a5d246705b6d43698eef1.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
index3c300d59e31a5d246705b6d43698eef1.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
const index3c300d59e31a5d246705b6d43698eef1Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
index3c300d59e31a5d246705b6d43698eef1Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/admin/ledger'
*/
index3c300d59e31a5d246705b6d43698eef1Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index3c300d59e31a5d246705b6d43698eef1.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index3c300d59e31a5d246705b6d43698eef1.form = index3c300d59e31a5d246705b6d43698eef1Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffLedgerController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/ledger': index6ee27ee88c23d53ec4fe4f870340d04a,
    '/admin/ledger': index3c300d59e31a5d246705b6d43698eef1,
}

const StaffLedgerController = { index }

export default StaffLedgerController