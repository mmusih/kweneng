<?php

namespace App\Services\Timetable;

use App\Models\Tt\Lesson;
use Illuminate\Support\Facades\DB;

class BaseRoomResolver
{
    /** A shared or classless lesson has no unambiguous baseroom. */
    public function forLesson(Lesson $lesson): ?int
    {
        $classIds = $lesson->relationLoaded('classes')
            ? $lesson->classes->pluck('id')
            : $lesson->classes()->pluck('classes.id');

        if ($classIds->count() !== 1) {
            return null;
        }

        $id = DB::table('tt_class_base_rooms')
            ->where('tt_setting_id', $lesson->tt_setting_id)
            ->where('class_id', $classIds->first())
            ->value('tt_room_id');

        return $id === null ? null : (int) $id;
    }
}
