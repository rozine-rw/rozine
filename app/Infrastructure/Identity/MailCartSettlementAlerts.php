<?php

declare(strict_types=1);

namespace App\Infrastructure\Identity;

use App\Application\Primary\Contracts\CartSettlementAlerts;
use App\Models\StaffAccount;
use App\Models\User;
use App\Notifications\Staff\CartSettlementStuck;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * E-mails every enabled staff member whose role grants `cart.releases.resolve`, so whoever is on
 * duty can retry the stuck expiry from the console's Exceptions page.
 */
final readonly class MailCartSettlementAlerts implements CartSettlementAlerts
{
    public function stuck(string $checkoutId, string $total, string $reasonCode): void
    {
        $recipients = DB::table('staff_accounts as staff')
            ->join('model_has_roles as grants', fn (JoinClause $join) => $join->on('grants.model_id', 'staff.user_id')->where('grants.model_type', StaffAccount::class))
            ->join('role_has_permissions as granted', 'granted.role_id', 'grants.role_id')
            ->join('permissions as permission', 'permission.id', 'granted.permission_id')
            ->where('permission.name', 'cart.releases.resolve')->where('permission.guard_name', 'staff')->where('staff.enabled', true)
            ->distinct()->orderBy('staff.user_id')->pluck('staff.user_id');
        $message = new CartSettlementStuck($checkoutId, $total, $reasonCode, route('staff.exceptions.index', ['kind' => 'cart_release']));
        foreach (User::query()->whereKey($recipients)->orderBy('id')->get() as $user) {
            try {
                $user->notifyNow($message);
            } catch (Throwable $failure) {
                report($failure);
            }
        }
    }
}
