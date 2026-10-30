<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Operations\Contracts\CanonicalJson;
use App\Application\Primary\Contracts\HeldNoteCapital;
use Brick\Math\BigInteger;
use Illuminate\Support\Facades\DB;

/**
 * Outstanding held-note principal from the immutable Holdings a Party owns.
 *
 * The original principal is the outstanding principal only while nothing can have reduced it.
 * Holdings cannot be updated, deleted or reassigned (`protect_primary_holding`), and there is no
 * transfer producer. The only way principal comes back is a Business repayment allocated by
 * servicing, so the figure is complete only while no Business repayment has been recorded at all.
 * Once servicing records one, this source reports incomplete until servicing supplies the
 * reductions, and pricing refuses with POLICY_INPUT_REQUIRED instead of using a stale figure.
 */
final readonly class RetainedHeldNoteCapital implements HeldNoteCapital
{
    public const string VERSION = 'held-note-capital-1';

    public function __construct(private CanonicalJson $json) {}

    public function current(string $partyId): array
    {
        $holdings = DB::table('primary_holdings')->where('party_id', $partyId)->orderBy('id')->get(['id', 'principal']);
        $principal = BigInteger::zero();
        $notes = [];
        foreach ($holdings as $holding) {
            $principal = $principal->plus((string) $holding->principal);
            $notes[] = ['id' => (string) $holding->id, 'principal' => (string) BigInteger::of((string) $holding->principal)];
        }
        $reduced = DB::table('business_repayments')->exists();
        $outstanding = (string) $principal;

        return ['outstanding_principal' => $outstanding, 'revision' => count($notes),
            'sha256' => hash('sha256', $this->json->encode(['version' => self::VERSION, 'party_id' => $partyId, 'notes' => $notes,
                'outstanding_principal' => $outstanding, 'complete' => ! $reduced])),
            'complete' => ! $reduced, 'as_of' => now('UTC')->format('Y-m-d\TH:i:s\Z')];
    }
}
