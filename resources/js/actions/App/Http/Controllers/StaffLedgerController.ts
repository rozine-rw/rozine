import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
const StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url = (options?: RouteQueryOptions) => {
    return StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
const StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04aForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04aForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/api/v1/staff/ledger'
*/
StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04aForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a.form = StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04aForm
/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
const StaffLedgerController3c300d59e31a5d246705b6d43698eef1 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

StaffLedgerController3c300d59e31a5d246705b6d43698eef1.definition = {
    methods: ["get","head"],
    url: '/admin/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url = (options?: RouteQueryOptions) => {
    return StaffLedgerController3c300d59e31a5d246705b6d43698eef1.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
StaffLedgerController3c300d59e31a5d246705b6d43698eef1.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
StaffLedgerController3c300d59e31a5d246705b6d43698eef1.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
const StaffLedgerController3c300d59e31a5d246705b6d43698eef1Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
StaffLedgerController3c300d59e31a5d246705b6d43698eef1Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::__invoke
* @see app/Http/Controllers/StaffLedgerController.php:23
* @route '/admin/ledger'
*/
StaffLedgerController3c300d59e31a5d246705b6d43698eef1Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StaffLedgerController3c300d59e31a5d246705b6d43698eef1.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StaffLedgerController3c300d59e31a5d246705b6d43698eef1.form = StaffLedgerController3c300d59e31a5d246705b6d43698eef1Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffLedgerController::StaffLedgerController, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `StaffLedgerController['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
const StaffLedgerController = {
    '/api/v1/staff/ledger': StaffLedgerController6ee27ee88c23d53ec4fe4f870340d04a,
    '/admin/ledger': StaffLedgerController3c300d59e31a5d246705b6d43698eef1,
}

export default StaffLedgerController