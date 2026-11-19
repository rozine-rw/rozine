<?php

declare(strict_types=1);

use App\Application\Environment\UatMoneySimulation;
use App\Application\Identity\ChangeMembership;
use App\Domain\Identity\IdentityViolation;
use App\Models\IdentityOperator;
use App\Models\Party;
use App\Models\RoleMembership;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Gives staging QA a third resale buyer for QA #663 item 5 (a reservation held by one buyer while
 * another looks at the same listing). qa-kyc2@rozine.rw is already a verified person with Business
 * access; the QA identity operator (qa-admin@rozine.rw) grants it Investor access through the same
 * action an operator uses, and it is then named a wallet tester like qa-kyc3. It acts only while
 * the UAT money simulation is on, and only when both accounts exist and qa-kyc2 is verified and holds
 * no Investor membership yet; everywhere else it does nothing. Both steps are idempotent.
 */
return new class extends Migration
{
    public const string BUYER = 'qa-kyc2@rozine.rw';

    public const string OPERATOR = 'qa-admin@rozine.rw';

    public function up(): void
    {
        if (! app(UatMoneySimulation::class)->active()) {
            return;
        }
        $buyer = User::query()->where('email', self::BUYER)->whereNotNull('party_id')->first();
        $operator = User::query()->where('email', self::OPERATOR)->first();
        if ($buyer === null || $operator === null || ! IdentityOperator::query()->whereKey($operator->id)->where('enabled', true)->exists()) {
            return;
        }
        $partyId = (string) $buyer->party_id;
        if (! Party::query()->whereKey($partyId)->whereNotNull('verified_at')->exists()) {
            return;
        }
        if (! RoleMembership::query()->where('party_id', $partyId)->where('role', 'investor')->exists()) {
            try {
                app(ChangeMembership::class)->handle($operator->id, $partyId, 'investor', 'active', 0, 'qa:staging-663-item-5',
                    'Staging QA: a third resale buyer for QA #663 item 5, named in code with the UAT money simulation (19 Nov 2026).', (string) Str::uuid());
            } catch (IdentityViolation $refusal) {
                // A QA convenience never fails a staging deploy: the refusal is logged and the buyer left as it is.
                Log::warning('UAT third resale buyer not granted Investor access.', ['code' => $refusal->reason]);

                return;
            }
        }
        Artisan::call('staging:wallet', ['email' => self::BUYER,
            '--reason' => 'Staging QA third resale buyer for QA #663 item 5, named in code with the UAT money simulation (19 Nov 2026).']);
    }

    public function down(): void
    {
        // Access is changed by an identity operator and a tester revoked with `staging:wallet --revoke`, never by rolling back.
    }
};
