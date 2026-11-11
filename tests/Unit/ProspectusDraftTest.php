<?php

declare(strict_types=1);

use App\Domain\Business\ProspectusDraft;

it('keeps whole-franc amounts, trimmed text and a whole percentage', function (): void {
    $result = ProspectusDraft::normalize(['purpose' => "  Buy a cold room.\r\nInstall it.  ", 'use_of_funds' => [['label' => ' Cold room ', 'amount' => 9000000, 'quote_amount' => '', 'quote_document_id' => '01JC8A00000000000000000QTE']],
        'forecast' => [['month' => '2027-01', 'revenue' => '4000000', 'costs' => 0, 'contract_document_id' => '']],
        'risks' => [['text' => 'Power cuts.', 'concentration' => 'yes']], 'largest_customer_share_percent' => '30']);

    expect($result['errors'])->toBe([])->and($result['inputs'])->toBe(['purpose' => "Buy a cold room.\nInstall it.",
        'use_of_funds' => [['label' => 'Cold room', 'amount' => '9000000', 'quote_amount' => null, 'quote_document_id' => '01jc8a00000000000000000qte']],
        'forecast' => [['month' => '2027-01', 'revenue' => '4000000', 'costs' => '0', 'contract_document_id' => null]],
        'assumptions' => '', 'downside' => '', 'profiles' => [], 'largest_customer_share_percent' => 30,
        'risks' => [['text' => 'Power cuts.', 'concentration' => false]]])
        ->and(ProspectusDraft::blank()['largest_customer_share_percent'])->toBeNull();
});

it('refuses input that is not the right shape or exceeds the storage ceilings', function (): void {
    $result = ProspectusDraft::normalize(['purpose' => ['not text'], 'assumptions' => str_repeat('a', ProspectusDraft::TEXT_CEILING + 1),
        'use_of_funds' => ['label' => 'not a list'], 'forecast' => array_fill(0, ProspectusDraft::MAXIMUM_MONTHS + 1, []),
        'profiles' => ['nope'], 'risks' => 'none', 'largest_customer_share_percent' => 'many']);

    expect($result['errors'])->toEqual(['purpose' => ['Enter text.'], 'use_of_funds' => ['Send a list.'],
        'forecast' => ['Keep this to 60 entries.'], 'profiles.0.role' => ['Choose director, owner or official representative.'],
        'risks' => ['Send a list.'], 'assumptions' => ['Keep this to 20000 characters.'],
        'largest_customer_share_percent' => ['Enter a whole percentage from 0 to 100.']])
        ->and($result['inputs']['profiles'])->toBe([['name' => '', 'role' => '', 'text' => '']])
        ->and($result['inputs']['largest_customer_share_percent'])->toBeNull();
});

it('refuses a document reference that is not an uploaded document id', function (): void {
    $result = ProspectusDraft::normalize(['use_of_funds' => [['label' => 'Stock', 'amount' => '1', 'quote_document_id' => 'quote.pdf']],
        'forecast' => [['month' => '2027-01', 'revenue' => '1', 'costs' => '1', 'contract_document_id' => true]]]);

    expect($result['errors'])->toEqual(['use_of_funds.0.quote_document_id' => ['Choose one of the documents you uploaded.'],
        'forecast.0.contract_document_id' => ['Choose one of the documents you uploaded.']])
        ->and($result['inputs']['use_of_funds'][0]['quote_document_id'])->toBeNull()
        ->and($result['inputs']['forecast'][0]['contract_document_id'])->toBeNull();
});
