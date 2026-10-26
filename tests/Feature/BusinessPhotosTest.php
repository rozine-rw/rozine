<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessCampaignStore;
use App\Application\Business\Contracts\BusinessPhotos;
use App\Domain\Operations\CommandRejection;
use App\Models\BusinessPhoto;
use App\Models\Party;
use App\Models\StaffAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PhotoBytesFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->photos = app(BusinessPhotos::class);
    $this->jpeg = PhotoBytesFixture::jpeg();
    $this->png = PhotoBytesFixture::png();
    $this->webp = PhotoBytesFixture::webp();
});

it('puts a photo at a position, changes nothing when put again and replaces it with new bytes', function (): void {
    $first = $this->photos->put($this->campaign->business_id, 1, '  Layer house  ', 'image/jpeg', $this->jpeg);
    $again = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
    $replaced = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/png', $this->png);
    $this->photos->put($this->campaign->business_id, 2, 'Egg room', 'image/webp', $this->webp);

    expect($first['changed'])->toBeTrue()
        ->and($again)->toBe(['id' => $first['id'], 'changed' => false])
        ->and($replaced)->toBe(['id' => $first['id'], 'changed' => true])
        ->and(BusinessPhoto::query()->count())->toBe(2)
        ->and($this->photos->published($this->campaign->business_id))->toBe([
            ['id' => $first['id'], 'caption' => 'Layer house', 'sha256' => hash('sha256', $this->png), 'url' => null],
            ['id' => BusinessPhoto::query()->where('position', 2)->value('id'), 'caption' => 'Egg room', 'sha256' => hash('sha256', $this->webp), 'url' => null],
        ]);
});

it('refuses an unknown business and a photo outside the allowed position, caption, type or size', function (string $business, int $position, string $caption, string $type, ?int $size, string $reason): void {
    $businessId = $business === 'known' ? $this->campaign->business_id : '01JZZZZZZZZZZZZZZZZZZZZZZZ';

    expect(fn () => $this->photos->put($businessId, $position, $caption, $type, match ($size) {
        null => $this->jpeg, 0 => '', default => str_pad($this->jpeg, $size, 'a')
    }))
        ->toThrow(InvalidArgumentException::class, $reason);
})->with([
    'unknown business' => ['unknown', 1, 'Shop', 'image/jpeg', null, 'BUSINESS_PHOTO_BUSINESS_UNKNOWN'],
    'position zero' => ['known', 0, 'Shop', 'image/jpeg', null, 'BUSINESS_PHOTO_INVALID'],
    'position seven' => ['known', 7, 'Shop', 'image/jpeg', null, 'BUSINESS_PHOTO_INVALID'],
    'blank caption' => ['known', 1, '   ', 'image/jpeg', null, 'BUSINESS_PHOTO_INVALID'],
    'long caption' => ['known', 1, str_repeat('x', 81), 'image/jpeg', null, 'BUSINESS_PHOTO_INVALID'],
    'gif' => ['known', 1, 'Shop', 'image/gif', null, 'BUSINESS_PHOTO_INVALID'],
    'empty file' => ['known', 1, 'Shop', 'image/jpeg', 0, 'BUSINESS_PHOTO_INVALID'],
    'over 5 MB' => ['known', 1, 'Shop', 'image/jpeg', BusinessPhotos::MAX_BYTES + 1, 'BUSINESS_PHOTO_INVALID'],
]);

