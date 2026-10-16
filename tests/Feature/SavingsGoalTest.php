<?php

use App\Models\SavingsGoal;
use App\Models\User;

test('guest cannot access savings goals or record goal movements', function () {
    $goal = SavingsGoal::factory()->create();

    $this->get(route('savings-goals.index'))->assertRedirect(route('login'));
    $this->get(route('savings-goals.create'))->assertRedirect(route('login'));
    $this->get(route('savings-goals.show', $goal))->assertRedirect(route('login'));
    $this->post(route('savings-goals.entries.store', $goal), [])->assertRedirect(route('login'));
});

test('unverified users cannot access savings goals', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get(route('savings-goals.index'))
        ->assertRedirect(route('verification.notice'));
});

test('savings goals list contains only the authenticated users goals', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownGoal = SavingsGoal::factory()->for($user)->create();
    SavingsGoal::factory()->for($otherUser)->create();

    $this->actingAs($user)->get(route('savings-goals.index'))
        ->assertInertia(fn ($page) => $page
            ->component('savings-goals/Index')
            ->has('goals', 1)
            ->where('goals.0.id', $ownGoal->id));
});

test('verified user can create view update and soft delete a goal while retaining its entries', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('savings-goals.index'))
        ->assertInertia(fn ($page) => $page->component('savings-goals/Index')->has('goals', 0));

    $this->get(route('savings-goals.create'))
        ->assertInertia(fn ($page) => $page->component('savings-goals/Create')->where('today', now()->toDateString()));

    $this->post(route('savings-goals.store'), [
        'name' => 'Emergency fund',
        'target_amount' => '1000.00',
        'target_date' => '2027-01-31',
        'description' => 'Unexpected costs',
    ])->assertRedirect();

    $goal = SavingsGoal::query()->where('user_id', $user->id)->firstOrFail();
    $entry = $goal->entries()->create([
        'type' => 'contribution',
        'amount' => '125.50',
        'entry_date' => '2026-10-15',
        'note' => 'First deposit',
    ]);

    $this->get(route('savings-goals.show', $goal))
        ->assertInertia(fn ($page) => $page
            ->component('savings-goals/Show')
            ->where('goal.current_amount', '125.50')
            ->where('goal.remaining_amount', '874.50')
            ->where('goal.target_date', '2027-01-31')
            ->where('entries.data.0.entry_date', '2026-10-15')
            ->where('entries.data.0.note', 'First deposit'));

    $this->get(route('savings-goals.edit', $goal))
        ->assertInertia(fn ($page) => $page
            ->component('savings-goals/Edit')
            ->where('goal.target_amount', '1000.00'));

    $this->put(route('savings-goals.update', $goal), [
        'name' => 'Emergency reserve',
        'target_amount' => '1200.00',
        'target_date' => '2027-02-28',
        'description' => 'Updated description',
    ])->assertRedirect(route('savings-goals.show', $goal));

    $this->assertDatabaseHas('savings_goals', ['id' => $goal->id, 'name' => 'Emergency reserve', 'target_amount' => '1200.00']);

    $this->delete(route('savings-goals.destroy', $goal))->assertRedirect(route('savings-goals.index'));

    $this->assertSoftDeleted('savings_goals', ['id' => $goal->id]);
    $this->assertModelExists($entry);
    $this->get(route('savings-goals.index'))->assertInertia(fn ($page) => $page->has('goals', 0));
    $this->get(route('savings-goals.show', $goal))->assertNotFound();
});

test('goal validation rejects missing values invalid amounts and malformed or past create dates', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $this->actingAs(User::factory()->create());

    $this->post(route('savings-goals.store'), [])->assertSessionHasErrors(['name', 'target_amount']);

    foreach (['0', '-1.00', '10000000000.00', '1.999'] as $amount) {
        $this->post(route('savings-goals.store'), [
            'name' => 'Savings goal',
            'target_amount' => $amount,
            'target_date' => '2026-10-14',
        ])->assertSessionHasErrors(['target_amount', 'target_date']);
    }

    $this->post(route('savings-goals.store'), [
        'name' => 'Savings goal', 'target_amount' => '10.00', 'target_date' => 'next week',
    ])->assertSessionHasErrors('target_date');
});

test('users cannot view update delete or add movements to another users goal', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $goal = SavingsGoal::factory()->for($owner)->create();

    $this->actingAs($otherUser);
    $this->get(route('savings-goals.show', $goal))->assertForbidden();
    $this->get(route('savings-goals.edit', $goal))->assertForbidden();
    $this->put(route('savings-goals.update', $goal), ['name' => 'Changed'])->assertForbidden();
    $this->delete(route('savings-goals.destroy', $goal))->assertForbidden();
    $this->post(route('savings-goals.entries.store', $goal), [
        'type' => 'contribution', 'amount' => '5.00', 'entry_date' => now()->toDateString(),
    ])->assertForbidden();

    $this->assertDatabaseCount('savings_goal_entries', 0);
});

