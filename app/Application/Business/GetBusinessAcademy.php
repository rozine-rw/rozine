<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Domain\Business\ReadinessAcademy;
use App\Domain\Operations\CommandRejection;

/**
 * The Business Academy (BRS v3.0 RDY-R07) for a current mandate holder, read under `business.view`:
 * the list of its stage's core modules and the next stage's preview, and one module's flashcards
 * with, while its test is open, the test drawn for the next attempt. Each module reads in the
 * person's language where it is written, otherwise in English.
 *
 * @phpstan-import-type Status from ReadinessAcademy
 * @phpstan-import-type Drawn from ReadinessAcademy
 * @phpstan-import-type Attempt from ReadinessAcademy
 * @phpstan-import-type Module from ReadinessAcademy
 *
 * @phpstan-type Listed array{key: string, title: string, summary: string, cards: int, status: Status|null}
 * @phpstan-type Course array{identity_context_revision: int, business_id: string, stage: int|null, level: int, pass_mark: int, questions: int,
 *     core: list<Listed>, preview: list<Listed>}
 * @phpstan-type Lesson array{identity_context_revision: int, business_id: string, key: string, role: 'core'|'preview', level: int, title: string,
 *     summary: string, cards: list<array{front: string, back: string}>, status: Status|null, attempt: int|null, test: Drawn|null, last: Attempt|null,
 *     pass_mark: int}
 */
final readonly class GetBusinessAcademy
{
    public function __construct(private WithBusinessAuthority $authority, private BusinessAcademySyllabus $syllabus) {}

    /** @return Course */
    public function course(int $userId, int $contextRevision, string $businessId): array
    {
        $id = $this->business($userId, $contextRevision, $businessId);
        $syllabus = $this->syllabus->forBusiness($id);
        $locale = app()->getLocale();

        return ['identity_context_revision' => $contextRevision, 'business_id' => $id, 'stage' => $syllabus['stage'], 'level' => $syllabus['level'],
            'pass_mark' => ReadinessAcademy::PASS_MARK, 'questions' => ReadinessAcademy::QUESTIONS,
            'core' => array_map(fn (array $entry): array => self::listed($entry['key'], $entry['module'], $entry['status'], $locale), $syllabus['core']),
            'preview' => array_map(fn (array $entry): array => self::listed($entry['key'], $entry['module'], null, $locale), $syllabus['preview'])];
    }

    /**
     * One module of the Business's syllabus; any other module is not found.
     *
     * @return Lesson
     */
    public function lesson(int $userId, int $contextRevision, string $businessId, string $key): array
    {
        $id = $this->business($userId, $contextRevision, $businessId);
        $syllabus = $this->syllabus->forBusiness($id);
        $locale = app()->getLocale();
        foreach ($syllabus['core'] as $entry) {
            if ($entry['key'] === $key) {
                $text = ReadinessAcademy::text($entry['module'], $locale);
                $attempt = count($entry['attempts']) + 1;
                $open = $entry['status']['state'] === 'open';

                return ['identity_context_revision' => $contextRevision, 'business_id' => $id, 'key' => $key, 'role' => 'core', 'level' => $syllabus['level'],
                    'title' => $text['title'], 'summary' => $text['summary'], 'cards' => self::cards($text['cards']), 'status' => $entry['status'],
                    'attempt' => $open ? $attempt : null, 'test' => $open ? ReadinessAcademy::draw($text, ReadinessAcademy::seed($id, $key, $attempt)) : null,
                    'last' => $entry['attempts'] === [] ? null : $entry['attempts'][array_key_last($entry['attempts'])], 'pass_mark' => ReadinessAcademy::PASS_MARK];
            }
        }
        foreach ($syllabus['preview'] as $entry) {
            if ($entry['key'] === $key) {
                $text = ReadinessAcademy::text($entry['module'], $locale);

                return ['identity_context_revision' => $contextRevision, 'business_id' => $id, 'key' => $key, 'role' => 'preview', 'level' => $syllabus['level'],
                    'title' => $text['title'], 'summary' => $text['summary'], 'cards' => self::cards($text['cards']), 'status' => null, 'attempt' => null,
                    'test' => null, 'last' => null, 'pass_mark' => ReadinessAcademy::PASS_MARK];
            }
        }

        throw new CommandRejection('ACADEMY_MODULE_NOT_FOUND', 404);
    }

    private function business(int $userId, int $contextRevision, string $businessId): string
    {
        return $this->authority->handle($userId, $contextRevision, $businessId, 'business.view', null, fn (array $business): string => (string) $business['id']);
    }

    /**
     * @param  Module  $module
     * @param  Status|null  $status
     * @return Listed
     */
    private static function listed(string $key, array $module, ?array $status, string $locale): array
    {
        $text = ReadinessAcademy::text($module, $locale);

        return ['key' => $key, 'title' => $text['title'], 'summary' => $text['summary'], 'cards' => count($text['cards']), 'status' => $status];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $cards
     * @return list<array{front: string, back: string}>
     */
    private static function cards(array $cards): array
    {
        return array_map(fn (array $card): array => ['front' => $card[0], 'back' => $card[1]], $cards);
    }
}
