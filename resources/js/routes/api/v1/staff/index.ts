import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
import disbursements from './disbursements'
import audit from './audit'
import applications from './applications'
import investors from './investors'
import staff from './staff'
import events from './events'
import investorVerifications from './investor-verifications'
/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/api/v1/staff/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StaffDashboardController::__invoke
* @see app/Http/Controllers/StaffDashboardController.php:21
* @route '/api/v1/staff/dashboard'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

const staffNamespace = {
    disbursements: Object.assign(disbursements, disbursements),
    audit: Object.assign(audit, audit),
    applications: Object.assign(applications, applications),
    investors: Object.assign(investors, investors),
    dashboard: Object.assign(dashboard, dashboard),
    staff: Object.assign(staff, staff),
    events: Object.assign(events, events),
    investorVerifications: Object.assign(investorVerifications, investorVerifications),
}

export default staffNamespace