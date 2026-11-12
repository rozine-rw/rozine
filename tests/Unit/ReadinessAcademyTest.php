<?php

declare(strict_types=1);

use App\Domain\Business\ReadinessAcademy;
use App\Domain\Operations\CommandRejection;

/*
 * BRS v3.0 RDY-R07: each module is 8 to 12 flashcards and a 10-question test drawn afresh for each
 * attempt; 8 of 10 passes and a failed test reopens 24 hours later.
 */

/** @return array{title: string, summary: string, cards: list<array{0: string, 1: string}>, questions: list<array{0: string, 1: list<string>, 2: int}>} */
function academyText(): array
{
    $questions = [];
    for ($index = 0; $index < 12; $index++) {
        $questions[] = ["Question {$index}", ["A{$index}", "B{$index}", "C{$index}"], $index % 3];
    }

    return ['title' => 'Module', 'summary' => 'Summary', 'cards' => [['Front', 'Back']], 'questions' => $questions];
}

/**
 * Answers to the draw for `$seed`, right for the first `$right` questions and wrong after.
 *
 * @return array<int, int>
 */
function academyAnswers(string $seed, int $right): array
{
    $text = academyText();
    $answers = [];
    foreach (ReadinessAcademy::draw($text, $seed) as $place => $question) {
        $key = $text['questions'][$question['id']][2];
        $answers[$question['id']] = $place < $right ? $key : ($key + 1) % 3;
    }

    return $answers;
}

it('holds a business with no stage, or below Foundation, to the Foundation syllabus', function (): void {
    expect(ReadinessAcademy::level(null))->toBe(1)
        ->and(ReadinessAcademy::level(0))->toBe(1)
        ->and(ReadinessAcademy::level(3))->toBe(3);
});

it('reads a module in the language written, or in English', function (): void {
    $english = academyText();
    $kinyarwanda = [...$english, 'title' => 'Isomo'];

    expect(ReadinessAcademy::text(['stage' => 1, 'en' => $english], 'rw')['title'])->toBe('Module')
        ->and(ReadinessAcademy::text(['stage' => 1, 'en' => $english, 'rw' => $kinyarwanda], 'rw')['title'])->toBe('Isomo')
        ->and(ReadinessAcademy::text(['stage' => 1, 'en' => $english, 'rw' => $kinyarwanda], 'fr')['title'])->toBe('Module')
        ->and(ReadinessAcademy::text(['stage' => 1, 'en' => $english, 'rw' => $kinyarwanda], 'en')['title'])->toBe('Module');
});

it('draws 10 of the bank in an order its seed decides, with no answer key', function (): void {
    $drawn = ReadinessAcademy::draw(academyText(), 'business|bookkeeping|1');

    expect($drawn)->toHaveCount(10)
        ->and(ReadinessAcademy::draw(academyText(), 'business|bookkeeping|1'))->toBe($drawn)
        ->and(ReadinessAcademy::draw(academyText(), 'business|bookkeeping|2'))->not->toBe($drawn)
        ->and(array_unique(array_column($drawn, 'id')))->toHaveCount(10)
        ->and(array_keys($drawn[0]))->toBe(['id', 'question', 'options']);
    foreach ($drawn as $question) {
        $ids = array_column($question['options'], 'id');
        sort($ids);
        expect($ids)->toBe([0, 1, 2])
            ->and($question['question'])->toBe("Question {$question['id']}");
    }
    expect(ReadinessAcademy::seed('business', 'bookkeeping', 3))->toBe('business|bookkeeping|3');
});

it('scores the drawn questions and passes at 8 of 10', function (): void {
    $seed = 'business|bookkeeping|1';

    expect(ReadinessAcademy::score(academyText(), $seed, academyAnswers($seed, 10)))->toBe(10)
        ->and(ReadinessAcademy::score(academyText(), $seed, academyAnswers($seed, 8)))->toBe(8)
        ->and(ReadinessAcademy::score(academyText(), $seed, array_map('strval', academyAnswers($seed, 7))))->toBe(7)
        ->and(ReadinessAcademy::PASS_MARK)->toBe(8);
});

it('refuses an answer sheet with a question unanswered or answered with no such option', function (): void {
    $seed = 'business|bookkeeping|1';
    $answers = academyAnswers($seed, 10);
    [$first, $second] = array_keys($answers);
    unset($answers[$first]);
    $answers[$second] = 7;

    try {
        ReadinessAcademy::score(academyText(), $seed, $answers);
        throw new RuntimeException('The answers were scored.');
    } catch (CommandRejection $rejection) {
        expect($rejection->reason)->toBe('VALIDATION_FAILED')
            ->and($rejection->fieldErrors)->toBe(["answers.{$first}" => ['Choose one answer.'], "answers.{$second}" => ['Choose one answer.']]);
    }
});

it('opens a test, holds a failed one for 24 hours, and stays passed once passed', function (): void {
    $now = new DateTimeImmutable('2026-11-10T12:00:00Z');
    $failed = ['module' => 'bookkeeping', 'attempt' => 1, 'score' => 6, 'passed' => false, 'submitted_at' => '2026-11-10T08:00:00Z'];

    expect(ReadinessAcademy::status([], $now))->toBe(['state' => 'open', 'attempts' => 0, 'best' => null, 'retake_at' => null])
        ->and(ReadinessAcademy::status([$failed], $now))->toBe(['state' => 'waiting', 'attempts' => 1, 'best' => 6, 'retake_at' => '2026-11-11T08:00:00Z'])
        ->and(ReadinessAcademy::status([$failed], new DateTimeImmutable('2026-11-11T08:00:00Z')))
        ->toBe(['state' => 'open', 'attempts' => 1, 'best' => 6, 'retake_at' => null])
        ->and(ReadinessAcademy::status([$failed, [...$failed, 'attempt' => 2, 'score' => 9, 'passed' => true, 'submitted_at' => '2026-11-10T11:00:00Z']], $now))
        ->toBe(['state' => 'passed', 'attempts' => 2, 'best' => 9, 'retake_at' => null]);
});

it('ships a core syllabus for every stage, each module within RDY-R07', function (): void {
    /** @var array<string, array{stage: int, en: array{title: string, summary: string, cards: list<array{0: string, 1: string}>, questions: list<array{0: string, 1: list<string>, 2: int}>}}> $modules */
    $modules = require dirname(__DIR__, 2).'/resources/academy/business.php';

    expect(array_values(array_unique(array_column($modules, 'stage'))))->toBe([1, 2, 3, 4]);
    foreach ($modules as $key => $module) {
        expect($key)->toMatch('/^[a-z_]{1,40}$/')
            ->and(count($module['en']['cards']))->toBeGreaterThanOrEqual(8)->toBeLessThanOrEqual(12)
            ->and(count($module['en']['questions']))->toBeGreaterThanOrEqual(ReadinessAcademy::QUESTIONS + 2);
        foreach ($module['en']['questions'] as [$question, $options, $answer]) {
            expect($options)->toHaveCount(3)->and($answer)->toBeIn([0, 1, 2])->and($question)->not->toBe('');
        }
    }
});
