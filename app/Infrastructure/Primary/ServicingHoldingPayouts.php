<?php

declare(strict_types=1);

namespace App\Infrastructure\Primary;

use App\Application\Primary\Contracts\HoldingPayouts;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The servicing payouts recorded against issued Holdings' own lots (`servicing_payouts`, lot type
 * `primary_holding`), and against lots bought on the secondary market (`secondary_transfer`), the
 * buyer's transfer rather than a Holding.
 *
 * @phpstan-import-type HoldingPayout from HoldingPayouts
 */
final readonly class ServicingHoldingPayouts implements HoldingPayouts
{
    public function forHoldings(array $holdingIds): array
    {
        return $this->forLots('primary_holding', $holdingIds);
    }

    public function forLots(string $lotType, array $lotIds): array
    {
        if ($lotIds === []) {
            return [];
        }
        $paid = [];
        $rows = DB::table('servicing_payouts')->where('lot_type', $lotType)->whereIn('lot_id', $lotIds)
            ->orderBy('created_at')->orderBy('id')->get(['lot_id', 'instalment_indexes', 'principal', 'contractual_return', 'net', 'created_at']);
        foreach ($rows as $row) {
            /** @var list<int> $instalments */
            $instalments = array_map(intval(...), json_decode((string) $row->instalment_indexes, true, flags: JSON_THROW_ON_ERROR));
            $paid[(string) $row->lot_id][] = ['recorded_at' => CarbonImmutable::parse((string) $row->created_at)->utc()->toIso8601String(),
                'instalments' => $instalments, 'principal' => (string) $row->principal, 'return' => (string) $row->contractual_return, 'net' => (string) $row->net];
        }

        return $paid;
    }
}
