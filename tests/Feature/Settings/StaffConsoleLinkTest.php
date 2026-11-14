<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\StaffFixture;

/*
 * Account settings use the shared settings layout, outside the Admin console (#618), so staff are
 * sent their way back to it there. Nobody else gets the link, and no other page carries it.
 */

beforeEach(function (): void {
    $this->withoutVite();
});

it('links staff back to the console from every settings page, and nobody else', function (): void {
    $staff = User::factory()->withTwoFactor()->create();
    StaffFixture::provision($staff, ['analyst'], 'Settings link.');
    $console = ['url' => '/admin/dashboard', 'method' => 'get'];

    $this->actingAs($staff)->withSession(['auth.password_confirmed_at' => time()]);
    foreach (['profile.edit', 'security.edit', 'appearance.edit'] as $route) {
        $this->get(route($route))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('staff_console', $console));
    }
    $this->get(route('staff.dashboard'))->assertOk()->assertInertia(fn (Assert $page): Assert => $page->where('staff_console', null));

    $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page->where('staff_console', null));
});
