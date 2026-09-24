import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/pulse',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PulseController::index
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:28
* @route '/pulse'
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
* @see \App\Http\Controllers\PulseController::previewInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:42
* @route '/pulse/investor/preview'
*/
export const previewInvestor = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: previewInvestor.url(options),
    method: 'post',
})

previewInvestor.definition = {
    methods: ["post"],
    url: '/pulse/investor/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::previewInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:42
* @route '/pulse/investor/preview'
*/
previewInvestor.url = (options?: RouteQueryOptions) => {
    return previewInvestor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::previewInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:42
* @route '/pulse/investor/preview'
*/
previewInvestor.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: previewInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::previewInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:42
* @route '/pulse/investor/preview'
*/
const previewInvestorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: previewInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::previewInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:42
* @route '/pulse/investor/preview'
*/
previewInvestorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: previewInvestor.url(options),
    method: 'post',
})

previewInvestor.form = previewInvestorForm

/**
* @see \App\Http\Controllers\PulseController::previewBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
export const previewBusiness = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: previewBusiness.url(options),
    method: 'post',
})

previewBusiness.definition = {
    methods: ["post"],
    url: '/pulse/business/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::previewBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
previewBusiness.url = (options?: RouteQueryOptions) => {
    return previewBusiness.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::previewBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
previewBusiness.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: previewBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::previewBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
const previewBusinessForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: previewBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::previewBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:54
* @route '/pulse/business/preview'
*/
previewBusinessForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: previewBusiness.url(options),
    method: 'post',
})

previewBusiness.form = previewBusinessForm

/**
* @see \App\Http\Controllers\PulseController::storeInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
export const storeInvestor = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeInvestor.url(options),
    method: 'post',
})

storeInvestor.definition = {
    methods: ["post"],
    url: '/pulse/investor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::storeInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
storeInvestor.url = (options?: RouteQueryOptions) => {
    return storeInvestor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::storeInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
storeInvestor.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::storeInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
const storeInvestorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeInvestor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::storeInvestor
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:70
* @route '/pulse/investor'
*/
storeInvestorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeInvestor.url(options),
    method: 'post',
})

storeInvestor.form = storeInvestorForm

/**
* @see \App\Http\Controllers\PulseController::storeBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
export const storeBusiness = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBusiness.url(options),
    method: 'post',
})

storeBusiness.definition = {
    methods: ["post"],
    url: '/pulse/business',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PulseController::storeBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
storeBusiness.url = (options?: RouteQueryOptions) => {
    return storeBusiness.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PulseController::storeBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
storeBusiness.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::storeBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
const storeBusinessForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBusiness.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PulseController::storeBusiness
* @see Users/engineersticity/Documents/projects/rozine/app/Http/Controllers/PulseController.php:91
* @route '/pulse/business'
*/
storeBusinessForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBusiness.url(options),
    method: 'post',
})

storeBusiness.form = storeBusinessForm

const PulseController = { index, previewInvestor, previewBusiness, storeInvestor, storeBusiness }

export default PulseController