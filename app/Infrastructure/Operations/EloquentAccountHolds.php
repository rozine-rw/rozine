<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use App\Application\Operations\Contracts\AccountHolds;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

/** The Investor account cases in force from `investor_account_restrictions`, named by the Party's account. */
final class EloquentAccountHolds implements AccountHolds
{
    public function inForce(DateTimeInterface $at): array
    {
        $holds = [];
        foreach (DB::table('investor_account_restrictions as r')
            ->leftJoin('users', 'users.party_id', '=', 'r.party_id')
            ->where('r.effective_at', '<=', $at)
            ->where(fn (Builder $open) => $open->whereNull('r.expires_at')->orWhere('r.expires_at', '>', $at))
            ->orderBy('r.effective_at')->orderBy('r.id')
            ->get(['r.id', 'r.party_id', 'users.name', 'r.cause', 'r.scope', 'r.effective_at', 'r.expires_at']) as $row) {
            $holds[] = $this->hold($row);
        }

        return $holds;
    }

    /** @return array{id: string, party_id: string, name: string|null, cause: string, scope: list<string>, effective_at: string, expires_at: string|null} */
    private function hold(stdClass $row): array
    {
        /** @var list<string> $scope */
        $scope = array_values(array_map(strval(...), (array) json_decode((string) $row->scope, true)));

        return ['id' => (string) $row->id, 'party_id' => (string) $row->party_id, 'name' => $row->name === null ? null : (string) $row->name,
            'cause' => (string) $row->cause, 'scope' => $scope, 'effective_at' => (string) $row->effective_at,
            'expires_at' => $row->expires_at === null ? null : (string) $row->expires_at];
    }
}
