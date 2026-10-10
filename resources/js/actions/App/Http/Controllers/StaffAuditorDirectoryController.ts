import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
const index757d8241174f641d6ddc2ca1e6de29ee = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index757d8241174f641d6ddc2ca1e6de29ee.url(options),
    method: 'get',
})

index757d8241174f641d6ddc2ca1e6de29ee.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/auditors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
index757d8241174f641d6ddc2ca1e6de29ee.url = (options?: RouteQueryOptions) => {
    return index757d8241174f641d6ddc2ca1e6de29ee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
index757d8241174f641d6ddc2ca1e6de29ee.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index757d8241174f641d6ddc2ca1e6de29ee.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
index757d8241174f641d6ddc2ca1e6de29ee.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index757d8241174f641d6ddc2ca1e6de29ee.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
const index757d8241174f641d6ddc2ca1e6de29eeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index757d8241174f641d6ddc2ca1e6de29ee.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
index757d8241174f641d6ddc2ca1e6de29eeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index757d8241174f641d6ddc2ca1e6de29ee.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/api/v1/staff/auditors'
*/
index757d8241174f641d6ddc2ca1e6de29eeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index757d8241174f641d6ddc2ca1e6de29ee.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index757d8241174f641d6ddc2ca1e6de29ee.form = index757d8241174f641d6ddc2ca1e6de29eeForm
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
const indexebf087c0053e1074a0029d7a419c2f62 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexebf087c0053e1074a0029d7a419c2f62.url(options),
    method: 'get',
})

indexebf087c0053e1074a0029d7a419c2f62.definition = {
    methods: ["get","head"],
    url: '/admin/auditors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
indexebf087c0053e1074a0029d7a419c2f62.url = (options?: RouteQueryOptions) => {
    return indexebf087c0053e1074a0029d7a419c2f62.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
indexebf087c0053e1074a0029d7a419c2f62.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexebf087c0053e1074a0029d7a419c2f62.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
indexebf087c0053e1074a0029d7a419c2f62.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexebf087c0053e1074a0029d7a419c2f62.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
const indexebf087c0053e1074a0029d7a419c2f62Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexebf087c0053e1074a0029d7a419c2f62.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
indexebf087c0053e1074a0029d7a419c2f62Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexebf087c0053e1074a0029d7a419c2f62.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::index
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:42
* @route '/admin/auditors'
*/
indexebf087c0053e1074a0029d7a419c2f62Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: indexebf087c0053e1074a0029d7a419c2f62.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

indexebf087c0053e1074a0029d7a419c2f62.form = indexebf087c0053e1074a0029d7a419c2f62Form

/**
* Multiple routes resolve to \App\Http\Controllers\StaffAuditorDirectoryController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/api/v1/staff/auditors': index757d8241174f641d6ddc2ca1e6de29ee,
    '/admin/auditors': indexebf087c0053e1074a0029d7a419c2f62,
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/approve'
*/
const decideb0ace22935edb94b050be0f480b7c60f = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decideb0ace22935edb94b050be0f480b7c60f.url(args, options),
    method: 'post',
})

decideb0ace22935edb94b050be0f480b7c60f.definition = {
    methods: ["post"],
    url: '/api/v1/staff/auditors/{party}/licence/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/approve'
*/
decideb0ace22935edb94b050be0f480b7c60f.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: args.party,
    }

    return decideb0ace22935edb94b050be0f480b7c60f.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/approve'
*/
decideb0ace22935edb94b050be0f480b7c60f.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decideb0ace22935edb94b050be0f480b7c60f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/approve'
*/
const decideb0ace22935edb94b050be0f480b7c60fForm = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decideb0ace22935edb94b050be0f480b7c60f.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/approve'
*/
decideb0ace22935edb94b050be0f480b7c60fForm.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decideb0ace22935edb94b050be0f480b7c60f.url(args, options),
    method: 'post',
})

decideb0ace22935edb94b050be0f480b7c60f.form = decideb0ace22935edb94b050be0f480b7c60fForm
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/reject'
*/
const decide146bb3aac4ed95f60b4912e52bd7a892 = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decide146bb3aac4ed95f60b4912e52bd7a892.url(args, options),
    method: 'post',
})

