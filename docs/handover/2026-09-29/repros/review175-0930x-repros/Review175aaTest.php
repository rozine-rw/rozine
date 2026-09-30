<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Primary\Contracts\CampaignFundingEvidence;
use App\Application\Primary\Contracts\PrimaryCheckout;
use App\Application\Primary\Contracts\PrimaryFunding;
use App\Application\Primary\Contracts\PrimaryReservations;
use App\Application\Wallet\Contracts\WalletPostings;
use App\Application\Wallet\PostingSource;
use App\Domain\Operations\CommandRejection;
use App\Domain\Wallet\WalletMoney;
use App\Models\BusinessCampaignClosure;
use App\Models\CommandOperation;
use App\Models\LedgerEntry;
use App\Models\PrimaryCampaignFunding;
use App\Models\PrimaryCommitment;
use App\Models\PrimaryReservationRecord;
use App\Models\PrimaryReservationVersion;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

/* Review #175 range 33ba9040..3bb74427 (durable funding lock). PROBE = expected to hold; OBSERVE = pins a finding. */

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->checkout = app(PrimaryCheckout::class);
    $this->reserve = function (array $investor, string $units = '1080'): PrimaryReservationRecord {
        $result = $this->checkout->reserve($investor['user']->id, 1, $this->campaign->id, $units, (string) Str::uuid(), PrimaryReservationFixture::terms(...));

        return PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
    };
    $this->confirm = function (array $investor, PrimaryReservationRecord $root): string {
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $root->id)->orderByDesc('revision')->firstOrFail();

        return $this->checkout->confirm($investor['user']->id, 1, $this->campaign->id, $root->id, $version->revision,
            $version->payload['terms']['disclosure_version'], $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    };
    $this->admission = fn (): array => ['campaign_id' => $this->campaign->id, 'publication_sha256' => $this->campaign->sha256,
        ...array_fill_keys(['eligibility', 'policy', 'connections', 'destination'], ['status' => 'passed', 'evidence' => ['synthetic' => 'review fixture']])];
    $this->fund = fn (?Closure $admit = null): array => app(PrimaryFunding::class)->lock($this->campaign->id, $admit ?? fn (): array => ($this->admission)());
    $this->soldOut = function (): array {
        $a = PrimaryReservationFixture::investor();
        $b = PrimaryReservationFixture::investor();
        $ra = ($this->reserve)($a);
        $rb = ($this->reserve)($b);
        expect(($this->confirm)($a, $ra))->toBe('RESERVATION_CONFIRMED')->and(($this->confirm)($b, $rb))->toBe('RESERVATION_CONFIRMED');

        return [[$a, $ra], [$b, $rb]];
    };
    /* Capture the rows a real lock writes, then roll them back, so raw-SQL forgeries start from valid bindings. */
    $this->captured = function (): array {
        DB::beginTransaction();
        ($this->fund)();
        $record = (array) DB::table('primary_campaign_fundings')->sole();
        $members = DB::table('primary_funding_commitments')->orderBy('reservation_id')->get()->map(fn (object $row): array => (array) $row)->all();
        DB::rollBack();

        return [$record, $members];
    };
    $this->forge = function (array $record, array $members, bool $immediateFirst = false, bool $membersFirst = false): void {
        DB::transaction(function () use ($record, $members, $immediateFirst, $membersFirst): void {
            if ($immediateFirst) {
                DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
            }
            if (! $membersFirst) {
                DB::table('primary_campaign_fundings')->insert($record);
            }
            foreach ($members as $member) {
                DB::table('primary_funding_commitments')->insert($member);
            }
            if ($membersFirst) {
                DB::table('primary_campaign_fundings')->insert($record);
            }
            DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
        });
    };
});

