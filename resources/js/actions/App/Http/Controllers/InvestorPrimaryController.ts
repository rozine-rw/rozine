import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/api/v1/investor/deals/{campaign}/reservations'
*/
const reserve569453ae235f04e8e1ffe08b577d123a = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve569453ae235f04e8e1ffe08b577d123a.url(args, options),
    method: 'post',
})

reserve569453ae235f04e8e1ffe08b577d123a.definition = {
    methods: ["post"],
    url: '/api/v1/investor/deals/{campaign}/reservations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/api/v1/investor/deals/{campaign}/reservations'
*/
reserve569453ae235f04e8e1ffe08b577d123a.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reserve569453ae235f04e8e1ffe08b577d123a.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/api/v1/investor/deals/{campaign}/reservations'
*/
reserve569453ae235f04e8e1ffe08b577d123a.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve569453ae235f04e8e1ffe08b577d123a.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/api/v1/investor/deals/{campaign}/reservations'
*/
const reserve569453ae235f04e8e1ffe08b577d123aForm = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve569453ae235f04e8e1ffe08b577d123a.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/api/v1/investor/deals/{campaign}/reservations'
*/
reserve569453ae235f04e8e1ffe08b577d123aForm.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve569453ae235f04e8e1ffe08b577d123a.url(args, options),
    method: 'post',
})

reserve569453ae235f04e8e1ffe08b577d123a.form = reserve569453ae235f04e8e1ffe08b577d123aForm
/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/investor/deals/{campaign}/reservations'
*/
const reserve99473108201d15d29adcdaea6a44a104 = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve99473108201d15d29adcdaea6a44a104.url(args, options),
    method: 'post',
})

