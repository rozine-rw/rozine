import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/risk-statements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::acknowledge
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
export const acknowledge = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: acknowledge.url(options),
    method: 'post',
})

acknowledge.definition = {
    methods: ["post"],
    url: '/api/v1/investor/risk-statements/acknowledgements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::acknowledge
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
acknowledge.url = (options?: RouteQueryOptions) => {
    return acknowledge.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::acknowledge
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
acknowledge.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: acknowledge.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::acknowledge
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
const acknowledgeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: acknowledge.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::acknowledge
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
acknowledgeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: acknowledge.url(options),
    method: 'post',
})

acknowledge.form = acknowledgeForm

const risks = {
    show: Object.assign(show, show),
    acknowledge: Object.assign(acknowledge, acknowledge),
}

export default risks