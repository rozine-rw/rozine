<?php

declare(strict_types=1);

use App\Application\Business\GetBusinessProspectus;
use App\Application\Business\SaveBusinessProspectus;
use App\Application\Business\SignBusinessProspectus;
use App\Application\Business\UploadProspectusDocument;
use App\Application\Operations\Contracts\CanonicalJson;
use App\Domain\Business\ProspectusGates;
use App\Models\BusinessProspectusDocument;
use App\Models\BusinessProspectusVersion;
use App\Models\CommandOperation;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditSealingFixture;
use Tests\Support\BusinessQuoteFixture;

/*
 * The prospectus behind a listing (BRS v3.0 PRO-R02 to PRO-R04, settled on #524): the Business saves
 * drafts as immutable versions, every gate is judged against the accepted application's current
 * terms, and the official representative's signature locks one fingerprinted version.
 */

beforeEach(function (): void {
    $this->freezeSecond();
    $this->accepted = function (int $signatories = 1, ?int $required = null, bool $sealed = true): array {
        $fixture = AuditSealingFixture::ready($signatories, requiredSignatories: $required);
        if ($sealed) {
            AuditSealingFixture::seal($fixture);
            AuditSealingFixture::publish($fixture);
        }

        return $fixture;
    };
    $this->user = fn (array $fixture, int $index = 0): int => $fixture['audit']['authority']['users'][$index]->id;
    $this->read = fn (array $fixture, int $user = 0): array => app(GetBusinessProspectus::class)->handle(($this->user)($fixture, $user), 1,
        $fixture['audit']['business'], $fixture['application']->id);
    $this->save = fn (array $fixture, int $expected, array $input, int $user = 0, ?string $request = null): array => app(SaveBusinessProspectus::class)->handle(
        ($this->user)($fixture, $user), 1, $fixture['audit']['business'], $fixture['application']->id, $expected, $input, $request ?? (string) Str::uuid());
    $this->sign = fn (array $fixture, int $expected, int $user = 0): array => app(SignBusinessProspectus::class)->handle(
        ($this->user)($fixture, $user), 1, $fixture['audit']['business'], $fixture['application']->id, $expected, (string) Str::uuid());
    $this->upload = fn (array $fixture, string $kind = 'quote', string $filename = 'cold-room-quote.pdf', string $content = "%PDF-1.7\nSupplier quote\n", int $user = 0): array => app(UploadProspectusDocument::class)->handle(
        ($this->user)($fixture, $user), 1, $fixture['audit']['business'], $fixture['application']->id, $kind, $filename, $content, (string) Str::uuid());
    $this->complete = function (string $name, ?string $quote = null): array {
        $profile = 'I have run the workshop for eleven years, '.str_repeat('managing suppliers, staff and the cold chain every week ', 5).'and I keep the books myself.';

        return ['purpose' => 'Buy and install a solar cold room so fresh produce keeps for a week, cutting spoilage and letting us take larger orders.',
            'use_of_funds' => [['label' => 'Solar cold room', 'amount' => '8476000', 'quote_amount' => '8500000', 'quote_document_id' => $quote],
                ['label' => 'Installation and wiring', 'amount' => '2000000', 'quote_amount' => null, 'quote_document_id' => null]],
            'forecast' => array_map(fn (int $month): array => ['month' => sprintf('2027-%02d', $month), 'revenue' => '4000000', 'costs' => '1000000',
                'contract_document_id' => null], range(1, 6)),
            'assumptions' => 'Sales hold at the verified average while the cold room cuts spoilage.',
            'downside' => 'If sales fall by a third we pause new stock purchases and still meet each instalment.',
            'profiles' => [['name' => $name, 'role' => 'representative', 'text' => $profile]], 'largest_customer_share_percent' => 10,
            'risks' => [['text' => 'Power cuts can spoil stock before the batteries take over.', 'concentration' => false],
                ['text' => 'Produce prices swing with the harvest season.', 'concentration' => false],
                ['text' => 'A new competitor could open nearby.', 'concentration' => false]]];
    };
});