reserve99473108201d15d29adcdaea6a44a104.definition = {
    methods: ["post"],
    url: '/investor/deals/{campaign}/reservations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/investor/deals/{campaign}/reservations'
*/
reserve99473108201d15d29adcdaea6a44a104.url = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return reserve99473108201d15d29adcdaea6a44a104.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/investor/deals/{campaign}/reservations'
*/
reserve99473108201d15d29adcdaea6a44a104.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reserve99473108201d15d29adcdaea6a44a104.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/investor/deals/{campaign}/reservations'
*/
const reserve99473108201d15d29adcdaea6a44a104Form = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve99473108201d15d29adcdaea6a44a104.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::reserve
* @see app/Http/Controllers/InvestorPrimaryController.php:26
* @route '/investor/deals/{campaign}/reservations'
*/
reserve99473108201d15d29adcdaea6a44a104Form.post = (args: { campaign: string | number } | [campaign: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reserve99473108201d15d29adcdaea6a44a104.url(args, options),
    method: 'post',
})

reserve99473108201d15d29adcdaea6a44a104.form = reserve99473108201d15d29adcdaea6a44a104Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorPrimaryController::reserve, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `reserve['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const reserve = {
    '/api/v1/investor/deals/{campaign}/reservations': reserve569453ae235f04e8e1ffe08b577d123a,
    '/investor/deals/{campaign}/reservations': reserve99473108201d15d29adcdaea6a44a104,
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/api/v1/investor/reservations/{reservation}/confirm'
*/
const confirm071bb353caf9e5a6ea27d8de3f5a097f = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm071bb353caf9e5a6ea27d8de3f5a097f.url(args, options),
    method: 'post',
})

confirm071bb353caf9e5a6ea27d8de3f5a097f.definition = {
    methods: ["post"],
    url: '/api/v1/investor/reservations/{reservation}/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/api/v1/investor/reservations/{reservation}/confirm'
*/
confirm071bb353caf9e5a6ea27d8de3f5a097f.url = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reservation: args }
    }

    if (Array.isArray(args)) {
        args = {
            reservation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reservation: args.reservation,
    }

    return confirm071bb353caf9e5a6ea27d8de3f5a097f.definition.url
            .replace('{reservation}', parsedArgs.reservation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/api/v1/investor/reservations/{reservation}/confirm'
*/
confirm071bb353caf9e5a6ea27d8de3f5a097f.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm071bb353caf9e5a6ea27d8de3f5a097f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/api/v1/investor/reservations/{reservation}/confirm'
*/
const confirm071bb353caf9e5a6ea27d8de3f5a097fForm = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm071bb353caf9e5a6ea27d8de3f5a097f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/api/v1/investor/reservations/{reservation}/confirm'
*/
confirm071bb353caf9e5a6ea27d8de3f5a097fForm.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm071bb353caf9e5a6ea27d8de3f5a097f.url(args, options),
    method: 'post',
})

confirm071bb353caf9e5a6ea27d8de3f5a097f.form = confirm071bb353caf9e5a6ea27d8de3f5a097fForm
/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/investor/reservations/{reservation}/confirm'
*/
const confirma7667de5aefcd00aca89630e7b15b660 = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirma7667de5aefcd00aca89630e7b15b660.url(args, options),
    method: 'post',
})

confirma7667de5aefcd00aca89630e7b15b660.definition = {
    methods: ["post"],
    url: '/investor/reservations/{reservation}/confirm',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/investor/reservations/{reservation}/confirm'
*/
confirma7667de5aefcd00aca89630e7b15b660.url = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reservation: args }
    }

    if (Array.isArray(args)) {
        args = {
            reservation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reservation: args.reservation,
    }

    return confirma7667de5aefcd00aca89630e7b15b660.definition.url
            .replace('{reservation}', parsedArgs.reservation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/investor/reservations/{reservation}/confirm'
*/
confirma7667de5aefcd00aca89630e7b15b660.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirma7667de5aefcd00aca89630e7b15b660.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/investor/reservations/{reservation}/confirm'
*/
const confirma7667de5aefcd00aca89630e7b15b660Form = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirma7667de5aefcd00aca89630e7b15b660.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::confirm
* @see app/Http/Controllers/InvestorPrimaryController.php:33
* @route '/investor/reservations/{reservation}/confirm'
*/
confirma7667de5aefcd00aca89630e7b15b660Form.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirma7667de5aefcd00aca89630e7b15b660.url(args, options),
    method: 'post',
})

confirma7667de5aefcd00aca89630e7b15b660.form = confirma7667de5aefcd00aca89630e7b15b660Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorPrimaryController::confirm, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `confirm['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const confirm = {
    '/api/v1/investor/reservations/{reservation}/confirm': confirm071bb353caf9e5a6ea27d8de3f5a097f,
    '/investor/reservations/{reservation}/confirm': confirma7667de5aefcd00aca89630e7b15b660,
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/api/v1/investor/reservations/{reservation}/release'
*/
const releasee94260be7793b4986fddf3fde5282ec3 = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releasee94260be7793b4986fddf3fde5282ec3.url(args, options),
    method: 'post',
})

releasee94260be7793b4986fddf3fde5282ec3.definition = {
    methods: ["post"],
    url: '/api/v1/investor/reservations/{reservation}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/api/v1/investor/reservations/{reservation}/release'
*/
releasee94260be7793b4986fddf3fde5282ec3.url = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reservation: args }
    }

    if (Array.isArray(args)) {
        args = {
            reservation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reservation: args.reservation,
    }

    return releasee94260be7793b4986fddf3fde5282ec3.definition.url
            .replace('{reservation}', parsedArgs.reservation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/api/v1/investor/reservations/{reservation}/release'
*/
releasee94260be7793b4986fddf3fde5282ec3.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releasee94260be7793b4986fddf3fde5282ec3.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/api/v1/investor/reservations/{reservation}/release'
*/
const releasee94260be7793b4986fddf3fde5282ec3Form = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releasee94260be7793b4986fddf3fde5282ec3.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/api/v1/investor/reservations/{reservation}/release'
*/
releasee94260be7793b4986fddf3fde5282ec3Form.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releasee94260be7793b4986fddf3fde5282ec3.url(args, options),
    method: 'post',
})

