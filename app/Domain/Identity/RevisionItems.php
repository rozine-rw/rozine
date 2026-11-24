<?php

declare(strict_types=1);

namespace App\Domain\Identity;

use App\Domain\Operations\CommandRejection;

/**
 * The answers and documents Compliance names when it sends a KYC or a role application back (Erastus,
 * 24 Nov 2026): each one the reviewer ticked, with an optional word on what to fix. Only items the case
 * actually has may be named, each once, so the applicant is never shown a field they cannot find.
 */
final class RevisionItems
{
    /** Items one request may name, at most. */
    public const int MAX_ITEMS = 40;

    /** Characters a note on one item may hold. */
    public const int MAX_NOTE = 300;

    /**
     * @param  mixed  $input  the reviewer's list of `{key, note?}`
     * @param  array<string, string>  $allowed  the case's revisable items, key to label
     * @return list<array{key: string, label: string, note: string}>
     */
    public static function pick(mixed $input, array $allowed): array
    {
        if ($input === null || $input === []) {
            return [];
        }
        if (! is_array($input) || ! array_is_list($input) || count($input) > self::MAX_ITEMS) {
            throw self::invalid();
        }
        $items = [];
        foreach ($input as $item) {
            $key = is_array($item) ? ($item['key'] ?? null) : null;
            $note = is_array($item) ? ($item['note'] ?? '') : null;
            if (! is_string($key) || ! isset($allowed[$key]) || ! is_string($note) || isset($items[$key])) {
                throw self::invalid();
            }
            $note = trim($note);
            if (mb_strlen($note) > self::MAX_NOTE || preg_match('/[\p{Cc}\p{Cf}]/u', str_replace(["\n", "\r"], ' ', $note)) === 1) {
                throw new CommandRejection('REVISION_NOTE_INVALID', 422, fieldErrors: ['items' => ['Keep each note to at most '.self::MAX_NOTE.' characters.']]);
            }
            $items[$key] = ['key' => $key, 'label' => $allowed[$key], 'note' => $note];
        }

        return array_values($items);
    }

    /**
     * The words a request with no note of its own is recorded and sent with.
     *
     * @param  list<array{key: string, label: string, note: string}>  $items
     */
    public static function summary(array $items): string
    {
        return 'Please change: '.implode(', ', array_column($items, 'label')).'.';
    }

    private static function invalid(): CommandRejection
    {
        return new CommandRejection('REVISION_ITEM_INVALID', 422, fieldErrors: ['items' => ['Choose the items to change from this application.']]);
    }
}