it('OBSERVE the exact funded page over browser and token', function (string $instant): void {
    ($this->soldOut)();
    if ($instant === 'late') {
        $this->travelTo($this->campaign->expires_at->addDay());
    }
    $before = app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    ($this->fund)();
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $actor = User::query()->findOrFail($this->campaign->actor_user_id);
    Sanctum::actingAs($actor, ['business:read', 'business:command']);
    $token = $this->getJson(route('api.v1.business.campaigns.show', $parameters))->assertOk()->json('data');
    $this->app['auth']->forgetGuards();
    $browser = null;
    $this->actingAs($actor)->get(route('business.campaigns.show', $parameters))->assertOk()
        ->assertInertia(function ($page) use (&$browser) {
            $browser = $page->toArray()['props'];

            return $page;
        });
    fwrite(STDERR, "\n[$instant] before funding progress keys: ".json_encode(array_keys($before['progress'])).' lifecycle='.$before['lifecycle']."\n");
    fwrite(STDERR, "[$instant] token campaign=".json_encode($token['campaign']).' allowed_actions='.json_encode($token['allowed_actions']).' actions='.json_encode($token['actions'])."\n");
    fwrite(STDERR, "[$instant] token note.progress=".json_encode($token['note']['progress'], JSON_PRETTY_PRINT)."\n");
    fwrite(STDERR, "[$instant] browser note.progress=".json_encode($browser['note']['progress'])."\n");
    fwrite(STDERR, "[$instant] types=".json_encode(array_map(get_debug_type(...), $token['note']['progress']))."\n");
    expect($token['campaign']['lifecycle'])->toBe('funded_pending_disbursement')
        ->and($token['note']['progress']['lifecycle'])->toBe('funded_pending_disbursement')
        ->and($token['note']['progress']['phase'])->toBe('funded')
        ->and($browser['campaign']['lifecycle'])->toBe('funded_pending_disbursement')
        ->and($browser['note']['progress'])->toBe($token['note']['progress'])
        ->and($token['allowed_actions'])->toBe([])->and($token['actions']['cancel'])->toBeNull()
        ->and(array_keys($token['note']['progress']))->toBe(array_keys($before['progress']))
        ->and($token['note']['progress'])->not->toHaveKey('funded_at')->not->toHaveKey('closing');
})->with(['live', 'late']);