it('refuses bytes that are not a whole image of the type they are labelled as', function (string $type, string $bytes): void {
    expect(fn () => $this->photos->put($this->campaign->business_id, 1, 'Shop', $type, $bytes))
        ->toThrow(InvalidArgumentException::class, 'BUSINESS_PHOTO_INVALID');
})->with([
    'text as JPEG' => ['image/jpeg', '<?php echo 1;'],
    'PNG as JPEG' => ['image/jpeg', PhotoBytesFixture::png()],
    'JPEG as PNG' => ['image/png', PhotoBytesFixture::jpeg()],
    'RIFF audio as WebP' => ['image/webp', 'RIFF'.pack('V', 12).'WAVExxxx'],
    'JPEG signature on garbage' => ['image/jpeg', "\xFF\xD8\xFF\xE0".str_repeat('a', 64)."\xFF\xD9"],
    'PNG signature on garbage' => ['image/png', "\x89PNG\r\n\x1A\n".str_repeat('b', 64)],
    'WebP signature on garbage' => ['image/webp', 'RIFF'.pack('V', 68).'WEBP'.str_repeat('c', 60)],
    'JPEG cut short' => ['image/jpeg', substr(PhotoBytesFixture::jpeg(), 0, -3)],
    'PNG cut short' => ['image/png', substr(PhotoBytesFixture::png(), 0, -1)],
    'WebP cut short' => ['image/webp', substr(PhotoBytesFixture::webp(), 0, -1)],
]);

it('shows the published photos on the deal card and the deal, and serves each one', function (): void {
    $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
    $url = "/investor/deals/{$this->campaign->id}/photos/{$photo['id']}?v=".substr(hash('sha256', $this->jpeg), 0, 12);
    $user = $this->investor['user'];

    $deck = $this->actingAs($user)->get(route('investor.deals'))->assertOk()->viewData('page')['props'];
    $deal = $this->actingAs($user)->get(route('investor.deals.show', ['campaign' => $this->campaign->id]))->assertOk()->viewData('page')['props'];
    $response = $this->actingAs($user)->get($url)->assertOk();

    expect($deck['deals'][0]['photos'])->toBe([['url' => $url, 'caption' => 'Layer house']])
        ->and($deal['deal']['photos'])->toBe([['url' => $url, 'caption' => 'Layer house']])
        ->and($response->getContent())->toBe($this->jpeg)
        ->and($response->headers->get('Content-Type'))->toBe('image/jpeg')
        ->and($response->headers->get('ETag'))->toBe('"'.hash('sha256', $this->jpeg).'"')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=86400');
});

it('does not serve a photo through another business\'s campaign, an unknown campaign or to a guest', function (): void {
    $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
    $this->travel(1)->minute();
    $other = PrimaryReservationFixture::campaign();
    $user = $this->investor['user'];

    $this->actingAs($user)->get(route('investor.deals.photo', ['campaign' => $other->id, 'photo' => $photo['id']]))->assertNotFound();
    $this->actingAs($user)->get(route('investor.deals.photo', ['campaign' => '01JZZZZZZZZZZZZZZZZZZZZZZZ', 'photo' => $photo['id']]))->assertNotFound();
    auth()->logout();
    $this->get(route('investor.deals.photo', ['campaign' => $this->campaign->id, 'photo' => $photo['id']]))->assertRedirect(route('login'));
});

it('serves photos to whoever may read the deals, and only while the deal is open', function (): void {
    $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
    $url = route('investor.deals.photo', ['campaign' => $this->campaign->id, 'photo' => $photo['id']]);
    $staff = User::factory()->withTwoFactor()->create();
    StaffAccount::factory()->create(['user_id' => $staff->id]);

    $this->actingAs(User::factory()->create(['party_id' => Party::factory()]))->get($url)->assertOk();
    $this->actingAs(User::query()->findOrFail($this->campaign->actor_user_id))->getJson($url)->assertForbidden();
    $this->actingAs($staff)->getJson($url)->assertForbidden()->assertJsonPath('code', 'IDENTITY_NOT_LINKED');
    $this->actingAs(User::factory()->create(['party_id' => Party::factory()->verified()]))->getJson($url)->assertForbidden();

    app(BusinessCampaignStore::class)->cancel($this->campaign->actor_user_id, 1, $this->campaign->business_id, $this->campaign->id, 1, 'Changed plans.',
        (string) Str::uuid());
    $this->actingAs($this->investor['user'])->getJson($url)->assertNotFound()->assertJsonPath('code', 'BUSINESS_PHOTO_NOT_FOUND');
});

it('refuses to serve a photo whose bytes no longer match their digest', function (string $content): void {
    $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
    DB::table('business_photos')->where('id', $photo['id'])->update(['content' => $content]);

    $this->actingAs($this->investor['user'])->get(route('investor.deals.photo', ['campaign' => $this->campaign->id, 'photo' => $photo['id']]))
        ->assertConflict();
})->with(['other bytes' => [base64_encode('other')], 'not base64' => ['%%%']]);

