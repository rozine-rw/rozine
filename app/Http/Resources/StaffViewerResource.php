<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in operator as the console frame names them: name, email, initials and their most
 * privileged role. The resource is the operator's list of staff roles.
 */
class StaffViewerResource extends JsonResource
{
    /** Staff roles from most to least privileged. */
    public const ROLES = ['superadmin', 'compliance', 'approver', 'treasury', 'analyst'];

    /** @return array{id: string, name: string, email: string, initials: string, role: string} */
    public function toArray(Request $request): array
    {
        /** @var list<string> $roles */
        $roles = $this->resource;
        $name = (string) $request->user()?->name;

        return ['id' => (string) $request->user()?->getAuthIdentifier(), 'name' => $name, 'email' => (string) $request->user()?->email,
            'initials' => self::initials($name), 'role' => self::role($roles) ?? 'analyst'];
    }

    /**
     * The most privileged of these roles, or null when there is none.
     *
     * @param  list<string>  $roles
     */
    public static function role(array $roles): ?string
    {
        return array_values(array_intersect(self::ROLES, $roles))[0] ?? null;
    }

    /** The first letters of the first two words of a name, as the design's avatars show them ("A. Diane" is AD). */
    public static function initials(string $name): string
    {
        $words = preg_split('/[\s.\-]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(implode('', array_map(fn (string $word): string => mb_substr($word, 0, 1), array_slice($words, 0, 2))));
    }
}
