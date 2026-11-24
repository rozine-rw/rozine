import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
export const show = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
show.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return show.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
show.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
show.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
const showForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
showForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealsController::show
* @see app/Http/Controllers/InvestorDealsController.php:27
* @route '/investor/deals/{campaign}'
*/
showForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
export const prospectus = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: prospectus.url(args, options),
    method: 'get',
})

prospectus.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/prospectus',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
prospectus.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return prospectus.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
prospectus.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: prospectus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
prospectus.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: prospectus.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
const prospectusForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
prospectusForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusController::__invoke
* @see app/Http/Controllers/InvestorProspectusController.php:16
* @route '/investor/deals/{campaign}/prospectus'
*/
prospectusForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

prospectus.form = prospectusForm

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
export const prospectus_pdf = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: prospectus_pdf.url(args, options),
    method: 'get',
})

prospectus_pdf.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/prospectus.pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
prospectus_pdf.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { campaign: args }
    }

    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
    }

    return prospectus_pdf.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
prospectus_pdf.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: prospectus_pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
prospectus_pdf.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: prospectus_pdf.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
const prospectus_pdfForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus_pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
prospectus_pdfForm.get = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus_pdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorProspectusPdfController::__invoke
* @see app/Http/Controllers/InvestorProspectusPdfController.php:22
* @route '/investor/deals/{campaign}/prospectus.pdf'
*/
prospectus_pdfForm.head = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: prospectus_pdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

prospectus_pdf.form = prospectus_pdfForm

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
export const photo = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})

photo.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/photos/{photo}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
photo.url = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
            photo: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
        photo: args.photo,
    }

    return photo.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace('{photo}', parsedArgs.photo.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
photo.get = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
photo.head = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: photo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
const photoForm = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: photo.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
photoForm.get = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: photo.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorDealPhotoController::__invoke
* @see app/Http/Controllers/InvestorDealPhotoController.php:14
* @route '/investor/deals/{campaign}/photos/{photo}'
*/
photoForm.head = (args: { campaign: string | number, photo: string | number } | [campaign: string | number, photo: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: photo.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

photo.form = photoForm

const deals = {
    show: Object.assign(show, show),
    prospectus: Object.assign(prospectus, prospectus),
    prospectus_pdf: Object.assign(prospectus_pdf, prospectus_pdf),
    photo: Object.assign(photo, photo),
}

export default deals