releasee94260be7793b4986fddf3fde5282ec3.form = releasee94260be7793b4986fddf3fde5282ec3Form
/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/investor/reservations/{reservation}/release'
*/
const releasef8ea3d2bec7566206046b917e432010f = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releasef8ea3d2bec7566206046b917e432010f.url(args, options),
    method: 'post',
})

releasef8ea3d2bec7566206046b917e432010f.definition = {
    methods: ["post"],
    url: '/investor/reservations/{reservation}/release',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/investor/reservations/{reservation}/release'
*/
releasef8ea3d2bec7566206046b917e432010f.url = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reservation: args }
    }

    if (Array.isArray(args)) {
        args = {
            reservation: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reservation: args.reservation,
    }

    return releasef8ea3d2bec7566206046b917e432010f.definition.url
            .replace('{reservation}', parsedArgs.reservation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/investor/reservations/{reservation}/release'
*/
releasef8ea3d2bec7566206046b917e432010f.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: releasef8ea3d2bec7566206046b917e432010f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/investor/reservations/{reservation}/release'
*/
const releasef8ea3d2bec7566206046b917e432010fForm = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releasef8ea3d2bec7566206046b917e432010f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::release
* @see app/Http/Controllers/InvestorPrimaryController.php:40
* @route '/investor/reservations/{reservation}/release'
*/
releasef8ea3d2bec7566206046b917e432010fForm.post = (args: { reservation: string | number } | [reservation: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: releasef8ea3d2bec7566206046b917e432010f.url(args, options),
    method: 'post',
})

releasef8ea3d2bec7566206046b917e432010f.form = releasef8ea3d2bec7566206046b917e432010fForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorPrimaryController::release, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `release['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const release = {
    '/api/v1/investor/reservations/{reservation}/release': releasee94260be7793b4986fddf3fde5282ec3,
    '/investor/reservations/{reservation}/release': releasef8ea3d2bec7566206046b917e432010f,
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
const commitment863bb59b225460d3979143a9ba64ce62 = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commitment863bb59b225460d3979143a9ba64ce62.url(args, options),
    method: 'get',
})

commitment863bb59b225460d3979143a9ba64ce62.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/commitments/{commitment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
commitment863bb59b225460d3979143a9ba64ce62.url = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { commitment: args }
    }

    if (Array.isArray(args)) {
        args = {
            commitment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        commitment: args.commitment,
    }

    return commitment863bb59b225460d3979143a9ba64ce62.definition.url
            .replace('{commitment}', parsedArgs.commitment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
commitment863bb59b225460d3979143a9ba64ce62.get = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commitment863bb59b225460d3979143a9ba64ce62.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
commitment863bb59b225460d3979143a9ba64ce62.head = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: commitment863bb59b225460d3979143a9ba64ce62.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
const commitment863bb59b225460d3979143a9ba64ce62Form = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commitment863bb59b225460d3979143a9ba64ce62.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
commitment863bb59b225460d3979143a9ba64ce62Form.get = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commitment863bb59b225460d3979143a9ba64ce62.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/api/v1/investor/commitments/{commitment}'
*/
commitment863bb59b225460d3979143a9ba64ce62Form.head = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commitment863bb59b225460d3979143a9ba64ce62.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

commitment863bb59b225460d3979143a9ba64ce62.form = commitment863bb59b225460d3979143a9ba64ce62Form
/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
const commitment14cf5dc7eed53f902216996a710f089e = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commitment14cf5dc7eed53f902216996a710f089e.url(args, options),
    method: 'get',
})

commitment14cf5dc7eed53f902216996a710f089e.definition = {
    methods: ["get","head"],
    url: '/investor/commitments/{commitment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
commitment14cf5dc7eed53f902216996a710f089e.url = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { commitment: args }
    }

    if (Array.isArray(args)) {
        args = {
            commitment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        commitment: args.commitment,
    }

    return commitment14cf5dc7eed53f902216996a710f089e.definition.url
            .replace('{commitment}', parsedArgs.commitment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
commitment14cf5dc7eed53f902216996a710f089e.get = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: commitment14cf5dc7eed53f902216996a710f089e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
commitment14cf5dc7eed53f902216996a710f089e.head = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: commitment14cf5dc7eed53f902216996a710f089e.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
const commitment14cf5dc7eed53f902216996a710f089eForm = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commitment14cf5dc7eed53f902216996a710f089e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
commitment14cf5dc7eed53f902216996a710f089eForm.get = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commitment14cf5dc7eed53f902216996a710f089e.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::commitment
* @see app/Http/Controllers/InvestorPrimaryController.php:64
* @route '/investor/commitments/{commitment}'
*/
commitment14cf5dc7eed53f902216996a710f089eForm.head = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: commitment14cf5dc7eed53f902216996a710f089e.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

commitment14cf5dc7eed53f902216996a710f089e.form = commitment14cf5dc7eed53f902216996a710f089eForm

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorPrimaryController::commitment, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `commitment['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const commitment = {
    '/api/v1/investor/commitments/{commitment}': commitment863bb59b225460d3979143a9ba64ce62,
    '/investor/commitments/{commitment}': commitment14cf5dc7eed53f902216996a710f089e,
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/api/v1/investor/commitments/{commitment}/cancel'
*/
const cancel8607c40168c7cea24228dbfb8a158129 = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel8607c40168c7cea24228dbfb8a158129.url(args, options),
    method: 'post',
})

cancel8607c40168c7cea24228dbfb8a158129.definition = {
    methods: ["post"],
    url: '/api/v1/investor/commitments/{commitment}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/api/v1/investor/commitments/{commitment}/cancel'
*/
cancel8607c40168c7cea24228dbfb8a158129.url = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { commitment: args }
    }

    if (Array.isArray(args)) {
        args = {
            commitment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        commitment: args.commitment,
    }

    return cancel8607c40168c7cea24228dbfb8a158129.definition.url
            .replace('{commitment}', parsedArgs.commitment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/api/v1/investor/commitments/{commitment}/cancel'
*/
cancel8607c40168c7cea24228dbfb8a158129.post = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel8607c40168c7cea24228dbfb8a158129.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/api/v1/investor/commitments/{commitment}/cancel'
*/
const cancel8607c40168c7cea24228dbfb8a158129Form = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel8607c40168c7cea24228dbfb8a158129.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/api/v1/investor/commitments/{commitment}/cancel'
*/
cancel8607c40168c7cea24228dbfb8a158129Form.post = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel8607c40168c7cea24228dbfb8a158129.url(args, options),
    method: 'post',
})

cancel8607c40168c7cea24228dbfb8a158129.form = cancel8607c40168c7cea24228dbfb8a158129Form
/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/investor/commitments/{commitment}/cancel'
*/
const cancel12de6d9297648c5933065d1c735ca295 = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel12de6d9297648c5933065d1c735ca295.url(args, options),
    method: 'post',
})