it('prefills the people to introduce and saves each draft as an immutable version', function (): void {
    $fixture = ($this->accepted)();
    $name = $fixture['audit']['authority']['people'][0]->name ?? 'Verified person 0';

    $blank = ($this->read)($fixture);
    expect($blank['version'])->toBe(0)->and($blank['signed'])->toBeNull()->and($blank['can_edit'])->toBeTrue()->and($blank['can_sign'])->toBeFalse()
        ->and($blank['inputs']['profiles'])->toBe([['name' => $name, 'role' => 'representative', 'text' => '']])
        ->and($blank['facts'])->toMatchArray(['principal' => '10800000', 'term_months' => 6, 'instalment' => '1999800',
            'verified_monthly_revenue' => '4000000', 'registered_names' => [$name], 'required_people' => [['name' => $name, 'role' => 'representative']],
            'evidence' => [], 'signed' => false])
        ->and($blank['documents'])->toBe([])
        ->and($blank['evaluation']['may_sign'])->toBeFalse()
        ->and(collect($blank['evaluation']['gates'])->firstWhere('id', 'P-G04')['fix'])
        ->toBe('Use-of-funds lines must add up to RWF 10,476,000, the principal less the 3% platform fee.');

    $first = ($this->save)($fixture, 0, ['purpose' => 'A first thought.', 'profiles' => $blank['inputs']['profiles']]);
    expect($first['status'])->toBe('completed')->and($first['data'])->toEqual(['application_id' => $fixture['application']->id, 'version' => 1]);
    $quote = ($this->upload)($fixture)['data']['document_id'];
    $second = ($this->save)($fixture, 1, ($this->complete)($name, $quote));
    expect($second['data']['version'])->toBe(2);

    $current = ($this->read)($fixture);
    $versions = BusinessProspectusVersion::query()->where('business_application_id', $fixture['application']->id)->orderBy('version')->get();
    expect($current['version'])->toBe(2)->and($current['inputs'])->toBe(($this->complete)($name, $quote))
        ->and($current['evaluation']['may_sign'])->toBeTrue()->and($current['can_sign'])->toBeTrue()
        ->and($versions->pluck('kind')->all())->toBe(['draft', 'draft'])->and($versions[0]->fingerprint)->toBeNull()
        ->and($versions[0]->payload['inputs']['purpose'])->toBe('A first thought.')
        ->and(DB::table('business_prospectus_versions')->value('payload'))->not->toContain($name)
        ->and(json_encode(CommandOperation::query()->where('command', 'prospectus.save')->get()->map->getAttributes()))->not->toContain($name);

    expect(fn () => BusinessProspectusVersion::query()->whereKey($versions[0]->id)->update(['sha256' => str_repeat('0', 64)]))
        ->toThrow(QueryException::class);
});

it('locks the signed prospectus under its fingerprint once every gate passes', function (): void {
    $fixture = ($this->accepted)();
    $name = ($this->read)($fixture)['inputs']['profiles'][0]['name'];
    $quote = ($this->upload)($fixture)['data']['document_id'];
    ($this->save)($fixture, 0, ($this->complete)($name, $quote));

    $signed = ($this->sign)($fixture, 1);
    expect($signed['status'])->toBe('completed')->and($signed['code'])->toBe('PROSPECTUS_SIGNED')->and($signed['data']['version'])->toBe(2);

    $record = BusinessProspectusVersion::query()->where('kind', 'signed')->sole();
    $document = $record->payload['document'] ?? [];
    $current = ($this->read)($fixture);
    expect($record->fingerprint)->toBe($signed['data']['fingerprint'])
        ->and($record->fingerprint)->toBe(hash('sha256', app(CanonicalJson::class)->encode($document)))
        ->and($document)->toMatchArray(['version' => 2, 'draft_version' => 1, 'inputs' => ($this->complete)($name, $quote),
            'evidence_sha256' => [$quote => hash('sha256', "%PDF-1.7\nSupplier quote\n")], 'signed_by_name' => $name])
        ->and($document['facts']['signed'] ?? null)->toBeTrue()
        ->and($current['signed'])->toBe(['version' => 2, 'fingerprint' => $record->fingerprint, 'signed_at' => $record->created_at->toIso8601String()])
        ->and($current['evaluation']['publishable'])->toBeTrue()->and($current['evaluation']['may_sign'])->toBeFalse()
        ->and(collect($current['evaluation']['gates'])->firstWhere('id', 'P-G12')['passed'])->toBeTrue()
        ->and($current['can_edit'])->toBeFalse()->and($current['can_sign'])->toBeFalse();

    expect(($this->save)($fixture, 2, ($this->complete)($name)))->toMatchArray(['status' => 'rejected', 'code' => 'PROSPECTUS_SIGNED'])
        ->and(($this->sign)($fixture, 2))->toMatchArray(['status' => 'rejected', 'code' => 'PROSPECTUS_SIGNED'])
        ->and(($this->upload)($fixture))->toMatchArray(['status' => 'rejected', 'code' => 'PROSPECTUS_SIGNED'])
        ->and(BusinessProspectusVersion::query()->count())->toBe(2)->and(BusinessProspectusDocument::query()->count())->toBe(1);
});

