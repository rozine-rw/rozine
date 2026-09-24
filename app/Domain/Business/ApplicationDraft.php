<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Operations\CommandRejection;

/** @phpstan-type Fields array{title: string, target: string|null, term_months: int|null, use_of_funds: list<string>, story: string} */
final class ApplicationDraft
{
    /** @return Fields */
    public function empty(): array
    {
        return ['title' => '', 'target' => null, 'term_months' => null, 'use_of_funds' => [], 'story' => ''];
    }

    /**
     * Drafts may be incomplete. Evaluation and signing apply their stronger
     * preconditions separately and never silently change the original request.
     *
     * @param  Fields  $fields
     * @return Fields
     */
    public function normalize(array $fields): array
    {
        if (array_diff(array_keys($fields), ['title', 'target', 'term_months', 'use_of_funds', 'story']) !== []) {
            throw new CommandRejection('APPLICATION_INPUT_INVALID', 422);
        }
        $errors = [];
        if (mb_strlen($fields['title']) > 180 || ! mb_check_encoding($fields['title'], 'UTF-8')) {
            $errors['title'] = ['Use a title of at most 180 characters.'];
        }
        if ($fields['target'] !== null && ! preg_match('/^(0|[1-9][0-9]{0,17})$/D', $fields['target'])) {
            $errors['target'] = ['Enter a whole RWF amount using decimal digits.'];
        }
        if ($fields['term_months'] !== null && ! in_array($fields['term_months'], [3, 4, 5, 6], true)) {
            $errors['term_months'] = ['Select a term of 3, 4, 5 or 6 months.'];
        }
        if (count($fields['use_of_funds']) > 6 || array_diff($fields['use_of_funds'], ['inventory', 'expansion', 'equipment', 'hiring', 'working_capital', 'other']) !== []
            || count(array_unique($fields['use_of_funds'])) !== count($fields['use_of_funds'])) {
            $errors['use_of_funds'] = ['Select each supported use of funds at most once.'];
        }
        if (mb_strlen($fields['story']) > 10000 || ! mb_check_encoding($fields['story'], 'UTF-8')) {
            $errors['story'] = ['Use at most 10,000 characters.'];
        }
        if ($errors !== []) {
            throw new CommandRejection('APPLICATION_INPUT_INVALID', 422, fieldErrors: $errors);
        }

        return ['title' => trim($fields['title']), 'target' => $fields['target'], 'term_months' => $fields['term_months'],
            'use_of_funds' => $fields['use_of_funds'], 'story' => trim($fields['story'])];
    }

    public function assertEditable(string $status, int $revision, int $expectedRevision): void
    {
        if ($revision !== $expectedRevision) {
            throw new CommandRejection('VERSION_CONFLICT', 409, $revision);
        }
        if ($status !== 'draft') {
            throw new CommandRejection('APPLICATION_NOT_EDITABLE', 409, $revision);
        }
    }
}
