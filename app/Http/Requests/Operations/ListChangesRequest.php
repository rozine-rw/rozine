<?php

declare(strict_types=1);

namespace App\Http\Requests\Operations;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A beacon read: `topics` (comma-separated) and the opaque `after` cursor. The staff route reads
 * staff queues only; the member routes read the Party's and its Businesses' topics. An API token
 * needs the read ability of every audience it asks about.
 */
class ListChangesRequest extends FormRequest
{
    /** The read ability an API token needs for each topic. */
    private const array ABILITIES = ['wallet' => 'investor:read', 'purchase' => 'investor:read', 'campaign' => 'business:read', 'staff_queue' => 'staff:applications:read'];

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (! $this->routeIs('api.*') || array_all($this->requestedTopics(),
            fn (string $topic): bool => ! isset(self::ABILITIES[$topic]) || $user->tokenCan(self::ABILITIES[$topic])));
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $allowed = implode('|', $this->allowedTopics());

        return ['topics' => ['required', 'string', 'max:100', 'regex:/^('.$allowed.')(,('.$allowed.'))*$/D'],
            'after' => ['sometimes', 'string', 'max:100']];
    }

    /** @return list<string> */
    public function topics(): array
    {
        return array_values(array_unique(explode(',', (string) $this->validated('topics'))));
    }

    /** @return list<string> */
    private function allowedTopics(): array
    {
        return $this->routeIs('staff.*') ? ['staff_queue'] : ['wallet', 'purchase', 'campaign'];
    }

    /** @return list<string> */
    private function requestedTopics(): array
    {
        return explode(',', is_string($this->query('topics')) ? $this->query('topics') : '');
    }
}