it('refuses to sign while a gate fails, and names each fix', function (): void {
    $fixture = ($this->accepted)();
    $name = ($this->read)($fixture)['inputs']['profiles'][0]['name'];
    $draft = ($this->complete)($name, ($this->upload)($fixture)['data']['document_id']);
    $draft['risks'] = array_slice($draft['risks'], 0, 2);
    $draft['purpose'] = 'A guaranteed return on a cold room that more than pays for itself within the first few months of trading.';
    ($this->save)($fixture, 0, $draft);

    $refused = ($this->sign)($fixture, 1);
    expect($refused['status'])->toBe('rejected')->and($refused['code'])->toBe('PROSPECTUS_GATES_FAILED')
        ->and(array_column($refused['data']['gates'], 'id'))->toBe(['P-G09', 'P-G10'])
        ->and(($this->sign)($fixture, 0))->toMatchArray(['status' => 'rejected', 'code' => 'VERSION_CONFLICT'])
        ->and(BusinessProspectusVersion::query()->where('kind', 'signed')->exists())->toBeFalse();
});

it('refuses malformed drafts, stale versions and a signature from anyone but the representative, even another required signatory', function (): void {
    $fixture = ($this->accepted)(2, 2);
    $name = ($this->read)($fixture)['inputs']['profiles'][0]['name'];

    $malformed = ($this->save)($fixture, 0, ['use_of_funds' => [['label' => 'Stock', 'amount' => '12.50']], 'forecast' => [['month' => '2027-13']],
        'profiles' => [['name' => 'A', 'role' => 'chairman']], 'largest_customer_share_percent' => 120]);
    expect($malformed)->toMatchArray(['status' => 'rejected', 'code' => 'VALIDATION_FAILED'])
        ->and($malformed['field_errors'])->toEqual(['use_of_funds.0.amount' => ['Enter a whole amount in francs.'],
            'forecast.0.month' => ['Enter the month as YYYY-MM.'], 'forecast.0.revenue' => ['Enter a whole amount in francs.'],
            'forecast.0.costs' => ['Enter a whole amount in francs.'], 'profiles.0.role' => ['Choose director, owner or official representative.'],
            'largest_customer_share_percent' => ['Enter a whole percentage from 0 to 100.']]);

    expect(($this->save)($fixture, 0, ($this->complete)($name))['status'])->toBe('completed')
        ->and(($this->save)($fixture, 0, ($this->complete)($name)))->toMatchArray(['status' => 'rejected', 'code' => 'VERSION_CONFLICT'])
        ->and(($this->read)($fixture, 1)['can_sign'])->toBeFalse()
        ->and(($this->sign)($fixture, 1, 1))->toMatchArray(['status' => 'rejected', 'code' => 'ACTION_FORBIDDEN'])
        ->and(BusinessProspectusVersion::query()->count())->toBe(1);
});

it('cannot judge the prospectus until the application has a current published report', function (): void {
    $fixture = ($this->accepted)(sealed: false);

    $current = ($this->read)($fixture);
    expect($current['facts'])->toBeNull()->and($current['evaluation'])->toBeNull()->and($current['unavailable'])->toBe('REPORT_NOT_CURRENT')
        ->and($current['can_edit'])->toBeTrue()->and($current['can_sign'])->toBeFalse();
    ($this->save)($fixture, 0, ['purpose' => 'Drafting while the audit finishes.']);
    expect(($this->sign)($fixture, 1))->toMatchArray(['status' => 'rejected', 'code' => 'REPORT_NOT_CURRENT']);
});

it('has no prospectus to judge before the application is accepted, nor for another Business\'s application', function (): void {
    $fixture = BusinessQuoteFixture::ready();

    expect(($this->read)($fixture))->toMatchArray(['facts' => null, 'evaluation' => null, 'unavailable' => 'PROSPECTUS_NOT_AVAILABLE'])
        ->and(app(SaveBusinessProspectus::class)->handle(($this->user)($fixture), 1, $fixture['audit']['business'], (string) Str::ulid(), 0, [], (string) Str::uuid()))
        ->toMatchArray(['status' => 'rejected', 'code' => 'APPLICATION_NOT_FOUND'])
        ->and(app(UploadProspectusDocument::class)->handle(($this->user)($fixture), 1, $fixture['audit']['business'], (string) Str::ulid(), 'quote',
            'cold-room-quote.pdf', "%PDF-1.7\nSupplier quote\n", (string) Str::uuid()))
        ->toMatchArray(['status' => 'rejected', 'code' => 'APPLICATION_NOT_FOUND']);
});