it('OBSERVE cancel of a funded campaign over HTTP returns CAMPAIGN_FUNDED', function (string $transport): void {
    ($this->soldOut)();
    ($this->fund)();
    $parameters = ['business' => $this->campaign->business_id, 'campaign' => $this->campaign->id];
    $actor = User::query()->findOrFail($this->campaign->actor_user_id);
    $input = ['request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'campaign_id' => $this->campaign->id, 'expected_campaign_revision' => 1, 'reason' => null];
    if ($transport === 'token') {
        Sanctum::actingAs($actor, ['business:read', 'business:command']);
        $response = $this->postJson(route('api.v1.business.campaigns.cancel', $parameters), $input);
    } else {
        $response = $this->actingAs($actor)->postJson(route('business.campaigns.cancel', $parameters), $input);
    }
    fwrite(STDERR, "\n[$transport] cancel funded: HTTP ".$response->status().' '.json_encode(['code' => $response->json('code'), 'revision' => $response->json('revision'), 'data_keys' => array_keys($response->json('data') ?? []), 'current_lifecycle' => $response->json('data.current.campaign.lifecycle'), 'current_phase' => $response->json('data.current.note.progress.phase'), 'message' => $response->json('message')])."\n");
    expect(BusinessCampaignClosure::query()->count())->toBe(0);
})->with(['browser', 'token']);

it('PROBE replay is identical, never rewrites, and a second lock with different evidence keeps the first', function (): void {
    ($this->soldOut)();
    $first = ($this->fund)();
    $row = (array) DB::table('primary_campaign_fundings')->sole();
    $other = ($this->admission)();
    $other['policy']['evidence'] = ['different' => 'later evidence'];
    expect(($this->fund)(fn (): array => $other))->toBe($first)
        ->and((array) DB::table('primary_campaign_fundings')->sole())->toBe($row)
        ->and($first['admission']['policy']['evidence'])->toBe(['synthetic' => 'review fixture']);
    $this->travelTo($this->campaign->expires_at->addDays(3));
    expect(($this->fund)())->toBe($first)->and(PrimaryCampaignFunding::query()->count())->toBe(1);
});

it('OBSERVE a retry after funding is refused when current admission no longer passes, and the record stays', function (): void {
    ($this->soldOut)();
    $first = ($this->fund)();
    $failed = ($this->admission)();
    $failed['destination']['status'] = 'failed';
    $codes = [];
    foreach (['failed destination' => fn (): array => $failed, 'empty' => fn (): array => []] as $label => $admit) {
        try {
            ($this->fund)($admit);
            $codes[$label] = 'returned';
        } catch (CommandRejection $exception) {
            $codes[$label] = $exception->reason;
        }
    }
    fwrite(STDERR, "\nreplay with failing current admission: ".json_encode($codes)."\n");
    expect($codes)->toBe(['failed destination' => 'FUNDING_PRECHECK_FAILED', 'empty' => 'POLICY_INPUT_REQUIRED'])
        ->and(app(CampaignFundingEvidence::class)->find($this->campaign->id))->toBe($first);
});

it('PROBE a refused or throwing admission leaves no partial state, including rows the admission wrote', function (string $mode): void {
    ($this->soldOut)();
    $operations = CommandOperation::query()->count();
    $admit = function () use ($mode): array {
        CommandOperation::factory()->create();
        if ($mode === 'throws') {
            throw new RuntimeException('admission exploded');
        }
        $admission = ($this->admission)();
        if ($mode === 'failed') {
            $admission['policy']['status'] = 'failed';
        } elseif ($mode === 'unknown status') {
            $admission['policy']['status'] = 'PASSED';
        } elseif ($mode === 'other campaign') {
            $admission['campaign_id'] = strtolower((string) Str::ulid());
        } elseif ($mode === 'list evidence of nulls') {
            return [...$admission, 'eligibility' => ['status' => 'passed', 'evidence' => [null]]];
        }

        return $admission;
    };
    $outcome = 'funded';
    DB::beginTransaction();
    try {
        ($this->fund)($admit);
    } catch (Throwable $exception) {
        $outcome = $exception instanceof CommandRejection ? $exception->reason : $exception::class.':'.$exception->getMessage();
    }
    $inside = ['fundings' => PrimaryCampaignFunding::query()->count(), 'operations' => CommandOperation::query()->count() - $operations];
    DB::commit();
    fwrite(STDERR, "\n[$mode] outcome=$outcome inside=".json_encode($inside)."\n");
    if ($mode === 'list evidence of nulls') {
        expect($outcome)->toBe('funded');

        return;
    }
    expect($outcome)->not->toBe('funded')->and($inside)->toBe(['fundings' => 0, 'operations' => 0])
        ->and(DB::table('primary_funding_commitments')->count())->toBe(0);
})->with(['throws', 'failed', 'unknown status', 'other campaign', 'list evidence of nulls']);

it('OBSERVE the actual FOR UPDATE order of one lock()', function (): void {
    ($this->soldOut)();
    $queries = [];
    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        if (str_contains($event->sql, 'for update') && preg_match('/from "([a-z_]+)"/', $event->sql, $match)) {
            $queries[] = $match[1];
        } elseif (preg_match('/^(insert into|select \* from) "(ledger_[a-z]+|primary_campaign_fundings|primary_funding_commitments)"/', $event->sql, $match)) {
            $queries[] = '('.explode(' ', $match[1])[0].' '.$match[2].')';
        }
    });
    DB::beginTransaction();
    ($this->fund)(function () use (&$queries): array {
        $queries[] = '<<admission>>';

        return ($this->admission)();
    });
    DB::rollBack();
    $collapsed = [];
    foreach ($queries as $query) {
        if (end($collapsed) !== $query) {
            $collapsed[] = $query;
        }
    }
    fwrite(STDERR, "\nlock order: ".implode(' -> ', $collapsed)."\n");
    $first = fn (string $table): int => (int) array_search($table, $collapsed, true);
    expect($collapsed[0])->toBe('business_profiles')->and($collapsed[1])->toBe('<<admission>>')
        ->and($first('business_campaigns'))->toBeLessThan($first('primary_reservations'))
        ->and($first('primary_reservations'))->toBeLessThan($first('primary_commitments'))
        ->and($first('primary_commitments'))->toBeLessThan($first('investor_wallets'));
});

