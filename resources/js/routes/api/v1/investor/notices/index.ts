import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorNoticesController::read_all
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
export const read_all = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read_all.url(options),
    method: 'post',
})

read_all.definition = {
    methods: ["post"],
    url: '/api/v1/investor/notices/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::read_all
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
read_all.url = (options?: RouteQueryOptions) => {
    return read_all.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::read_all
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
read_all.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read_all.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read_all
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
const read_allForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read_all.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read_all
* @see app/Http/Controllers/InvestorNoticesController.php:27
* @route '/api/v1/investor/notices/read-all'
*/
read_allForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read_all.url(options),
    method: 'post',
})

read_all.form = read_allForm

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
export const read = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
})

read.definition = {
    methods: ["post"],
    url: '/api/v1/investor/notices/{notice}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
read.url = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notice: args }
    }

    if (Array.isArray(args)) {
        args = {
            notice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notice: args.notice,
    }

    return read.definition.url
            .replace('{notice}', parsedArgs.notice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
read.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
const readForm = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorNoticesController::read
* @see app/Http/Controllers/InvestorNoticesController.php:22
* @route '/api/v1/investor/notices/{notice}/read'
*/
readForm.post = (args: { notice: string | number } | [notice: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
})

read.form = readForm

const notices = {
    read_all: Object.assign(read_all, read_all),
    read: Object.assign(read, read),
}

export default notices