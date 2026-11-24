<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\Application\Business\Contracts\AcademyAttemptStore;
use App\Domain\Business\ReadinessAcademy;
use App\Domain\Operations\CommandRejection;

/**
 * `business.academy.test.submit` (BRS v3.0 RDY-R07): the Business answers the test drawn for its
 * next attempt at one of its stage's core modules, and the server scores it. A module already
 * passed, or failed less than 24 hours ago, takes no attempt; nor does an answer sheet for an
 * attempt that is no longer the next one. A preview module has no test.
 */
final readonly class SubmitAcademyTest
{
    public function __construct(private AcademyAttemptStore $attempts, private BusinessAcademySyllabus $syllabus) {}

    /**
     * @param  array{attempt: int, answers: array<array-key, mixed>}  $input
     * @return array<string, mixed>
     */
    public function handle(int $userId, int $contextRevision, string $businessId, string $module, string $requestId, array $input): array
    {
        return $this->attempts->submit($userId, $contextRevision, $businessId, $module, $requestId, $input, function (string $id) use ($module, $input): array {
            $entries = array_values(array_filter($this->syllabus->forBusiness($id)['core'], fn (array $entry): bool => $entry['key'] === $module));
            $entry = $entries[0] ?? throw new CommandRejection('ACADEMY_MODULE_NOT_FOUND', 404);
            $state = $entry['status']['state'];
            if ($state !== 'open') {
                throw new CommandRejection($state === 'passed' ? 'ACADEMY_ALREADY_PASSED' : 'ACADEMY_RETAKE_WAITING', 409);
            }
            $attempt = count($entry['attempts']) + 1;
            if ($input['attempt'] !== $attempt) {
                throw new CommandRejection('ACADEMY_ATTEMPT_STALE', 409);
            }
            $text = ReadinessAcademy::text($entry['module'], 'en');
            $seed = ReadinessAcademy::seed($id, $module, $attempt);
            $score = ReadinessAcademy::score($text, $seed, $input['answers']);
            $drawn = ReadinessAcademy::draw($text, $seed);
            $answers = [];
            foreach ($drawn as $question) {
                $answers[$question['id']] = (int) $input['answers'][$question['id']];
            }

            return ['attempt' => $attempt, 'questions' => array_column($drawn, 'id'), 'answers' => $answers, 'score' => $score,
                'passed' => $score >= ReadinessAcademy::PASS_MARK];
        });
    }
}