it('PROBE every post-funding command is refused and nothing moves', function (): void {
    [[$a, $ra], [$b, $rb]] = ($this->soldOut)();
    ($this->fund)();
    $c = PrimaryReservationFixture::investor();
    $cash = LedgerEntry::query()->orderBy('id')->get()->toJson();
    $versions = PrimaryReservationVersion::query()->count();
    $codes = [];
    $codes['reserve'] = $this->checkout->reserve($c['user']->id, 1, $this->campaign->id, '1', (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    $codes['confirm again'] = $this->checkout->confirm($a['user']->id, 1, $this->campaign->id, $ra->id, 2, 'x', str_repeat('0', 64), (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'];
    $codes['release'] = $this->checkout->release($a['user']->id, 1, $this->campaign->id, $ra->id, 2, (string) Str::uuid())['code'];
    $codes['cancel'] = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid())['code'];
    $this->travelTo($this->campaign->expires_at->addHour());
    $codes['campaigns:expire'] = (string) app(BusinessCampaignStore::class)->expireDue(10);
    $codes['primary:expire-reservations'] = (string) app(PrimaryReservations::class)->expireDue(10);
    $codes['cancel late'] = app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, null, (string) Str::uuid())['code'];
    $wallets = app(WalletPostings::class);
    foreach (['refund', 'release'] as $kind) {
        try {
            DB::transaction(fn () => $wallets->{$kind}($wallets->lockForParty($rb->party_id), WalletMoney::of($rb->principal),
                new PostingSource('primary_reservation', $rb->id, $rb->origin_operation_id)));
            $codes['wallet '.$kind] = 'posted';
        } catch (Throwable $exception) {
            $codes['wallet '.$kind] = $exception::class.': '.mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 150);
        }
    }
    fwrite(STDERR, "\npost-funding commands: ".json_encode($codes, JSON_PRETTY_PRINT)."\n");
    expect($codes['reserve'])->toBe('CAMPAIGN_FUNDED')->and($codes['cancel'])->toBe('CAMPAIGN_FUNDED')->and($codes['cancel late'])->toBe('CAMPAIGN_FUNDED')
        ->and($codes['campaigns:expire'])->toBe('0')->and($codes['primary:expire-reservations'])->toBe('0')
        ->and($codes['wallet refund'])->toContain('authoritative failed closing')->and($codes['wallet release'])->not->toBe('posted')
        ->and(LedgerEntry::query()->orderBy('id')->get()->toJson())->toBe($cash)
        ->and(PrimaryReservationVersion::query()->count())->toBe($versions)
        ->and(BusinessCampaignClosure::query()->count())->toBe(0);
});

it('PROBE raw SQL cannot end, reopen or extend a funded campaign', function (string $attack): void {
    [[$a, $ra], [$b, $rb]] = ($this->soldOut)();
    ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $run = fn () => DB::transaction(function () use ($attack, $ra, $rb): void {
        $confirmed = (array) DB::table('primary_reservation_versions')->where('primary_reservation_id', $ra->id)->where('revision', 2)->sole();
        if (in_array($attack, ['version released', 'version expired', 'version confirmed', 'version held'], true)) {
            $state = explode(' ', $attack)[1];
            DB::table('primary_reservation_versions')->insert([...$confirmed, 'id' => strtolower((string) Str::ulid()), 'revision' => 3,
                'state' => $state, 'operation_id' => $state === 'expired' ? null : CommandOperation::factory()->create()->id, 'previous_sha256' => $confirmed['sha256']]);
        } elseif ($attack === 'new root') {
            $root = (array) DB::table('primary_reservations')->where('id', $ra->id)->sole();
            DB::table('primary_reservations')->insert([...$root, 'id' => strtolower((string) Str::ulid()), 'origin_operation_id' => CommandOperation::factory()->create()->id]);
        } elseif ($attack === 'second commitment') {
            $commitment = (array) DB::table('primary_commitments')->where('primary_reservation_id', $ra->id)->sole();
            DB::table('primary_commitments')->insert([...$commitment, 'id' => strtolower((string) Str::ulid()), 'operation_id' => CommandOperation::factory()->create()->id]);
        } elseif ($attack === 'refund entry') {
            $commit = (array) DB::table('ledger_entries')->where('source_id', $rb->id)->where('kind', 'primary_commit')->sole();
            unset($commit['created_xid']);
            DB::table('ledger_entries')->insert([...$commit, 'id' => strtolower((string) Str::ulid()), 'kind' => 'primary_refund']);
        } elseif ($attack === 'release entry') {
            $commit = (array) DB::table('ledger_entries')->where('source_id', $rb->id)->where('kind', 'primary_commit')->sole();
            unset($commit['created_xid']);
            DB::table('ledger_entries')->insert([...$commit, 'id' => strtolower((string) Str::ulid()), 'kind' => 'primary_release']);
        } elseif ($attack === 'second funding') {
            $row = (array) DB::table('primary_campaign_fundings')->sole();
            DB::table('primary_campaign_fundings')->insert([...$row, 'id' => strtolower((string) Str::ulid())]);
        } elseif ($attack === 'duplicate member') {
            $row = (array) DB::table('primary_funding_commitments')->orderBy('reservation_id')->first();
            DB::table('primary_funding_commitments')->insert($row);
        } elseif ($attack === 'delete member') {
            DB::table('primary_funding_commitments')->where('reservation_id', $ra->id)->delete();
        } elseif ($attack === 'update member') {
            DB::table('primary_funding_commitments')->where('reservation_id', $ra->id)->update(['wallet_id' => DB::table('primary_funding_commitments')->where('reservation_id', $rb->id)->value('wallet_id')]);
        } elseif ($attack === 'delete funding') {
            DB::table('primary_campaign_fundings')->delete();
        } elseif ($attack === 'delete campaign') {
            DB::table('business_campaigns')->where('id', $this->campaign->id)->delete();
        }
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    });
    $message = 'ACCEPTED';
    try {
        $run();
    } catch (QueryException $exception) {
        $message = mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 170);
    }
    fwrite(STDERR, "\n[$attack] $message\n");
    expect($message)->not->toBe('ACCEPTED')->and(PrimaryCampaignFunding::query()->count())->toBe(1)
        ->and(DB::table('primary_funding_commitments')->count())->toBe(2);
})->with(['version released', 'version expired', 'version confirmed', 'version held', 'new root', 'second commitment', 'refund entry', 'release entry',
    'second funding', 'duplicate member', 'delete member', 'update member', 'delete funding', 'delete campaign']);

