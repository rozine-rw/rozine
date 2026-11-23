<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Application\Identity\AcceptLegalDocuments;
use App\Application\Identity\Contracts\LegalConsents;
use App\Models\Party;
use App\Models\VerifiedPersonIdentity;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /** Whether a person created here has agreed to the current legal documents, as everyone who signs up has. */
    private bool $agreedToLegalDocuments = true;

    /**
     * Records the sign-up consents of each person created, unless the factory says they have not agreed.
     *
     * @param  Collection<int, Model>  $instances
     */
    protected function callAfterCreating(Collection $instances, ?Model $parent = null): void
    {
        parent::callAfterCreating($instances, $parent);
        if (! $this->agreedToLegalDocuments) {
            return;
        }
        foreach ($instances as $party) {
            if ($party instanceof Party && $party->kind === 'person') {
                app(LegalConsents::class)->record($party->id, app(AcceptLegalDocuments::class)->agreed(), 'sign_up');
            }
        }
    }

    /** @param array<string, mixed> $arguments */
    protected function newInstance(array $arguments = []): static
    {
        $factory = parent::newInstance($arguments);
        $factory->agreedToLegalDocuments = $this->agreedToLegalDocuments;

        return $factory;
    }

    /** A person who has not yet agreed to the current Terms, Privacy Notice and Disclaimers. */
    public function withoutLegalConsent(): static
    {
        $factory = clone $this;
        $factory->agreedToLegalDocuments = false;

        return $factory;
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kind' => 'person',
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['verified_at' => now()])->afterCreating(function (Party $party): void {
            if ($party->kind === 'person') {
                VerifiedPersonIdentity::factory()->create(['party_id' => $party->id]);
            }
        });
    }
}