it('keeps the photo rules in the database too', function (array $change): void {
    $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);

    expect(fn () => DB::table('business_photos')->where('id', $photo['id'])->update($change))->toThrow(QueryException::class);
})->with([
    'position' => [['position' => 7]],
    'caption' => [['caption' => ' ']],
    'media type' => [['media_type' => 'image/gif']],
    'size' => [['size_bytes' => 0]],
]);

describe('on the public media disk', function (): void {
    beforeEach(function (): void {
        $this->mediaRoot = storage_path('framework/testing/media-'.uniqid());
        config(['filesystems.disks.media-test' => ['driver' => 'local', 'root' => $this->mediaRoot, 'url' => 'https://media.test', 'throw' => true]]);
    });

    afterEach(function (): void {
        File::deleteDirectory($this->mediaRoot);
    });

    it('puts a photo on the media disk under an unguessable name and shows its public URL on the deal', function (): void {
        config(['filesystems.media_disk' => 'media-test']);
        $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
        $row = BusinessPhoto::query()->findOrFail($photo['id']);
        $path = Str::after((string) $row->url, 'https://media.test/');

        expect($row->content)->toBeNull()
            ->and($path)->toMatch('#^'.$this->campaign->business_id.'/[0-9a-z]{26}\.jpg$#')
            ->and(File::get($this->mediaRoot.'/'.$path))->toBe($this->jpeg)
            ->and($this->photos->published($this->campaign->business_id))
            ->toBe([['id' => $photo['id'], 'caption' => 'Layer house', 'sha256' => hash('sha256', $this->jpeg), 'url' => $row->url]]);

        $deal = $this->actingAs($this->investor['user'])->get(route('investor.deals.show', ['campaign' => $this->campaign->id]))
            ->assertOk()->viewData('page')['props']['deal'];
        expect($deal['photos'])->toBe([['url' => $row->url, 'caption' => 'Layer house']]);
        $this->get(route('investor.deals.photo', ['campaign' => $this->campaign->id, 'photo' => $photo['id']]))->assertNotFound();
    });

    it('moves the photos kept in rows onto the media disk with the files command', function (): void {
        $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
        expect($this->photos->relocate())->toBe(0);
        config(['filesystems.media_disk' => 'media-test']);

        expect(Artisan::call('files:move', ['--dry-run' => true]))->toBe(0)
            ->and(Artisan::output())->toContain('business_photos: 1')
            ->and(BusinessPhoto::query()->findOrFail($photo['id'])->url)->toBeNull();

        expect(Artisan::call('files:move'))->toBe(0)
            ->and(Artisan::output())->toContain('business_photos: 1');
        $row = BusinessPhoto::query()->findOrFail($photo['id']);
        expect($row->content)->toBeNull()
            ->and(File::get($this->mediaRoot.'/'.Str::after((string) $row->url, 'https://media.test/')))->toBe($this->jpeg)
            ->and($this->photos->relocate())->toBe(0);
    });

    it('refuses to move a photo whose bytes no longer match their digest', function (): void {
        $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);
        DB::table('business_photos')->where('id', $photo['id'])->update(['content' => base64_encode('other')]);
        config(['filesystems.media_disk' => 'media-test']);

        expect(fn () => $this->photos->relocate())->toThrow(CommandRejection::class, 'BUSINESS_PHOTO_INTEGRITY_FAILED');
    });

    it('keeps exactly one of the bytes and the URL in the database', function (array $change): void {
        $photo = $this->photos->put($this->campaign->business_id, 1, 'Layer house', 'image/jpeg', $this->jpeg);

        expect(fn () => DB::table('business_photos')->where('id', $photo['id'])->update($change))->toThrow(QueryException::class);
    })->with([
        'neither' => [['content' => null]],
        'both' => [['url' => 'https://media.test/x.jpg']],
        'not a web address' => [['content' => null, 'url' => 'ftp://media.test/x.jpg']],
    ]);
});
