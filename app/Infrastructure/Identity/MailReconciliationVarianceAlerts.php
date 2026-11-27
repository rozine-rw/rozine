<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Operations\Contracts\ReconciliationVarianceAlerts;
use App\Events\ReconciliationVarianceOpened;
use App\Models\StaffAccount;
use App\Models\User;
use App\Notifications\Staff\ReconciliationVarianceDetected;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Existing enabled staff permission grants select recipients; this creates no CMA account or grant. */
final readonly class MailReconciliationVarianceAlerts implements ReconciliationVarianceAlerts
{
    public function opened(array $variance): void
    {
        event(new ReconciliationVarianceOpened($variance));
        $recipients = DB::table('staff_accounts as staff')
            ->join('model_has_roles as grants', fn (JoinClause $join) => $join->on('grants.model_id', 'staff.user_id')->where('grants.model_type', StaffAccount::class))
            ->join('role_has_permissions as granted', 'granted.role_id', 'grants.role_id')
            ->join('permissions as permission', 'permission.id', 'granted.permission_id')
            ->where('permission.name', 'reconciliation.view')->where('permission.guard_name', 'staff')->where('staff.enabled', true)
            ->distinct()->orderBy('staff.user_id')->pluck('staff.user_id');
        $message = new ReconciliationVarianceDetected($variance, route('staff.reconciliation.index'));
        foreach (User::query()->whereKey($recipients)->orderBy('id')->get() as $user) {
            try {
                $user->notifyNow($message);
            } catch (Throwable $failure) {
                report($failure);
            }
        }
    }
}
