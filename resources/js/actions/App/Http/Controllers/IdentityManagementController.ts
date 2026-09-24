import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\IdentityManagementController::resolvePerson
* @see app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
export const resolvePerson = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolvePerson.url(options),
    method: 'post',
})

resolvePerson.definition = {
    methods: ["post"],
    url: '/identity/people/resolve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::resolvePerson
* @see app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
resolvePerson.url = (options?: RouteQueryOptions) => {
    return resolvePerson.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::resolvePerson
* @see app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
resolvePerson.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resolvePerson.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::resolvePerson
* @see app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
const resolvePersonForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolvePerson.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::resolvePerson
* @see app/Http/Controllers/IdentityManagementController.php:20
* @route '/identity/people/resolve'
*/
resolvePersonForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resolvePerson.url(options),
    method: 'post',
})

resolvePerson.form = resolvePersonForm

/**
* @see \App\Http\Controllers\IdentityManagementController::membership
* @see app/Http/Controllers/IdentityManagementController.php:29
* @route '/identity/memberships'
*/
export const membership = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: membership.url(options),
    method: 'post',
})

membership.definition = {
    methods: ["post"],
    url: '/identity/memberships',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::membership
* @see app/Http/Controllers/IdentityManagementController.php:29
* @route '/identity/memberships'
*/
membership.url = (options?: RouteQueryOptions) => {
    return membership.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::membership
* @see app/Http/Controllers/IdentityManagementController.php:29
* @route '/identity/memberships'
*/
membership.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: membership.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::membership
* @see app/Http/Controllers/IdentityManagementController.php:29
* @route '/identity/memberships'
*/
const membershipForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: membership.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::membership
* @see app/Http/Controllers/IdentityManagementController.php:29
* @route '/identity/memberships'
*/
membershipForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: membership.url(options),
    method: 'post',
})

membership.form = membershipForm

/**
* @see \App\Http\Controllers\IdentityManagementController::selectRole
* @see app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
export const selectRole = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: selectRole.url(options),
    method: 'post',
})

selectRole.definition = {
    methods: ["post"],
    url: '/identity/active-role',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::selectRole
* @see app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
selectRole.url = (options?: RouteQueryOptions) => {
    return selectRole.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::selectRole
* @see app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
selectRole.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: selectRole.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::selectRole
* @see app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
const selectRoleForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: selectRole.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::selectRole
* @see app/Http/Controllers/IdentityManagementController.php:39
* @route '/identity/active-role'
*/
selectRoleForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: selectRole.url(options),
    method: 'post',
})

selectRole.form = selectRoleForm

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
export const role = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: role.url(args, options),
    method: 'get',
})

role.definition = {
    methods: ["get","head"],
    url: '/identity/roles/{role}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
role.url = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { role: args }
    }

    if (Array.isArray(args)) {
        args = {
            role: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        role: args.role,
    }

    return role.definition.url
            .replace('{role}', parsedArgs.role.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
role.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: role.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
role.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: role.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
const roleForm = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: role.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
roleForm.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: role.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\IdentityManagementController::role
* @see app/Http/Controllers/IdentityManagementController.php:47
* @route '/identity/roles/{role}'
*/
roleForm.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: role.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

role.form = roleForm

const IdentityManagementController = { resolvePerson, membership, selectRole, role }

export default IdentityManagementController