it('OBSERVE TRUNCATE is not covered by the row-level immutability triggers', function (string $table): void {
    ($this->soldOut)();
    ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::beginTransaction();
    $message = 'TRUNCATED';
    try {
        DB::statement("TRUNCATE $table CASCADE");
        $left = [PrimaryCampaignFunding::query()->count(), DB::table('primary_funding_commitments')->count()];
        $message .= ' left='.json_encode($left);
    } catch (QueryException $exception) {
        $message = mb_substr($exception->getMessage(), 0, 160);
    }
    DB::rollBack();
    fwrite(STDERR, "\n[truncate $table] $message\n");
    expect(true)->toBeTrue();
})->with(['primary_funding_commitments', 'primary_campaign_fundings']);

it('PROBE raw funding forgeries are refused in both modes', function (string $damage): void {
    [[$a, $ra], [$b, $rb]] = ($this->soldOut)();
    $other = null;
    if (in_array($damage, ['foreign extra member', 'foreign campaign id'], true)) {
        $otherCampaign = PrimaryReservationFixture::campaign();
        $c = PrimaryReservationFixture::investor();
        $result = $this->checkout->reserve($c['user']->id, 1, $otherCampaign->id, '1080', (string) Str::uuid(), PrimaryReservationFixture::terms(...));
        $rc = PrimaryReservationRecord::query()->whereKey($result['data']['reservation_id'])->sole();
        $version = PrimaryReservationVersion::query()->where('primary_reservation_id', $rc->id)->sole();
        expect($this->checkout->confirm($c['user']->id, 1, $otherCampaign->id, $rc->id, 1, $version->payload['terms']['disclosure_version'],
            $version->payload['disclosure_sha256'], (string) Str::uuid(), PrimaryReservationFixture::terms(...))['code'])->toBe('RESERVATION_CONFIRMED');
        $entries = LedgerEntry::query()->where('source_id', $rc->id)->get()->keyBy('kind');
        $other = ['campaign' => $otherCampaign, 'member' => ['commitment_id' => PrimaryCommitment::query()->where('primary_reservation_id', $rc->id)->sole()->id,
            'reservation_id' => $rc->id, 'hold_entry_id' => $entries['primary_hold']->id, 'commit_entry_id' => $entries['primary_commit']->id,
            'wallet_id' => $entries['primary_hold']->wallet_id, 'origin_operation_id' => $rc->origin_operation_id]];
    }
    [$record, $members] = ($this->captured)();
    $membersFirst = false;
    $immediate = false;
    switch ($damage) {
        case 'control': break;
        case 'control immediate first': $immediate = true; break;
        case 'members first': $membersFirst = true; break;
        case 'swapped commitments': [$members[0]['commitment_id'], $members[1]['commitment_id']] = [$members[1]['commitment_id'], $members[0]['commitment_id']]; break;
        case 'swapped holds': [$members[0]['hold_entry_id'], $members[1]['hold_entry_id']] = [$members[1]['hold_entry_id'], $members[0]['hold_entry_id']]; break;
        case 'swapped commits': [$members[0]['commit_entry_id'], $members[1]['commit_entry_id']] = [$members[1]['commit_entry_id'], $members[0]['commit_entry_id']]; break;
        case 'hold as commit and commit as hold': [$members[0]['hold_entry_id'], $members[0]['commit_entry_id']] = [$members[0]['commit_entry_id'], $members[0]['hold_entry_id']]; break;
        case 'foreign origin operation': $members[0]['origin_operation_id'] = $members[1]['origin_operation_id']; break;
        case 'wrong publication': $record['publication_sha256'] = str_repeat('a', 64); break;
        case 'wrong exposure': $record['exposure_reservation_id'] = strtolower((string) Str::ulid()); break;
        case 'wrong business': $record['business_id'] = DB::table('business_profiles')->where('id', '<>', $record['business_id'])->value('id') ?? strtolower((string) Str::ulid()); break;
        case 'smaller principal': $record['principal'] = '5400000'; break;
        case 'recorded before a confirmation': $record['created_at'] = now()->subSecond()->format('Y-m-d H:i:s.uP'); break;
        case 'recorded before live': $record['created_at'] = $this->campaign->live_at->subSecond()->format('Y-m-d H:i:s.uP'); break;
        case 'foreign extra member': $members[] = $other['member']; break;
        case 'foreign campaign id': $members = [$other['member'], $other['member']]; break;
        case 'garbage payload': $record['payload'] = 'not-encrypted'; $record['sha256'] = str_repeat('f', 64); break;
    }
    $message = 'ACCEPTED';
    try {
        ($this->forge)($record, $members, $immediate, $membersFirst);
    } catch (QueryException $exception) {
        $message = mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 170);
    }
    fwrite(STDERR, "\n[$damage] $message\n");
    if ($damage === 'control') {
        expect($message)->toBe('ACCEPTED')->and(app(CampaignFundingEvidence::class)->find($this->campaign->id))->not->toBeNull();
    } elseif ($damage === 'garbage payload') {
        $read = 'readable';
        try {
            app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
        } catch (Throwable $exception) {
            $read = $exception::class.': '.$exception->getMessage();
        }
        fwrite(STDERR, "[garbage payload] page read: $read\n");
        expect($message)->toBe('ACCEPTED');
    } else {
        expect($message)->not->toBe('ACCEPTED')->and(PrimaryCampaignFunding::query()->count())->toBe(0);
    }
})->with(['control', 'control immediate first', 'members first', 'swapped commitments', 'swapped holds', 'swapped commits', 'hold as commit and commit as hold',
    'foreign origin operation', 'wrong publication', 'wrong exposure', 'wrong business', 'smaller principal', 'recorded before a confirmation',
    'recorded before live', 'foreign extra member', 'foreign campaign id', 'garbage payload']);

