<?php

declare(strict_types=1);

namespace App\Domain\Business;

use App\Domain\Operations\CommandRejection;
use DateTimeImmutable;

/**
 * The Business Academy (BRS v3.0 RDY-R07): each module is 8 to 12 flashcards, then a 10-question
 * test drawn from the module's bank. 8 of 10 passes; a failed test can be retaken 24 hours later
 * with the questions drawn and ordered again. Questions appear only here.
 *
 * A Business studies the core modules of its stage (Foundation's while it has no stage yet or sits
 * below it); the next stage's core opens as a preview, cards only. The syllabus is the same for
 * every business at the same stage. Passing every core module of its stage is one of the three
 * conditions of Ready (RDY-R05).
 *
 * A draw is decided by its seed (the business, the module and the attempt's number), so the server
 * scores exactly the questions it showed without storing them before the answers arrive.
 *
 * @phpstan-type Text array{title: string, summary: string, cards: list<array{0: string, 1: string}>, questions: list<array{0: string, 1: list<string>, 2: int}>}
 * @phpstan-type Module array{stage: int, en: Text, rw?: Text, fr?: Text}
 * @phpstan-type Drawn list<array{id: int, question: string, options: list<array{id: int, text: string}>}>
 * @phpstan-type Attempt array{module: string, attempt: int, score: int, passed: bool, submitted_at: string}
 * @phpstan-type Status array{state: 'passed'|'open'|'waiting', attempts: int, best: int|null, retake_at: string|null}
 */
final class ReadinessAcademy
{
    public const string POLICY_VERSION = 'brs-3.0-rdy-r07';

    /** Questions in one test, and correct answers needed to pass (80%). */
    public const int QUESTIONS = 10;

    public const int PASS_MARK = 8;

    public const int RETAKE_HOURS = 24;

    /**
     * The stage whose core a Business studies: Foundation's with no stage yet or below Foundation.
     */
    public static function level(?int $stage): int
    {
        return $stage === null || $stage < 1 ? 1 : $stage;
    }

    /**
     * The module's text in `$locale`, or in English where that language is not written yet.
     *
     * @param  Module  $module
     * @return Text
     */
    public static function text(array $module, string $locale): array
    {
        return $locale === 'rw' || $locale === 'fr' ? ($module[$locale] ?? $module['en']) : $module['en'];
    }

    /**
     * The test for one attempt: `QUESTIONS` of the bank and each question's options, in an order
     * the seed decides. Ids are places in the bank and in the question's options, so the answer
     * key never leaves the server.
     *
     * @param  Text  $text
     * @return Drawn
     */
    public static function draw(array $text, string $seed): array
    {
        $drawn = [];
        foreach (array_slice(self::shuffled(range(0, count($text['questions']) - 1), $seed.'|q'), 0, self::QUESTIONS) as $id) {
            [$question, $options] = $text['questions'][$id];
            $drawn[] = ['id' => $id, 'question' => $question, 'options' => array_map(fn (int $option): array => ['id' => $option, 'text' => $options[$option]],
                self::shuffled(range(0, count($options) - 1), $seed.'|q'.$id))];
        }

        return $drawn;
    }

    /**
     * Correct answers among the drawn questions. Every drawn question must be answered with one of
     * its options; anything else is refused with the questions left unanswered.
     *
     * @param  Text  $text
     * @param  array<array-key, mixed>  $answers  question id => option id
     */
    public static function score(array $text, string $seed, array $answers): int
    {
        $correct = 0;
        $errors = [];
        foreach (self::draw($text, $seed) as $question) {
            $answer = $answers[$question['id']] ?? null;
            $chosen = is_int($answer) || (is_string($answer) && ctype_digit($answer)) ? (int) $answer : null;
            if ($chosen === null || ! in_array($chosen, array_column($question['options'], 'id'), true)) {
                $errors['answers.'.$question['id']] = ['Choose one answer.'];

                continue;
            }
            $correct += $chosen === $text['questions'][$question['id']][2] ? 1 : 0;
        }
        if ($errors !== []) {
            throw new CommandRejection('VALIDATION_FAILED', 422, fieldErrors: $errors);
        }

        return $correct;
    }

    /** The seed of one attempt's draw. */
    public static function seed(string $businessId, string $module, int $attempt): string
    {
        return $businessId.'|'.$module.'|'.$attempt;
    }

    /**
     * Where a Business stands on one module, from its attempts: passed once any attempt passed;
     * otherwise open, or waiting until 24 hours after the latest failed attempt.
     *
     * @param  list<Attempt>  $attempts  this module's attempts, any order
     * @return Status
     */
    public static function status(array $attempts, DateTimeImmutable $now): array
    {
        if ($attempts === []) {
            return ['state' => 'open', 'attempts' => 0, 'best' => null, 'retake_at' => null];
        }
        $best = max(array_column($attempts, 'score'));
        $latest = max(array_column($attempts, 'submitted_at'));
        $retake = (new DateTimeImmutable($latest))->modify('+'.self::RETAKE_HOURS.' hours');
        if (in_array(true, array_column($attempts, 'passed'), true)) {
            return ['state' => 'passed', 'attempts' => count($attempts), 'best' => $best, 'retake_at' => null];
        }

        return ['state' => $now >= $retake ? 'open' : 'waiting', 'attempts' => count($attempts), 'best' => $best,
            'retake_at' => $now >= $retake ? null : $retake->format('Y-m-d\TH:i:s\Z')];
    }

    /**
     * `$keys` in an order decided by `$seed` alone.
     *
     * @param  list<int>  $keys
     * @return list<int>
     */
    private static function shuffled(array $keys, string $seed): array
    {
        usort($keys, fn (int $left, int $right): int => strcmp(hash('sha256', $seed.'|'.$left), hash('sha256', $seed.'|'.$right)));

        return $keys;
    }
}