test('contributions can exceed the target while progress remains capped and the recorded amount remains accurate', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $goal = SavingsGoal::factory()->for(User::factory())->create(['target_amount' => '100.00']);
    $this->actingAs($goal->user);

    $this->post(route('savings-goals.entries.store', $goal), [
        'type' => 'contribution', 'amount' => '125.75', 'entry_date' => '2026-10-15', 'note' => 'Extra saving',
    ])->assertRedirect(route('savings-goals.show', $goal));

    $this->assertDatabaseHas('savings_goal_entries', ['savings_goal_id' => $goal->id, 'amount' => '125.75']);
    $this->get(route('savings-goals.show', $goal))
        ->assertInertia(fn ($page) => $page
            ->where('goal.current_amount', '125.75')
            ->where('goal.remaining_amount', '0.00')
            ->where('goal.progress_percentage', 100)
            ->where('goal.is_complete', true)
            ->where('goal.monthly_required_amount', '0.00'));
});

test('ledger totals preserve cents when the SQLite test database returns a floating aggregate', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $goal = SavingsGoal::factory()->for(User::factory())->create(['target_amount' => '1.00']);
    $this->actingAs($goal->user);

    foreach (['0.10', '0.20'] as $amount) {
        $this->post(route('savings-goals.entries.store', $goal), [
            'type' => 'contribution', 'amount' => $amount, 'entry_date' => '2026-10-15',
        ])->assertRedirect(route('savings-goals.show', $goal));
    }

    $this->get(route('savings-goals.show', $goal))
        ->assertInertia(fn ($page) => $page
            ->where('goal.current_amount', '0.30')
            ->where('goal.remaining_amount', '0.70'));
});

test('withdrawals are recorded only when enough funds are available and entries cannot be edited or deleted', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $goal = SavingsGoal::factory()->for(User::factory())->create(['target_amount' => '500.00']);
    $this->actingAs($goal->user);
    $contribution = $goal->entries()->create([
        'type' => 'contribution', 'amount' => '80.00', 'entry_date' => '2026-10-14',
    ]);

    $this->post(route('savings-goals.entries.store', $goal), [
        'type' => 'withdrawal', 'amount' => '80.01', 'entry_date' => '2026-10-15',
    ])->assertSessionHasErrors('amount');
    $this->assertDatabaseCount('savings_goal_entries', 1);

    $this->post(route('savings-goals.entries.store', $goal), [
        'type' => 'withdrawal', 'amount' => '30.25', 'entry_date' => '2026-10-15',
    ])->assertRedirect(route('savings-goals.show', $goal));

    $withdrawal = $goal->entries()->where('type', 'withdrawal')->firstOrFail();
    expect($contribution->update(['amount' => '1.00']))->toBeFalse();
    expect($contribution->delete())->toBeFalse();

    $this->get(route('savings-goals.show', $goal))
        ->assertInertia(fn ($page) => $page
            ->where('goal.current_amount', '49.75')
            ->where('entries.data.0.id', $withdrawal->id));
});

test('entry validation rejects invalid type amount and future or malformed dates', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $goal = SavingsGoal::factory()->for(User::factory())->create();
    $this->actingAs($goal->user);

    $this->post(route('savings-goals.entries.store', $goal), [
        'type' => 'transfer', 'amount' => '0', 'entry_date' => 'tomorrow',
    ])->assertSessionHasErrors(['type', 'amount', 'entry_date']);

    $this->post(route('savings-goals.entries.store', $goal), [
        'type' => 'contribution', 'amount' => '10.00', 'entry_date' => '2026-10-16',
    ])->assertSessionHasErrors('entry_date');
});

test('monthly required amount counts current and target calendar months and rounds up to cents', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $goal = SavingsGoal::factory()->for(User::factory())->create([
        'target_amount' => '100.00', 'target_date' => '2026-12-01',
    ]);
    $this->actingAs($goal->user);

    $this->get(route('savings-goals.show', $goal))
        ->assertInertia(fn ($page) => $page
            ->where('goal.months_remaining', 3)
            ->where('goal.monthly_required_amount', '33.34'));
});

test('goal without a date has no monthly estimate and an incomplete goal past its date is overdue', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->startOfDay());
    $user = User::factory()->create();
    $undatedGoal = SavingsGoal::factory()->for($user)->create(['target_amount' => '100.00', 'target_date' => null]);
    $overdueGoal = SavingsGoal::factory()->for($user)->create(['target_amount' => '100.00', 'target_date' => '2026-10-14']);
    $this->actingAs($user);

    $this->get(route('savings-goals.show', $undatedGoal))
        ->assertInertia(fn ($page) => $page
            ->where('goal.monthly_required_amount', null)
            ->where('goal.is_overdue', false));
    $this->get(route('savings-goals.show', $overdueGoal))
        ->assertInertia(fn ($page) => $page
            ->where('goal.monthly_required_amount', null)
            ->where('goal.is_overdue', true));
});
