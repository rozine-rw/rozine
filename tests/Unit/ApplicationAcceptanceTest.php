<?php

declare(strict_types=1);

use App\Domain\Business\ApplicationAcceptance;
use App\Domain\Operations\CommandRejection;

/** @return array<string, mixed> */
function applicationAcceptanceInput(): array
{
    return ['quote_id' => '01arz3ndektsv4rrffq69g5fav', 'quote_revision' => 1, 'evidence_version' => str_repeat('a', 64),
        'mandate_version' => '1', 'accepted_principal' => '5000000',
        'documents' => [['kind' => 'terms', 'version' => 'v1', 'sha256' => str_repeat('b', 64)]],
        'disclosures' => [['key' => 'risk', 'version' => 'v1', 'sha256' => str_repeat('c', 64)]],
        'terms' => true, 'privacy' => true, 'signature_name' => '  Synthetic signer  '];
}

it('normalizes attestation input without making typed names proof of identity', function (): void {
    $input = applicationAcceptanceInput();
    expect((new ApplicationAcceptance)->normalize($input))->toBe([...$input, 'signature_name' => 'Synthetic signer']);
});

it('refuses malformed or incomplete acceptance input before nested values are accessed', function (array $change): void {
    expect(fn () => (new ApplicationAcceptance)->normalize([...applicationAcceptanceInput(), ...$change]))->toThrow(CommandRejection::class);
})->with([
    'extra authority' => [['party_id' => 'forged']],
    'numeric quote' => [['quote_id' => 1]], 'bad quote' => [['quote_id' => 'foreign']],
    'bad revision type' => [['quote_revision' => '1']], 'zero revision' => [['quote_revision' => 0]],
    'bad evidence' => [['evidence_version' => 'missing']], 'bad mandate' => [['mandate_version' => '0']],
    'fractional principal' => [['accepted_principal' => '1.5']],
    'terms declined' => [['terms' => false]], 'privacy declined' => [['privacy' => false]], 'terms truthy' => [['terms' => '1']],
    'empty name' => [['signature_name' => '  ']], 'long name' => [['signature_name' => str_repeat('x', 181)]],
    'invalid utf8' => [['signature_name' => "bad\xFF"]], 'control characters' => [['signature_name' => "One\nTwo"]],
    'null documents' => [['documents' => null]], 'map documents' => [['documents' => ['terms' => []]]],
    'no documents' => [['documents' => []]], 'too many documents' => [['documents' => array_fill(0, 31, [])]],
    'non-array document' => [['documents' => [1]]], 'missing reference fields' => [['documents' => [['kind' => 'terms']]]],
    'unexpected reference field' => [['documents' => [['kind' => 'terms', 'version' => 'v1', 'text' => 'override']]]],
    'reference identity type' => [['documents' => [['kind' => 1, 'version' => 'v1', 'sha256' => str_repeat('a', 64)]]]],
    'reference version type' => [['documents' => [['kind' => 'terms', 'version' => 1, 'sha256' => str_repeat('a', 64)]]]],
    'reference hash type' => [['documents' => [['kind' => 'terms', 'version' => 'v1', 'sha256' => 1]]]],
    'reference identity invalid' => [['documents' => [['kind' => '', 'version' => 'v1', 'sha256' => str_repeat('a', 64)]]]],
    'reference version invalid' => [['documents' => [['kind' => 'terms', 'version' => '', 'sha256' => str_repeat('a', 64)]]]],
    'reference hash invalid' => [['documents' => [['kind' => 'terms', 'version' => 'v1', 'sha256' => 'bad']]]],
]);

it('requires every acceptance field', function (): void {
    $input = applicationAcceptanceInput();
    unset($input['signature_name']);
    expect(fn () => (new ApplicationAcceptance)->normalize($input))->toThrow(CommandRejection::class, 'APPLICATION_ACCEPTANCE_INVALID');
});
