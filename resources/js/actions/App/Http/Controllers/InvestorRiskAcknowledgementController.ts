import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
const show3ef4bd96a9b2df3fd857fce328f2dc35 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3ef4bd96a9b2df3fd857fce328f2dc35.url(options),
    method: 'get',
})

show3ef4bd96a9b2df3fd857fce328f2dc35.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/risk-statements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show3ef4bd96a9b2df3fd857fce328f2dc35.url = (options?: RouteQueryOptions) => {
    return show3ef4bd96a9b2df3fd857fce328f2dc35.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show3ef4bd96a9b2df3fd857fce328f2dc35.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3ef4bd96a9b2df3fd857fce328f2dc35.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show3ef4bd96a9b2df3fd857fce328f2dc35.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show3ef4bd96a9b2df3fd857fce328f2dc35.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
const show3ef4bd96a9b2df3fd857fce328f2dc35Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3ef4bd96a9b2df3fd857fce328f2dc35.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show3ef4bd96a9b2df3fd857fce328f2dc35Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3ef4bd96a9b2df3fd857fce328f2dc35.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/api/v1/investor/risk-statements'
*/
show3ef4bd96a9b2df3fd857fce328f2dc35Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show3ef4bd96a9b2df3fd857fce328f2dc35.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show3ef4bd96a9b2df3fd857fce328f2dc35.form = show3ef4bd96a9b2df3fd857fce328f2dc35Form
/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
const show436e2778a88cffdb3c208e1eac82aba9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show436e2778a88cffdb3c208e1eac82aba9.url(options),
    method: 'get',
})

show436e2778a88cffdb3c208e1eac82aba9.definition = {
    methods: ["get","head"],
    url: '/investor/risk-statements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
show436e2778a88cffdb3c208e1eac82aba9.url = (options?: RouteQueryOptions) => {
    return show436e2778a88cffdb3c208e1eac82aba9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
show436e2778a88cffdb3c208e1eac82aba9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show436e2778a88cffdb3c208e1eac82aba9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
show436e2778a88cffdb3c208e1eac82aba9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show436e2778a88cffdb3c208e1eac82aba9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
const show436e2778a88cffdb3c208e1eac82aba9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show436e2778a88cffdb3c208e1eac82aba9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
show436e2778a88cffdb3c208e1eac82aba9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show436e2778a88cffdb3c208e1eac82aba9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::show
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:17
* @route '/investor/risk-statements'
*/
show436e2778a88cffdb3c208e1eac82aba9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show436e2778a88cffdb3c208e1eac82aba9.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show436e2778a88cffdb3c208e1eac82aba9.form = show436e2778a88cffdb3c208e1eac82aba9Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorRiskAcknowledgementController::show, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `show['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const show = {
    '/api/v1/investor/risk-statements': show3ef4bd96a9b2df3fd857fce328f2dc35,
    '/investor/risk-statements': show436e2778a88cffdb3c208e1eac82aba9,
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
const store9fa9d19065a90a0525c34c83458a16ea = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store9fa9d19065a90a0525c34c83458a16ea.url(options),
    method: 'post',
})

store9fa9d19065a90a0525c34c83458a16ea.definition = {
    methods: ["post"],
    url: '/api/v1/investor/risk-statements/acknowledgements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
store9fa9d19065a90a0525c34c83458a16ea.url = (options?: RouteQueryOptions) => {
    return store9fa9d19065a90a0525c34c83458a16ea.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
store9fa9d19065a90a0525c34c83458a16ea.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store9fa9d19065a90a0525c34c83458a16ea.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
const store9fa9d19065a90a0525c34c83458a16eaForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store9fa9d19065a90a0525c34c83458a16ea.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/api/v1/investor/risk-statements/acknowledgements'
*/
store9fa9d19065a90a0525c34c83458a16eaForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store9fa9d19065a90a0525c34c83458a16ea.url(options),
    method: 'post',
})

store9fa9d19065a90a0525c34c83458a16ea.form = store9fa9d19065a90a0525c34c83458a16eaForm
/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/investor/risk-statements/acknowledgements'
*/
const store2af8161090a4699cfc2d090b6a142fe0 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store2af8161090a4699cfc2d090b6a142fe0.url(options),
    method: 'post',
})

store2af8161090a4699cfc2d090b6a142fe0.definition = {
    methods: ["post"],
    url: '/investor/risk-statements/acknowledgements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/investor/risk-statements/acknowledgements'
*/
store2af8161090a4699cfc2d090b6a142fe0.url = (options?: RouteQueryOptions) => {
    return store2af8161090a4699cfc2d090b6a142fe0.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/investor/risk-statements/acknowledgements'
*/
store2af8161090a4699cfc2d090b6a142fe0.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store2af8161090a4699cfc2d090b6a142fe0.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/investor/risk-statements/acknowledgements'
*/
const store2af8161090a4699cfc2d090b6a142fe0Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store2af8161090a4699cfc2d090b6a142fe0.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorRiskAcknowledgementController::store
* @see app/Http/Controllers/InvestorRiskAcknowledgementController.php:24
* @route '/investor/risk-statements/acknowledgements'
*/
store2af8161090a4699cfc2d090b6a142fe0Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store2af8161090a4699cfc2d090b6a142fe0.url(options),
    method: 'post',
})

store2af8161090a4699cfc2d090b6a142fe0.form = store2af8161090a4699cfc2d090b6a142fe0Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorRiskAcknowledgementController::store, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `store['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const store = {
    '/api/v1/investor/risk-statements/acknowledgements': store9fa9d19065a90a0525c34c83458a16ea,
    '/investor/risk-statements/acknowledgements': store2af8161090a4699cfc2d090b6a142fe0,
}

const InvestorRiskAcknowledgementController = { show, store }

export default InvestorRiskAcknowledgementController