decide146bb3aac4ed95f60b4912e52bd7a892.definition = {
    methods: ["post"],
    url: '/api/v1/staff/auditors/{party}/licence/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/reject'
*/
decide146bb3aac4ed95f60b4912e52bd7a892.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: args.party,
    }

    return decide146bb3aac4ed95f60b4912e52bd7a892.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/reject'
*/
decide146bb3aac4ed95f60b4912e52bd7a892.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decide146bb3aac4ed95f60b4912e52bd7a892.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/reject'
*/
const decide146bb3aac4ed95f60b4912e52bd7a892Form = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decide146bb3aac4ed95f60b4912e52bd7a892.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/api/v1/staff/auditors/{party}/licence/reject'
*/
decide146bb3aac4ed95f60b4912e52bd7a892Form.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decide146bb3aac4ed95f60b4912e52bd7a892.url(args, options),
    method: 'post',
})

decide146bb3aac4ed95f60b4912e52bd7a892.form = decide146bb3aac4ed95f60b4912e52bd7a892Form
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
const decideb6a30e00e0c578ede3b3623e9f641973 = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decideb6a30e00e0c578ede3b3623e9f641973.url(args, options),
    method: 'post',
})

decideb6a30e00e0c578ede3b3623e9f641973.definition = {
    methods: ["post"],
    url: '/admin/auditors/{party}/licence/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
decideb6a30e00e0c578ede3b3623e9f641973.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: args.party,
    }

    return decideb6a30e00e0c578ede3b3623e9f641973.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
decideb6a30e00e0c578ede3b3623e9f641973.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decideb6a30e00e0c578ede3b3623e9f641973.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
const decideb6a30e00e0c578ede3b3623e9f641973Form = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decideb6a30e00e0c578ede3b3623e9f641973.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/approve'
*/
decideb6a30e00e0c578ede3b3623e9f641973Form.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decideb6a30e00e0c578ede3b3623e9f641973.url(args, options),
    method: 'post',
})

decideb6a30e00e0c578ede3b3623e9f641973.form = decideb6a30e00e0c578ede3b3623e9f641973Form
/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
const decide1ff94fac3be191d026e9e50a2441058e = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decide1ff94fac3be191d026e9e50a2441058e.url(args, options),
    method: 'post',
})

decide1ff94fac3be191d026e9e50a2441058e.definition = {
    methods: ["post"],
    url: '/admin/auditors/{party}/licence/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
decide1ff94fac3be191d026e9e50a2441058e.url = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: args.party,
    }

    return decide1ff94fac3be191d026e9e50a2441058e.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
decide1ff94fac3be191d026e9e50a2441058e.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: decide1ff94fac3be191d026e9e50a2441058e.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
const decide1ff94fac3be191d026e9e50a2441058eForm = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decide1ff94fac3be191d026e9e50a2441058e.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StaffAuditorDirectoryController::decide
* @see app/Http/Controllers/StaffAuditorDirectoryController.php:62
* @route '/admin/auditors/{party}/licence/reject'
*/
decide1ff94fac3be191d026e9e50a2441058eForm.post = (args: { party: string | number } | [party: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: decide1ff94fac3be191d026e9e50a2441058e.url(args, options),
    method: 'post',
})

decide1ff94fac3be191d026e9e50a2441058e.form = decide1ff94fac3be191d026e9e50a2441058eForm

/**
* Multiple routes resolve to \App\Http\Controllers\StaffAuditorDirectoryController::decide, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `decide['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const decide = {
    '/api/v1/staff/auditors/{party}/licence/approve': decideb0ace22935edb94b050be0f480b7c60f,
    '/api/v1/staff/auditors/{party}/licence/reject': decide146bb3aac4ed95f60b4912e52bd7a892,
    '/admin/auditors/{party}/licence/approve': decideb6a30e00e0c578ede3b3623e9f641973,
    '/admin/auditors/{party}/licence/reject': decide1ff94fac3be191d026e9e50a2441058e,
}

const StaffAuditorDirectoryController = { index, decide }

export default StaffAuditorDirectoryController