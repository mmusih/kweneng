<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentUser extends Model
{
    use HasFactory;

    public const ROLE_TEACHER = 'teacher';
    public const ROLE_HOD = 'hod';
    public const ROLE_ASSISTANT_HOD = 'assistant_hod';
    public const ROLE_OBSERVER = 'observer';

    protected $table = 'department_user';

    protected $fillable = [
        'department_id',
        'user_id',
        'academic_year_id',
        'role_in_department',
    ];

    public static function roles(): array
    {
        return [
            self::ROLE_TEACHER => 'Teacher',
            self::ROLE_HOD => 'HOD',
            self::ROLE_ASSISTANT_HOD => 'Assistant HOD',
            self::ROLE_OBSERVER => 'Observer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
