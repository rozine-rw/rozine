<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Secondary\Contracts\SecondaryMarket;
use App\Domain\Operations\CommandRejection;
use App\Http\Requests\Staff\OverseeSecondaryMarketRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Compliance's commands on the Secondary Market page (BRS v3.0 SEC-R08, SEC-R09): halt the whole
 * market or one note, lift a halt in force, and cancel one open listing, each with a written reason
 * the market records (`secondary.halt`, `secondary.listings.cancel`). A refusal comes back on the
 * reason field so the confirmation stage shows it; success returns to the page, which reads again.
 */
class StaffSecondaryMarketController extends Controller
{
    private const array MESSAGES = [
        'SECONDARY_TRADING_DISABLED' => 'Resale trading is switched off, so there is nothing to halt, lift or cancel.',
        'MARKET_ALREADY_HALTED' => 'The whole market is already halted. Refresh the page.',
        'NOTE_ALREADY_HALTED' => 'This note is already halted. Refresh the page.',
        'MARKET_NOT_HALTED' => 'The whole market is not halted. Refresh the page.',
        'NOTE_NOT_HALTED' => 'This note is not halted. Refresh the page.',
        'NOTE_NOT_FOUND' => 'This note no longer exists.',
        'LISTING_NOT_FOUND' => 'This listing no longer exists. Refresh the page.',
        'LISTING_CLOSED' => 'This listing has already ended. Refresh the page.',
        'LISTING_RESERVED' => 'A buyer is holding units of this listing. Try again once their reservation ends.',
        'REASON_REQUIRED' => 'Write the reason. It is recorded and shown to the people affected.',
    ];

    public function halt(OverseeSecondaryMarketRequest $request, SecondaryMarket $market): RedirectResponse
    {
        return $this->answer(fn (): array => $market->halt($this->staff($request), $this->note($request), (string) $request->validated('reason'),
            (string) $request->validated('request_id')));
    }

    public function lift(OverseeSecondaryMarketRequest $request, SecondaryMarket $market): RedirectResponse
    {
        return $this->answer(fn (): array => $market->lift($this->staff($request), $this->note($request), (string) $request->validated('reason'),
            (string) $request->validated('request_id')));
    }

    public function cancel(OverseeSecondaryMarketRequest $request, SecondaryMarket $market, string $listing): RedirectResponse
    {
        return $this->answer(fn (): array => $market->cancelListing($this->staff($request), $listing, (string) $request->validated('reason'),
            (string) $request->validated('request_id')));
    }

    /** @param  callable(): array{status: string, code: string}  $command */
    private function answer(callable $command): RedirectResponse
    {
        try {
            $result = $command();
        } catch (CommandRejection $rejection) {
            $result = ['status' => 'rejected', 'code' => $rejection->reason];
        }
        if ($result['status'] !== 'completed') {
            throw ValidationException::withMessages(['reason' => [self::MESSAGES[$result['code']] ?? 'We could not record this. Try again.']]);
        }

        return to_route('staff.sections.show', ['section' => 'secondary-market']);
    }

    private function staff(OverseeSecondaryMarketRequest $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }

    private function note(OverseeSecondaryMarketRequest $request): ?string
    {
        $note = $request->validated('note_id');

        return is_string($note) && $note !== '' ? strtolower($note) : null;
    }
}
