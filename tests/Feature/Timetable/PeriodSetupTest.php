<?php

namespace Tests\Feature\Timetable;

use App\Models\Tt\BreakPeriod;
use App\Models\Tt\Period;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

/**
 * "How many periods in a day?" — the one number the whole grid layout is built from.
 *
 * Growing the day is always safe. Shrinking it is not: the cards sitting on the periods
 * about to be deleted would go with them, silently. That refusal, and the names it puts
 * on what is in the way, is most of what this suite is here to hold down.
 */
class PeriodSetupTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSchool();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_merge([
            'setting_id' => $this->setting->id,
            'periods_per_day' => 8,
            'days_per_cycle' => 6,
            'first_period_start' => '07:30',
            'period_minutes' => 40,
        ], $overrides);
    }

    // -----------------------------------------------------------------
    // Laying out a day
    // -----------------------------------------------------------------

    public function test_n_periods_generates_n_rows_with_times_running_back_to_back(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form([
                'periods_per_day' => 6,
                'first_period_start' => '07:00',
                'period_minutes' => 45,
            ]))
            ->assertRedirect(route('admin.timetable.index', ['setting' => $this->setting->id]))
            ->assertSessionHas('success');

        $periods = Period::where('tt_setting_id', $this->setting->id)
            ->orderBy('period_number')->get();

        $this->assertCount(6, $periods);
        $this->assertSame([1, 2, 3, 4, 5, 6], $periods->pluck('period_number')->map(fn ($n) => (int) $n)->all());

        $this->assertSame('07:00:00', (string) $periods[0]->start_time);
        $this->assertSame('07:45:00', (string) $periods[0]->end_time);
        $this->assertSame('07:45:00', (string) $periods[1]->start_time);

        // Six 45-minute periods with no break between them: 07:00 + 4h30.
        $this->assertSame('11:30:00', (string) $periods[5]->end_time);
    }

    public function test_a_break_pushes_every_period_after_it_later(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form([
                'periods_per_day' => 5,
                'first_period_start' => '08:00',
                'period_minutes' => 40,
                'breaks' => [
                    ['after_period' => 2, 'minutes' => 30, 'name' => 'Breaktime', 'short_name' => 'BK'],
                ],
            ]))
            ->assertRedirect();

        $periods = Period::where('tt_setting_id', $this->setting->id)
            ->orderBy('period_number')->get();

        $this->assertSame('09:20:00', (string) $periods[1]->end_time);
        $this->assertSame('09:50:00', (string) $periods[2]->start_time, 'Period 3 starts after the break.');

        $break = BreakPeriod::where('tt_setting_id', $this->setting->id)->sole();

        $this->assertSame(2, (int) $break->after_period);
        $this->assertSame('09:20:00', (string) $break->start_time);
        $this->assertSame('09:50:00', (string) $break->end_time);
        $this->assertSame('Breaktime', $break->name);
    }

    public function test_the_break_list_is_rewritten_from_what_was_submitted(): void
    {
        // The fixture seeds a break after period 4. Submitting a different one replaces
        // it rather than adding to it — the form owns the list.
        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form([
                'breaks' => [['after_period' => 2, 'minutes' => 15]],
            ]))
            ->assertRedirect();

        $breaks = BreakPeriod::where('tt_setting_id', $this->setting->id)->get();

        $this->assertCount(1, $breaks);
        $this->assertSame(2, (int) $breaks->first()->after_period);

        // And submitting none clears them.
        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form())
            ->assertRedirect();

        $this->assertSame(0, BreakPeriod::where('tt_setting_id', $this->setting->id)->count());
    }

    public function test_growing_the_day_keeps_the_cards_already_placed(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 8);

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form(['periods_per_day' => 10]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(10, Period::where('tt_setting_id', $this->setting->id)->count());
        $this->assertSame(8, (int) $card->fresh()->period_number);
    }

    public function test_the_cycle_length_follows_the_days_per_cycle(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form(['days_per_cycle' => 5]))
            ->assertRedirect();

        $this->assertSame(5, (int) $this->setting->fresh()->cycle_length);

        // And the grid reflows to match: five days of eight periods.
        $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertJsonCount(5, 'days')
            ->assertJsonCount(8, 'periods');
    }

    public function test_setting_eight_periods_reflows_the_grid_to_eight_columns_a_day(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form(['periods_per_day' => 4]))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertJsonCount(4, 'periods');

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form(['periods_per_day' => 8]))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->getJson(route('admin.timetable.grid', ['setting' => $this->setting->id]))
            ->assertOk()
            ->assertJsonCount(8, 'periods');
    }

    // -----------------------------------------------------------------
    // Shrinking — the refusal
    // -----------------------------------------------------------------

    public function test_shrinking_the_day_is_refused_while_cards_occupy_the_doomed_periods(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 7);

        $this->actingAs($this->admin)
            ->from(route('admin.timetable.grid'))
            ->post(route('admin.timetable.day-structure'), $this->form(['periods_per_day' => 6]))
            ->assertRedirect(route('admin.timetable.grid'))
            ->assertSessionHasErrors('periods_per_day');

        $this->assertSame(8, Period::where('tt_setting_id', $this->setting->id)->count());
        $this->assertSame(7, (int) $card->fresh()->period_number, 'A refused shrink loses nothing.');
    }

    public function test_the_refusal_names_the_periods_and_days_that_are_in_the_way(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 6, period: 8);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.day-structure'), $this->form([
                'periods_per_day' => 5,
                'days_per_cycle' => 5,
            ]));

        $response->assertStatus(422)
            ->assertJsonPath('occupied_periods.8', 1)
            ->assertJsonPath('occupied_days.6', 1);

        $this->assertStringContainsString('8', $response->json('message'));
    }

    public function test_shrinking_the_cycle_is_refused_while_a_card_sits_on_a_day_being_dropped(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $this->placeCard($bio, day: 6, period: 1);

        $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.day-structure'), $this->form(['days_per_cycle' => 5]))
            ->assertStatus(422);

        $this->assertSame(6, (int) $this->setting->fresh()->cycle_length);
    }

    public function test_shrinking_is_allowed_once_the_cards_in_the_way_are_gone(): void
    {
        $bio = $this->lesson('Biology', 'Form 5A', 'BIO A', 'K Simukonda');
        $card = $this->placeCard($bio, day: 1, period: 7);

        $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.grid.unplace'), [
                'setting_id' => $this->setting->id,
                'card_id' => $card->id,
            ])->assertOk();

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form(['periods_per_day' => 6]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(6, Period::where('tt_setting_id', $this->setting->id)->count());
    }

    // -----------------------------------------------------------------
    // Guards
    // -----------------------------------------------------------------

    public function test_a_renamed_period_keeps_its_name_when_the_times_are_regenerated(): void
    {
        Period::where('tt_setting_id', $this->setting->id)
            ->where('period_number', 1)
            ->update(['name' => 'Registration']);

        $this->actingAs($this->admin)
            ->post(route('admin.timetable.day-structure'), $this->form(['first_period_start' => '08:15']))
            ->assertRedirect();

        $first = Period::where('tt_setting_id', $this->setting->id)->where('period_number', 1)->sole();

        $this->assertSame('Registration', $first->name);
        $this->assertSame('08:15:00', (string) $first->start_time);
    }

    public function test_the_form_rejects_nonsense(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.day-structure'), $this->form(['periods_per_day' => 0]))
            ->assertStatus(422)->assertJsonValidationErrors('periods_per_day');

        $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.day-structure'), $this->form(['days_per_cycle' => 15]))
            ->assertStatus(422)->assertJsonValidationErrors('days_per_cycle');

        $this->actingAs($this->admin)
            ->postJson(route('admin.timetable.day-structure'), $this->form(['first_period_start' => 'half seven']))
            ->assertStatus(422)->assertJsonValidationErrors('first_period_start');
    }

    public function test_the_day_structure_is_closed_to_other_roles(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);

        $this->actingAs($teacher)
            ->post(route('admin.timetable.day-structure'), $this->form())
            ->assertForbidden();
    }
}