it('OBSERVE the app lock under a caller that set constraints immediate', function (): void {
    ($this->soldOut)();
    DB::beginTransaction();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $message = 'funded';
    try {
        ($this->fund)();
    } catch (Throwable $exception) {
        $message = $exception::class.': '.mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 140);
    }
    DB::rollBack();
    fwrite(STDERR, "\n[immediate caller] $message\n");
    expect(true)->toBeTrue();
});

it('OBSERVE commitments are retained in reservation order, not commitment id order', function (): void {
    $a = PrimaryReservationFixture::investor();
    $b = PrimaryReservationFixture::investor();
    $ra = ($this->reserve)($a);
    $rb = ($this->reserve)($b);
    $this->travel(1)->seconds();
    expect(($this->confirm)($b, $rb))->toBe('RESERVATION_CONFIRMED');
    $this->travel(1)->seconds();
    expect(($this->confirm)($a, $ra))->toBe('RESERVATION_CONFIRMED');
    $funding = ($this->fund)();
    $reservationIds = array_column($funding['commitments'], 'reservation_id');
    $commitmentIds = array_column($funding['commitments'], 'commitment_id');
    $sortedCommitments = $commitmentIds;
    sort($sortedCommitments, SORT_STRING);
    $sortedReservations = $reservationIds;
    sort($sortedReservations, SORT_STRING);
    fwrite(STDERR, "\nreservation order ascending=".json_encode($reservationIds === $sortedReservations).' commitment ids ascending='.json_encode($commitmentIds === $sortedCommitments)."\n");
    fwrite(STDERR, 'first commitment keys='.json_encode(array_keys($funding['commitments'][0])).' units type='.get_debug_type($funding['commitments'][0]['units'])
        .' ordinals='.json_encode($funding['commitments'][0]['ordinals']).' top keys='.json_encode(array_keys($funding))."\n");
    expect($reservationIds)->toBe($sortedReservations)->and($commitmentIds)->not->toBe($sortedCommitments);
});

