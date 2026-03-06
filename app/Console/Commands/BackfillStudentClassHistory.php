<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentClassHistory;
use Illuminate\Console\Command;

class BackfillStudentClassHistory extends Command
{
    protected $signature = 'school:backfill-history';
    protected $description = 'Backfill student class history from existing data';

    public function handle()
    {
        $this->info('Starting backfill process...');
        
        $students = Student::whereNotNull('current_class_id')->with(['currentClass', 'currentClass.academicYear'])->get();
        
        $bar = $this->output->createProgressBar(count($students));
        $bar->start();
        
        foreach ($students as $student) {
            $class = $student->currentClass;
            
            if ($class && $class->academicYear) {
                StudentClassHistory::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $class->academic_year_id,
                    ],
                    [
                        'class_id' => $class->id,
                        'is_current' => true,
                    ]
                );
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->info("\nBackfill completed successfully!");
    }
}