cancel12de6d9297648c5933065d1c735ca295.definition = {
    methods: ["post"],
    url: '/investor/commitments/{commitment}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/investor/commitments/{commitment}/cancel'
*/
cancel12de6d9297648c5933065d1c735ca295.url = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { commitment: args }
    }

    if (Array.isArray(args)) {
        args = {
            commitment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        commitment: args.commitment,
    }

    return cancel12de6d9297648c5933065d1c735ca295.definition.url
            .replace('{commitment}', parsedArgs.commitment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/investor/commitments/{commitment}/cancel'
*/
cancel12de6d9297648c5933065d1c735ca295.post = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancel12de6d9297648c5933065d1c735ca295.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/investor/commitments/{commitment}/cancel'
*/
const cancel12de6d9297648c5933065d1c735ca295Form = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel12de6d9297648c5933065d1c735ca295.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::cancel
* @see app/Http/Controllers/InvestorPrimaryController.php:46
* @route '/investor/commitments/{commitment}/cancel'
*/
cancel12de6d9297648c5933065d1c735ca295Form.post = (args: { commitment: string | number } | [commitment: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancel12de6d9297648c5933065d1c735ca295.url(args, options),
    method: 'post',
})

cancel12de6d9297648c5933065d1c735ca295.form = cancel12de6d9297648c5933065d1c735ca295Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorPrimaryController::cancel, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `cancel['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const cancel = {
    '/api/v1/investor/commitments/{commitment}/cancel': cancel8607c40168c7cea24228dbfb8a158129,
    '/investor/commitments/{commitment}/cancel': cancel12de6d9297648c5933065d1c735ca295,
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
const operation3ff2f17357c5339b36a3556bb0d1d97f = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation3ff2f17357c5339b36a3556bb0d1d97f.url(args, options),
    method: 'get',
})

operation3ff2f17357c5339b36a3556bb0d1d97f.definition = {
    methods: ["get","head"],
    url: '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation3ff2f17357c5339b36a3556bb0d1d97f.url = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
            request_id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
        request_id: args.request_id,
    }

    return operation3ff2f17357c5339b36a3556bb0d1d97f.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation3ff2f17357c5339b36a3556bb0d1d97f.get = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation3ff2f17357c5339b36a3556bb0d1d97f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation3ff2f17357c5339b36a3556bb0d1d97f.head = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation3ff2f17357c5339b36a3556bb0d1d97f.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
const operation3ff2f17357c5339b36a3556bb0d1d97fForm = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation3ff2f17357c5339b36a3556bb0d1d97f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation3ff2f17357c5339b36a3556bb0d1d97fForm.get = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation3ff2f17357c5339b36a3556bb0d1d97f.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation3ff2f17357c5339b36a3556bb0d1d97fForm.head = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation3ff2f17357c5339b36a3556bb0d1d97f.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation3ff2f17357c5339b36a3556bb0d1d97f.form = operation3ff2f17357c5339b36a3556bb0d1d97fForm
/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
const operation697b591f09ca55c1ba911f7007f55f79 = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation697b591f09ca55c1ba911f7007f55f79.url(args, options),
    method: 'get',
})

operation697b591f09ca55c1ba911f7007f55f79.definition = {
    methods: ["get","head"],
    url: '/investor/deals/{campaign}/primary-operations/{request_id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation697b591f09ca55c1ba911f7007f55f79.url = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            campaign: args[0],
            request_id: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        campaign: args.campaign,
        request_id: args.request_id,
    }

    return operation697b591f09ca55c1ba911f7007f55f79.definition.url
            .replace('{campaign}', parsedArgs.campaign.toString())
            .replace('{request_id}', parsedArgs.request_id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation697b591f09ca55c1ba911f7007f55f79.get = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: operation697b591f09ca55c1ba911f7007f55f79.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation697b591f09ca55c1ba911f7007f55f79.head = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: operation697b591f09ca55c1ba911f7007f55f79.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
const operation697b591f09ca55c1ba911f7007f55f79Form = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation697b591f09ca55c1ba911f7007f55f79.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation697b591f09ca55c1ba911f7007f55f79Form.get = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation697b591f09ca55c1ba911f7007f55f79.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorPrimaryController::operation
* @see app/Http/Controllers/InvestorPrimaryController.php:52
* @route '/investor/deals/{campaign}/primary-operations/{request_id}'
*/
operation697b591f09ca55c1ba911f7007f55f79Form.head = (args: { campaign: string | number, request_id: string | number } | [campaign: string | number, request_id: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: operation697b591f09ca55c1ba911f7007f55f79.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

operation697b591f09ca55c1ba911f7007f55f79.form = operation697b591f09ca55c1ba911f7007f55f79Form

/**
* Multiple routes resolve to \App\Http\Controllers\InvestorPrimaryController::operation, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `operation['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const operation = {
    '/api/v1/investor/deals/{campaign}/primary-operations/{request_id}': operation3ff2f17357c5339b36a3556bb0d1d97f,
    '/investor/deals/{campaign}/primary-operations/{request_id}': operation697b591f09ca55c1ba911f7007f55f79,
}

const InvestorPrimaryController = { reserve, confirm, release, commitment, cancel, operation }

export default InvestorPrimaryController