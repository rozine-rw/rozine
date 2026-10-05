import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffLedgerController::index
* @see app/Http/Controllers/StaffLedgerController.php:16
* @route '/api/v1/staff/ledger'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const ledger = {
    index: Object.assign(index, index),
}

export default ledger