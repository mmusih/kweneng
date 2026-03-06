<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use Illuminate\Console\Command;

class AssignAcademicYearToExistingClasses extends Command
{
    protected $signature = 'school:assign-academic-years';
    protected $description = 'Assign academic years to existing classes';

    public function handle()
    {
        $activeYear = AcademicYear::where('active', true)->first();
        
        if (!$activeYear) {
            $this->error('No active academic year found!');
            return;
        }
        
        $classes = ClassModel::whereNull('academic_year_id')->get();
        
        $this->info("Assigning {$classes->count()} classes to academic year: {$activeYear->year_name}");
        
        foreach ($classes as $class) {
            $class->academic_year_id = $activeYear->id;
            $class->save();
        }
        
        $this->info('Assignment completed successfully!');
    }
}
