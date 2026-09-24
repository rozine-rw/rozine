<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\RecordConsentRelease;
use App\Domain\Identity\ConsentDocuments;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * @phpstan-import-type DocumentInput from ConsentDocuments
 * @phpstan-import-type DisclosureInput from ConsentDocuments
 */
final class ConsentFixture
{
    public static function staff(string $role = 'compliance'): User
    {
        $user = User::factory()->withTwoFactor()->create();
        app(ConfigureStaffAccess::class)->handle($user->id, true, 'Record reviewed consent documents.', (string) Str::uuid(), [$role]);

        return $user;
    }

    /** @return array{DocumentInput, DocumentInput} */
    public static function documents(string $version = 'synthetic-1'): array
    {
        return [
            ['kind' => 'terms', 'version' => $version, 'body' => 'Synthetic terms. Test use only.',
                'summary' => [['heading' => 'Test terms', 'body' => 'Not approved legal terms.']]],
            ['kind' => 'privacy', 'version' => $version, 'body' => 'Synthetic privacy text. Test use only.',
                'summary' => [['heading' => 'Test privacy', 'body' => 'Not an approved privacy notice.']]],
        ];
    }

    /** @return array{DisclosureInput, DisclosureInput} */
    public static function disclosures(string $version = 'synthetic-1'): array
    {
        return [
            ['key' => 'test-disclosure', 'version' => $version, 'text' => 'Synthetic disclosure. Not approved for participants.'],
            ['key' => 'another-disclosure', 'version' => $version, 'text' => 'Another isolated disclosure.'],
        ];
    }

    /** @return array<string, mixed> */
    public static function record(User $staff, int $revision = 0, ?string $requestId = null): array
    {
        return app(RecordConsentRelease::class)->handle($staff->id, $revision, 'active',
            self::documents(), self::disclosures(), true, 'fixture:not-legal-approval', 'Isolated consent fixture.', $requestId ?? (string) Str::uuid());
    }
}
