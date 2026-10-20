import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
import operations from './operations'
/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/disbursements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::index
* @see app/Http/Controllers/StaffDisbursementController.php:25
* @route '/api/v1/staff/disbursements'
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

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
export const show = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/disbursements/{disbursement}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
show.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return show.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
show.get = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
show.head = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
const showForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showForm.get = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::show
* @see app/Http/Controllers/StaffDisbursementController.php:30
* @route '/api/v1/staff/disbursements/{disbursement}'
*/
showForm.head = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\StaffDisbursementController::hold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
export const hold = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: hold.url(args, options),
    method: 'post',
})

hold.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/hold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::hold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
hold.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return hold.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::hold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
hold.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: hold.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::hold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
const holdForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: hold.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::hold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/hold'
*/
holdForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: hold.url(args, options),
    method: 'post',
})

hold.form = holdForm

/**
* @see \App\Http\Controllers\StaffDisbursementController::releaseHold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
export const releaseHold = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releaseHold.url(args, options),
    method: 'post',
})

releaseHold.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/release-hold',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::releaseHold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
releaseHold.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return releaseHold.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::releaseHold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
releaseHold.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releaseHold.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::releaseHold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
const releaseHoldForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releaseHold.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::releaseHold
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/release-hold'
*/
releaseHoldForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releaseHold.url(args, options),
    method: 'post',
})

releaseHold.form = releaseHoldForm

/**
* @see \App\Http\Controllers\StaffDisbursementController::requery
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
export const requery = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requery.url(args, options),
    method: 'post',
})

requery.definition = {
    methods: ["post"],
    url: '/api/v1/staff/disbursements/{disbursement}/requery',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffDisbursementController::requery
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
requery.url = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { disbursement: args }
    }

    if (Array.isArray(args)) {
        args = {
            disbursement: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        disbursement: args.disbursement,
    }

    return requery.definition.url
            .replace('{disbursement}', parsedArgs.disbursement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDisbursementController::requery
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
requery.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requery.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::requery
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
const requeryForm = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requery.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffDisbursementController::requery
* @see app/Http/Controllers/StaffDisbursementController.php:35
* @route '/api/v1/staff/disbursements/{disbursement}/requery'
*/
requeryForm.post = (args: { disbursement: string | number } | [disbursement: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requery.url(args, options),
    method: 'post',
})

requery.form = requeryForm

const disbursements = {
    index: Object.assign(index, index),
    operations: Object.assign(operations, operations),
    show: Object.assign(show, show),
    hold: Object.assign(hold, hold),
    releaseHold: Object.assign(releaseHold, releaseHold),
    requery: Object.assign(requery, requery),
}

export default disbursements