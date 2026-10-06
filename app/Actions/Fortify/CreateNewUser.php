<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Application\Identity\RegisterIdentity;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
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
        ])->validate();

        return User::query()->findOrFail($this->registerIdentity->handle(
            $input['name'],
            $input['email'],
            $input['password'],
        ));
    }
}
