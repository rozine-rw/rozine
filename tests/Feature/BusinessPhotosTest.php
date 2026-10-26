<?php

declare(strict_types=1);

use App\Application\Business\Contracts\BusinessPhotos;
use App\Models\BusinessPhoto;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\InvestorWalletFixture;
use Tests\Support\PrimaryReservationFixture;

beforeEach(function (): void {
    $this->freezeSecond();
    InvestorWalletFixture::policy(maximum: null);
    $this->campaign = PrimaryReservationFixture::campaign();
    $this->investor = PrimaryReservationFixture::investor();
    $this->photos = app(BusinessPhotos::class);
    $this->jpeg = "\xFF\xD8\xFF\xE0".str_repeat('a', 64);
    $this->png = "\x89PNG\r\n\x1A\n".str_repeat('b', 64);
    $this->webp = 'RIFF'.pack('V', 68).'WEBP'.str_repeat('c', 60);
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
            ['id' => $first['id'], 'caption' => 'Layer house', 'sha256' => hash('sha256', $this->png)],
            ['id' => BusinessPhoto::query()->where('position', 2)->value('id'), 'caption' => 'Egg room', 'sha256' => hash('sha256', $this->webp)],
        ]);
});

it('refuses an unknown business and a photo outside the allowed position, caption, type or size', function (string $business, int $position, string $caption, string $type, ?int $size, string $reason): void {
    $businessId = $business === 'known' ? $this->campaign->business_id : '01JZZZZZZZZZZZZZZZZZZZZZZZ';

    expect(fn () => $this->photos->put($businessId, $position, $caption, $type, match ($size) { null => $this->jpeg, 0 => '', default => str_pad($this->jpeg, $size, 'a') }))
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

it('refuses bytes that are not the image type they are labelled as', function (string $type, string $bytes): void {
    expect(fn () => $this->photos->put($this->campaign->business_id, 1, 'Shop', $type, $bytes))
        ->toThrow(InvalidArgumentException::class, 'BUSINESS_PHOTO_INVALID');
})->with([
    'text as JPEG' => ['image/jpeg', '<?php echo 1;'],
    'PNG as JPEG' => ['image/jpeg', "\x89PNG\r\n\x1A\nxxxx"],
    'JPEG as PNG' => ['image/png', "\xFF\xD8\xFF\xE0xxxx"],
    'RIFF audio as WebP' => ['image/webp', 'RIFF'.pack('V', 12).'WAVExxxx'],
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
