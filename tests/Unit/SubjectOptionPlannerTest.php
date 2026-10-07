<?php

namespace Tests\Unit;

use App\Services\SubjectOptionPlanner;
use PHPUnit\Framework\TestCase;

class SubjectOptionPlannerTest extends TestCase
{
    private function data(): array
    {
        return [
            'groups' => [
                '1-1' => ['subject_id' => 1, 'name' => 'Biology', 'teachers' => [1], 'rooms' => [1], 'demand' => 10],
                '2-1' => ['subject_id' => 2, 'name' => 'Chemistry', 'teachers' => [1], 'rooms' => [1], 'demand' => 10],
                '3-1' => ['subject_id' => 3, 'name' => 'History', 'teachers' => [2], 'rooms' => [2], 'demand' => 10],
            ],
            'rooms' => [1 => ['name' => 'Lab', 'capacity' => 25], 2 => ['name' => 'Classroom', 'capacity' => 25]],
            'choices' => [[1, 2], [1, 2]], 'warnings' => [],
        ];
    }

    public function test_shared_teacher_and_room_and_learner_pairs_are_conflicts(): void
    {
        $planner = new SubjectOptionPlanner;
        $bad = $planner->assess($this->data(), ['1-1' => 0, '2-1' => 0, '3-1' => 1], 2);
        $this->assertSame(2, $bad['hard_conflicts']);
        $this->assertSame(2, $bad['clashing_learners']);
        $good = $planner->assess($this->data(), ['1-1' => 0, '2-1' => 1, '3-1' => 0], 2);
        $this->assertSame(0, $good['hard_conflicts']);
        $this->assertEquals(100, $good['score']);
        $this->assertSame(0, $good['clashing_learners']);
    }

    public function test_resource_matching_finds_alternative_teacher_and_room(): void
    {
        $data = $this->data();
        $data['groups']['1-1']['teachers'] = [1, 3];
        $data['groups']['1-1']['rooms'] = [1, 3];
        $data['rooms'][3] = ['name' => 'Second lab', 'capacity' => 25];
        $result = (new SubjectOptionPlanner)->assess($data, ['1-1' => 0, '2-1' => 0, '3-1' => 1], 2);
        $this->assertSame(0, $result['hard_conflicts']);
        $this->assertSame(3, $result['allocations']['1-1']['teacher_id']);
        $this->assertSame(3, $result['allocations']['1-1']['room_id']);
    }

    public function test_capacity_and_empty_blocks_are_hard_conflicts_and_missing_choices_are_unscored(): void
    {
        $data = $this->data();
        $data['choices'] = [];
        $data['groups']['1-1']['demand'] = 40;
        $result = (new SubjectOptionPlanner)->assess($data, ['1-1' => 0, '2-1' => 1, '3-1' => 1], 3);
        $this->assertNull($result['score']);
        $this->assertSame(2, $result['hard_conflicts']);
    }

    public function test_repeated_subject_offerings_allow_a_learner_to_choose_a_different_block(): void
    {
        $data = $this->data();
        $data['groups']['1-2'] = $data['groups']['1-1'];
        $data['groups']['1-2']['teachers'] = [3];
        $data['choices'] = [[1, 2]];
        $result = (new SubjectOptionPlanner)->assess($data, ['1-1' => 0, '2-1' => 0, '3-1' => 0, '1-2' => 1], 2);
        $this->assertSame(0, $result['clashing_learners']);
        $this->assertGreaterThanOrEqual(1, $result['allocations']['1-2']['learners']);
    }

    public function test_generation_ranks_feasible_arrangements_first_and_keeps_all_groups(): void
    {
        $suggestions = (new SubjectOptionPlanner)->generate($this->data(), 2);
        $this->assertNotEmpty($suggestions);
        $this->assertLessThanOrEqual(3, count($suggestions));
        $this->assertSame(0, $suggestions[0]['assessment']['hard_conflicts']);
        $this->assertEquals(100, $suggestions[0]['assessment']['score']);
        foreach ($suggestions as $suggestion) {
            $this->assertCount(3, $suggestion['assignment']);
        }
    }
}