it('OBSERVE a funding clock behind the last confirmation surfaces as a raw database refusal', function (): void {
    $this->travel(10)->seconds();
    ($this->soldOut)();
    $this->travel(-2)->seconds();
    expect(now()->gt($this->campaign->live_at))->toBeTrue();
    $message = 'funded';
    try {
        ($this->fund)();
    } catch (Throwable $exception) {
        $message = $exception::class.': '.mb_substr(preg_replace('/\s+/', ' ', $exception->getMessage()), 0, 150);
    }
    fwrite(STDERR, "\n[clock behind] $message\n");
    expect(PrimaryCampaignFunding::query()->count())->toBe(0);
});

it('PROBE one Party with two purchases and a third Party fund together once', function (): void {
    $a = PrimaryReservationFixture::investor();
    $b = PrimaryReservationFixture::investor();
    $c = PrimaryReservationFixture::investor();
    foreach ([[$a, '540'], [$b, '1080'], [$a, '270'], [$c, '270']] as [$investor, $units]) {
        expect(($this->confirm)($investor, ($this->reserve)($investor, $units)))->toBe('RESERVATION_CONFIRMED');
    }
    $funding = ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    expect($funding['commitments'])->toHaveCount(4)->and(array_sum(array_map(intval(...), array_column($funding['commitments'], 'principal'))))->toBe(10800000)
        ->and(DB::table('primary_funding_commitments')->distinct()->count('wallet_id'))->toBe(3)
        ->and(($this->fund)())->toBe($funding);
});

it('OBSERVE the read cost of the funded page and the expiry sweep revisiting funded campaigns', function (): void {
    ($this->soldOut)();
    $count = function (Closure $work): int {
        $n = 0;
        DB::listen(function () use (&$n): void {
            $n++;
        });
        $start = $n;
        $work();

        return $n - $start;
    };
    $page = fn () => app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id);
    $soldOut = $count($page);
    ($this->fund)();
    $funded = $count($page);
    $this->travelTo($this->campaign->expires_at->addDay());
    $sweeps = [app(BusinessCampaignStore::class)->expireDue(10), app(BusinessCampaignStore::class)->expireDue(10)];
    $due = DB::table('business_campaigns')->where('expires_at', '<=', now())->whereNotIn('id', DB::table('business_campaign_closures')->select('business_campaign_id'))->count();
    fwrite(STDERR, "\nqueries per page (cumulative listeners skew upward): sold out=$soldOut funded=$funded; sweeps=".json_encode($sweeps)." still-due candidates=$due\n");
    expect($sweeps)->toBe([0, 0])->and($due)->toBe(1);
});

it('PROBE a real moving clock with microseconds round-trips the record, the page and a retry', function (): void {
    Illuminate\Support\Carbon::setTestNow();
    usleep(1_100_000);
    ($this->soldOut)();
    usleep(3000);
    $funding = ($this->fund)();
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    $stored = DB::table('primary_campaign_fundings')->value('created_at');
    fwrite(STDERR, "\nrecorded_at=".$funding['recorded_at'].' stored='.$stored."\n");
    expect(substr($funding['recorded_at'], -7, 6))->not->toBe('000000')
        ->and(app(CampaignFundingEvidence::class)->find($this->campaign->id))->toBe($funding)
        ->and(($this->fund)())->toBe($funding)
        ->and(app(BusinessCampaignStore::class)->campaign($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id)['lifecycle'])->toBe('funded_pending_disbursement');
});
