<?php

namespace Tests\Feature\Timetable;

use App\Models\User;
use App\Services\Timetable\PrintScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Timetable\Concerns\SeedsTimetableSchool;
use Tests\TestCase;

class BatchPrintingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsTimetableSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedSchool();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']));
    }

    public function test_batch_and_summary_pdfs_for_each_entity_type(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda', double: true);
        $this->placeCard($lesson, day: 1, period: 1);
        $lesson->cards()->update(['tt_room_id' => $this->rooms['Lab 1']->id]);
        $this->get(route('admin.timetable.settings.verification', $this->setting))->assertOk()
            ->assertSee('Print all teachers')->assertSee('Print classes summary');
        foreach (['teacher', 'class', 'room'] as $type) {
            foreach (['all', 'summary'] as $mode) {
                $response = $this->get(route('admin.timetable.settings.print', [$this->setting, $type, $mode]))
                    ->assertOk()->assertHeader('content-type', 'application/pdf');
                $this->assertStringStartsWith('%PDF-', $response->getContent());
                if (getenv('TIMETABLE_PDF_QA')) {
                    file_put_contents(base_path('tmp/pdfs/'.$type.'-'.$mode.'.pdf'), $response->getContent());
                }
            }
        }
        if (getenv('TIMETABLE_PDF_QA')) {
            $service = app(PrintScheduleService::class);
            $page = $service->bundle($this->setting, 'class')[0];
            $entry = $page['cells'][1][1][0];
            $entry['subject'] = 'Add Maths';
            $entry['classes'] = 'Form 4A, Form 4B';
            $entry['teachers'] = 'K Simukonda';
            $entry['groups'] = 'Additional mathematics';
            $dense = [];
            foreach (range(1, 6) as $day) {
                foreach (range(1, 8) as $period) {
                    $page['cells'][$day][$period] = [$entry, array_merge($entry, ['subject' => 'Physics']), array_merge($entry, ['subject' => 'Geography'])];
                }
            }
            file_put_contents(base_path('tmp/pdfs/dense-class.pdf'), \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.timetable', ['pages' => [$page]])->setPaper('a4', 'landscape')->output());
            foreach (range(1, 24) as $number) {
                $dense[] = array_merge($page, ['entityName' => 'Form '.$number.'A']);
            }
            file_put_contents(base_path('tmp/pdfs/dense-summary.pdf'), \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.timetable-summary', ['pages' => $dense, 'setting' => $this->setting, 'type' => 'class', 'excludedForms' => [], 'teacherLegend' => $service->entities($this->setting, 'teacher'), 'roomLegend' => $service->entities($this->setting, 'room')])->setPaper('a3', 'landscape')->output());
        }
        foreach (['teacher-loads', 'teaching-summary'] as $report) {
            $response = $this->get(route('admin.timetable.'.$report.'.download', ['source' => 'working']))->assertOk();
            if (getenv('TIMETABLE_PDF_QA')) {
                file_put_contents(base_path('tmp/pdfs/'.$report.'.pdf'), $response->getContent());
            }
        }
    }

    public function test_form_filters_apply_to_batch_cells_and_class_targets(): void
    {
        $lesson = $this->lesson('Biology', 'Form 5A', 'BIO', 'K Simukonda');
        $this->placeCard($lesson, day: 1, period: 1);
        $service = app(PrintScheduleService::class);
        $this->assertCount(2, $service->bundle($this->setting, 'class'));
        $this->assertSame([], $service->bundle($this->setting, 'class', [5]));
        $pages = $service->bundle($this->setting, 'teacher', [5]);
        $this->assertSame([], $pages[0]['cells']);
        $this->get(route('admin.timetable.settings.print', [$this->setting, 'class', 'all', 'exclude_forms' => [5]]))->assertOk();
    }

    public function test_invalid_targets_and_unauthorised_requests_are_rejected(): void
    {
        $this->get(route('admin.timetable.settings.print', [$this->setting, 'invalid', 'all']))->assertNotFound();
        $this->get(route('admin.timetable.settings.print', [$this->setting, 'teacher', 'bad']))->assertNotFound();
        $this->getJson(route('admin.timetable.settings.print', [$this->setting, 'class', 'all', 'exclude_forms' => [99]]))->assertUnprocessable();
        $this->actingAs($this->teachers['K Simukonda']->user);
        $this->get(route('admin.timetable.settings.print', [$this->setting, 'teacher', 'all']))->assertForbidden();
    }
    public function test_print_cells_merge_doubles_without_crossing_breaks_or_empty_slots(): void
    {
        $periods = collect(range(1, 8))->map(fn ($number) => (object) ['period_number' => $number]);
        $entry = [['subject' => 'Biology']];
        $cells = [1 => $entry, 2 => $entry, 4 => $entry, 5 => $entry, 6 => $entry];
        $row = \App\Support\Timetable\PrintCells::row($periods, $cells, collect([(object) ['after_period' => 4]]));
        $this->assertSame([2, 1, 1, 2, 1, 1], array_column($row, 'span'));
        $this->assertSame([1, 3, 4, 5, 7, 8], array_column($row, 'start'));
    }

}
