<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Application\Identity\RegisterIdentity;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Domain\Identity\SandboxNotice;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private RegisterIdentity $registerIdentity) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'sandbox_notice_version' => ['required', 'string', Rule::in([SandboxNotice::VERSION])],
            'sandbox_notice_sha256' => ['required', 'string', Rule::in([SandboxNotice::sha256()])],
        ], array_fill_keys(['sandbox_notice_version.required', 'sandbox_notice_version.string', 'sandbox_notice_version.in',
            'sandbox_notice_sha256.required', 'sandbox_notice_sha256.string', 'sandbox_notice_sha256.in'],
            'Read and acknowledge the sandbox notice before you sign up.'))->validate();

        return User::query()->findOrFail($this->registerIdentity->handle(
            $input['name'],
            $input['email'],
            $input['password'],
            $input['sandbox_notice_version'],
            $input['sandbox_notice_sha256'],
        ));
    }
}
