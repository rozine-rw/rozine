<?php

declare(strict_types=1);

namespace App\Infrastructure\Secondary;

use App\Application\Secondary\Contracts\NoteUnitHolders;
use App\Application\Secondary\Contracts\SecondaryNotes;
use App\Domain\Secondary\HoldingLot;
use App\Models\SecondaryTransfer;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Builds each lot from its authenticated issue or its settled transfer, then takes away every later
 * sale from it in settlement order. A recorded sale that did not move the lot's lowest ordinals fails
 * closed, as does a lot read outside its note.
 *
 * @phpstan-import-type Lot from SecondaryNotes
 */
final readonly class EloquentNoteUnitHolders implements NoteUnitHolders
{
    public function __construct(private SecondaryNotes $notes) {}

    public function lots(string $noteId): array
    {
        $note = $this->notes->find($noteId);
        if ($note === null) {
            return [];
        }
        $transfers = SecondaryTransfer::query()->where('business_campaign_id', $noteId)->orderBy('settled_at')->orderBy('id')->get();
        $lots = [...$note['issued_lots'], ...$transfers->map(fn (SecondaryTransfer $transfer): array => self::bought($transfer))->all()];

        return array_values(array_filter(array_map(fn (array $lot): ?array => self::current($lot,
            $transfers->filter(fn (SecondaryTransfer $transfer): bool => $transfer->source_lot_type === $lot['lot_type'] && $transfer->source_lot_id === $lot['lot_id'])),
            $lots)));
    }

    public function lot(string $noteId, string $lotType, string $lotId): ?array
    {
        $lotId = strtolower($lotId);
        if ($lotType === 'primary_holding') {
            $issued = array_values(array_filter($this->notes->find($noteId)['issued_lots'] ?? [], fn (array $lot): bool => $lot['lot_id'] === $lotId));
            $lot = $issued[0] ?? null;
        } elseif ($lotType === 'secondary_transfer') {
            $transfer = SecondaryTransfer::query()->whereKey($lotId)->where('business_campaign_id', $noteId)->first();
            $lot = $transfer === null ? null : self::bought($transfer);
        } else {
            $lot = null;
        }

        return $lot === null ? null : self::current($lot, SecondaryTransfer::query()->where('source_lot_type', $lotType)->where('source_lot_id', $lotId)
            ->orderBy('settled_at')->orderBy('id')->get());
    }

    /** @return Lot */
    private static function bought(SecondaryTransfer $transfer): array
    {
        return ['lot_type' => 'secondary_transfer', 'lot_id' => $transfer->id, 'party_id' => $transfer->buyer_party_id, 'units' => (string) $transfer->units,
            'ordinals' => self::ranges($transfer->ordinals)];
    }

    /**
     * Ordinal ranges as plain strings in `first`, `last` order, however JSON stored them.
     *
     * @param  list<array{first: int|string, last: int|string}>  $ranges
     * @return list<array{first: string, last: string}>
     */
    public static function ranges(array $ranges): array
    {
        return array_map(fn (array $range): array => ['first' => (string) $range['first'], 'last' => (string) $range['last']], $ranges);
    }

    /**
     * @param  Lot  $lot
     * @param  Collection<int, SecondaryTransfer>  $sales
     * @return Lot|null
     */
    private static function current(array $lot, Collection $sales): ?array
    {
        $holding = HoldingLot::of($lot['ordinals']);
        foreach ($sales as $sale) {
            ['moved' => $moved, 'kept' => $holding] = ($holding ?? throw new RuntimeException('SECONDARY_LOT_INTEGRITY_FAILED'))->split((string) $sale->units);
            (self::ranges($moved->ranges) === self::ranges($sale->ordinals) && $sale->seller_party_id === $lot['party_id'])
                || throw new RuntimeException('SECONDARY_LOT_INTEGRITY_FAILED');
        }

        return $holding === null ? null : [...$lot, 'units' => (string) $holding->units, 'ordinals' => $holding->ranges];
    }
}