it('serves the Listing Builder and answers its saves and signature over the web', function (): void {
    $this->withoutVite();
    $fixture = ($this->accepted)();
    $route = ['business' => $fixture['audit']['business'], 'application' => $fixture['application']->id];
    $owner = $fixture['audit']['authority']['users'][0];
    $name = ($this->read)($fixture)['inputs']['profiles'][0]['name'];

    $this->actingAs($owner)->get(route('business.applications.prospectus.show', $route))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->component('business/prospectus')->where('version', 0)
            ->where('facts.platform_fee', '324000')->where('facts.net_of_fee', '10476000')->where('signed', null)
            ->where('actions.save.url', route('business.applications.prospectus.save', $route, false))->where('actions.sign', null)
            ->where('sections', ProspectusGates::SECTIONS));

    $this->post(route('business.applications.prospectus.documents.store', $route), ['request_id' => (string) Str::uuid(), 'kind' => 'quote',
        'file' => UploadedFile::fake()->createWithContent('cold-room-quote.pdf', "%PDF-1.7\nSupplier quote\n")])
        ->assertRedirect(route('business.applications.prospectus.show', $route))->assertSessionHasNoErrors();
    $this->post(route('business.applications.prospectus.documents.store', $route), ['request_id' => (string) Str::uuid(), 'kind' => 'quote',
        'file' => UploadedFile::fake()->createWithContent('quote.pdf', 'not a pdf')])
        ->assertSessionHasErrors(['file' => 'Upload a PDF, PNG or JPEG.']);
    $quote = BusinessProspectusDocument::query()->sole()->id;
    $this->get(route('business.applications.prospectus.show', $route))
        ->assertInertia(fn (Assert $page): Assert => $page->where('documents.0.id', $quote)->where('documents.0.filename', 'cold-room-quote.pdf')
            ->where('documents.0.kind', 'quote')->where('documents.0.media_type', 'application/pdf')
            ->where('actions.upload.url', route('business.applications.prospectus.documents.store', $route, false)));

    $this->put(route('business.applications.prospectus.save', $route), ['request_id' => (string) Str::uuid(), 'expected_version' => 0, ...($this->complete)($name, $quote)])
        ->assertRedirect(route('business.applications.prospectus.show', $route))->assertSessionHasNoErrors();
    $this->put(route('business.applications.prospectus.save', $route), ['request_id' => (string) Str::uuid(), 'expected_version' => 0, ...($this->complete)($name, $quote)])
        ->assertSessionHasErrors(['purpose' => 'The prospectus changed since you opened it. Reload to see the latest version.']);
    $this->put(route('business.applications.prospectus.save', $route), ['request_id' => (string) Str::uuid(), 'expected_version' => 1,
        ...($this->complete)($name, $quote), 'largest_customer_share_percent' => 101])
        ->assertSessionHasErrors(['largest_customer_share_percent' => 'Enter a whole percentage from 0 to 100.']);
    $this->get(route('business.applications.prospectus.show', $route))
        ->assertInertia(fn (Assert $page): Assert => $page->where('version', 1)->where('actions.sign.url', route('business.applications.prospectus.sign', $route, false)));

    $this->post(route('business.applications.prospectus.sign', $route), ['request_id' => (string) Str::uuid(), 'expected_version' => 1])
        ->assertRedirect(route('business.applications.prospectus.show', $route))->assertSessionHasNoErrors();
    $this->post(route('business.applications.prospectus.sign', $route), ['request_id' => (string) Str::uuid(), 'expected_version' => 2])
        ->assertSessionHasErrors(['declaration' => 'The prospectus is signed and locked.']);
    $this->get(route('business.applications.prospectus.show', $route))
        ->assertInertia(fn (Assert $page): Assert => $page->where('signed.version', 2)->where('actions.save', null)->where('actions.upload', null)->where('actions.sign', null));
});

it('falls back to a general refusal for a code the builder has no words for', function (): void {
    $fixture = ($this->accepted)();
    $route = ['business' => $fixture['audit']['business'], 'application' => (string) Str::ulid()];

    $this->actingAs($fixture['audit']['authority']['users'][0])
        ->post(route('business.applications.prospectus.sign', $route), ['request_id' => (string) Str::uuid(), 'expected_version' => 1, 'identity_context_revision' => 1])
        ->assertSessionHasErrors(['declaration' => 'We could not save the prospectus. Try again.']);
});

it('counts only quotes and contracts uploaded to this prospectus, keeps their bytes encrypted and never changes them', function (): void {
    $other = ($this->accepted)();
    $foreign = ($this->upload)($other)['data']['document_id'];
    $this->travel(1)->minutes();
    $fixture = ($this->accepted)();
    $name = ($this->read)($fixture)['inputs']['profiles'][0]['name'];
    $contract = ($this->upload)($fixture, 'contract', 'hotel-contract.png', "\x89PNG\r\n\x1a\nsigned")['data']['document_id'];

    ($this->save)($fixture, 0, ($this->complete)($name, $foreign));
    $gates = collect(($this->read)($fixture)['evaluation']['gates'])->keyBy('id');
    expect($gates['P-G05'])->toMatchArray(['passed' => false, 'fix' => 'Upload the quote or invoice, and give its amount within 5% of the line, for: Solar cold room.'])
        ->and(($this->sign)($fixture, 1))->toMatchArray(['status' => 'rejected', 'code' => 'PROSPECTUS_GATES_FAILED']);

    ($this->save)($fixture, 1, ($this->complete)($name, $contract));
    expect(collect(($this->read)($fixture)['evaluation']['gates'])->firstWhere('id', 'P-G05')['passed'])->toBeFalse();

    $quote = ($this->upload)($fixture, 'quote', 'quote.jpeg', "\xff\xd8\xffquote")['data']['document_id'];
    $draft = ($this->complete)($name, $quote);
    $draft['forecast'][0]['revenue'] = '9000000';
    ($this->save)($fixture, 2, $draft);
    expect(collect(($this->read)($fixture)['evaluation']['gates'])->firstWhere('id', 'P-G06')['fix'])->toContain('2027-01');
    $draft['forecast'][0]['contract_document_id'] = $contract;
    ($this->save)($fixture, 3, $draft);
    $current = ($this->read)($fixture);
    expect($current['evaluation']['may_sign'])->toBeTrue()
        ->and($current['facts']['evidence'])->toEqual([$contract => 'contract', $quote => 'quote'])
        ->and(array_column($current['documents'], 'filename'))->toBe(['hotel-contract.png', 'quote.jpeg'])
        ->and(array_column($current['documents'], 'media_type'))->toBe(['image/png', 'image/jpeg'])
        ->and(json_encode(DB::table('business_prospectus_documents')->get()))->not->toContain('hotel-contract')->not->toContain('signed');

    expect(fn () => BusinessProspectusDocument::query()->whereKey($quote)->update(['kind' => 'contract']))->toThrow(QueryException::class)
        ->and(fn () => BusinessProspectusDocument::query()->whereKey($quote)->delete())->toThrow(QueryException::class);
});

it('refuses a document that is not a PDF, PNG or JPEG quote or contract', function (string $kind, string $filename, string $content, string $field, string $message): void {
    $fixture = ($this->accepted)();

    expect(($this->upload)($fixture, $kind, $filename, $content))->toMatchArray(['status' => 'rejected', 'code' => 'VALIDATION_FAILED', 'field_errors' => [$field => [$message]]])
        ->and(BusinessProspectusDocument::query()->exists())->toBeFalse();
})->with([
    'an unknown kind' => ['receipt', 'quote.pdf', '%PDF-1.7', 'kind', 'Choose a quote or invoice, or a signed contract.'],
    'a path in the name' => ['quote', '../quote.pdf', '%PDF-1.7', 'file', 'Use a plain PDF, PNG or JPEG filename.'],
    'an empty file' => ['quote', 'quote.pdf', '', 'file', 'The file must be nonempty and at most 10 MiB.'],
    'a renamed file' => ['quote', 'quote.pdf', "\x89PNG\r\n\x1a\n", 'file', 'Upload a PDF, PNG or JPEG.'],
]);

it('requires a profile for each director and owner on the mandate, not only the representative', function (): void {
    $fixture = ($this->accepted)(2, 1);
    $read = ($this->read)($fixture);
    $quote = ($this->upload)($fixture)['data']['document_id'];
    ($this->save)($fixture, 0, ($this->complete)($read['inputs']['profiles'][0]['name'], $quote));

    $others = array_values(array_filter($read['facts']['required_people'], fn (array $person): bool => $person['role'] !== 'representative'));
    expect($read['inputs']['profiles'])->toHaveCount(count($read['facts']['required_people']))
        ->and($others)->not->toBe([])
        ->and(collect(($this->read)($fixture)['evaluation']['gates'])->firstWhere('id', 'P-G03')['fix'])
        ->toContain($others[0]['name'].' ('.$others[0]['role'].')');
});
