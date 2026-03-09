<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AccountsOfficer;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Mark;
use App\Models\ParentModel;
use App\Models\Student;
use App\Models\StudentClassHistory;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. CORE USERS
        |--------------------------------------------------------------------------
        */

        $adminUser = User::create([
            'name' => 'Administrator',
            'email' => 'admin@school.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $headmasterUser = User::create([
            'name' => 'Headmaster One',
            'email' => 'headmaster@school.local',
            'password' => Hash::make('password'),
            'role' => 'headmaster',
            'status' => 'active',
        ]);

        $teacherOneUser = User::create([
            'name' => 'Teacher Alice',
            'email' => 'teacher1@school.local',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $teacherTwoUser = User::create([
            'name' => 'Teacher Brian',
            'email' => 'teacher2@school.local',
            'password' => Hash::make('password'),
            'role' => 'teacher',
            'status' => 'active',
        ]);

        $librarianUser = User::create([
            'name' => 'Librarian One',
            'email' => 'librarian@school.local',
            'password' => Hash::make('password'),
            'role' => 'librarian',
            'status' => 'active',
        ]);

        $accountsUser = User::create([
            'name' => 'Accounts Officer',
            'email' => 'accounts@school.local',
            'password' => Hash::make('password'),
            'role' => 'accounts_officer',
            'status' => 'active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 2. OPERATIONAL ROLE RECORDS
        |--------------------------------------------------------------------------
        */

        $headmaster = Teacher::create([
            'user_id' => $headmasterUser->id,
        ]);

        $teacherOne = Teacher::create([
            'user_id' => $teacherOneUser->id,
        ]);

        $teacherTwo = Teacher::create([
            'user_id' => $teacherTwoUser->id,
        ]);

        AccountsOfficer::create([
            'user_id' => $accountsUser->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. ACADEMIC YEAR + TERMS
        |--------------------------------------------------------------------------
        */

        $academicYear = AcademicYear::create([
            'year_name' => '2026/2027',
            'active' => true,
            'status' => 'open',
        ]);

        $term1 = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-13',
            'end_date' => '2026-04-10',
            'locked' => false,
            'status' => 'active',
        ]);

        $term2 = Term::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Term 2',
            'start_date' => '2026-05-05',
            'end_date' => '2026-08-14',
            'locked' => false,
            'status' => 'finalized',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 4. CLASSES
        |--------------------------------------------------------------------------
        */

        $form1A = ClassModel::create([
            'name' => 'Form 1A',
            'level' => 8,
            'academic_year_id' => $academicYear->id,
        ]);

        $form2A = ClassModel::create([
            'name' => 'Form 2A',
            'level' => 9,
            'academic_year_id' => $academicYear->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 5. SUBJECTS
        |--------------------------------------------------------------------------
        */

        $mathematics = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MATH',
            'description' => 'Core Mathematics',
            'is_core' => true,
            'is_active' => true,
            'display_order' => 1,
        ]);

        $english = Subject::create([
            'name' => 'English First Language',
            'code' => 'EFL',
            'description' => 'English First Language',
            'is_core' => true,
            'is_active' => true,
            'display_order' => 2,
        ]);

        $biology = Subject::create([
            'name' => 'Biology',
            'code' => 'BIO',
            'description' => 'Biology',
            'is_core' => true,
            'is_active' => true,
            'display_order' => 3,
        ]);

        $physics = Subject::create([
            'name' => 'Physics',
            'code' => 'PHY',
            'description' => 'Physics',
            'is_core' => false,
            'is_active' => true,
            'display_order' => 4,
        ]);

        $chemistry = Subject::create([
            'name' => 'Chemistry',
            'code' => 'CHEM',
            'description' => 'Chemistry',
            'is_core' => false,
            'is_active' => true,
            'display_order' => 5,
        ]);

        $computerScience = Subject::create([
            'name' => 'Computer Science',
            'code' => 'CS',
            'description' => 'Computer Science',
            'is_core' => false,
            'is_active' => true,
            'display_order' => 6,
        ]);

        $subjects = [
            $mathematics,
            $english,
            $biology,
            $physics,
            $chemistry,
            $computerScience,
        ];

        /*
        |--------------------------------------------------------------------------
        | 6. CLASS-SUBJECT ASSIGNMENTS
        |--------------------------------------------------------------------------
        */

        foreach ($subjects as $subject) {
            ClassSubject::create([
                'class_id' => $form1A->id,
                'subject_id' => $subject->id,
                'academic_year_id' => $academicYear->id,
                'max_marks' => 100,
                'passing_marks' => 50,
                'remarks' => 'Assigned for testing',
            ]);

            ClassSubject::create([
                'class_id' => $form2A->id,
                'subject_id' => $subject->id,
                'academic_year_id' => $academicYear->id,
                'max_marks' => 100,
                'passing_marks' => 50,
                'remarks' => 'Assigned for testing',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. TEACHER-SUBJECT ASSIGNMENTS
        |--------------------------------------------------------------------------
        */

        // Form 1A
        TeacherSubject::create([
            'teacher_id' => $teacherOne->id,
            'subject_id' => $mathematics->id,
            'class_id' => $form1A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary Mathematics teacher for Form 1A',
        ]);

        TeacherSubject::create([
            'teacher_id' => $teacherOne->id,
            'subject_id' => $english->id,
            'class_id' => $form1A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary English teacher for Form 1A',
        ]);

        TeacherSubject::create([
            'teacher_id' => $teacherTwo->id,
            'subject_id' => $biology->id,
            'class_id' => $form1A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary Biology teacher for Form 1A',
        ]);

        TeacherSubject::create([
            'teacher_id' => $teacherTwo->id,
            'subject_id' => $computerScience->id,
            'class_id' => $form1A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary Computer Science teacher for Form 1A',
        ]);

        // Form 2A
        TeacherSubject::create([
            'teacher_id' => $headmaster->id,
            'subject_id' => $mathematics->id,
            'class_id' => $form2A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Headmaster teaches Mathematics in Form 2A',
        ]);

        TeacherSubject::create([
            'teacher_id' => $teacherOne->id,
            'subject_id' => $english->id,
            'class_id' => $form2A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary English teacher for Form 2A',
        ]);

        TeacherSubject::create([
            'teacher_id' => $teacherTwo->id,
            'subject_id' => $biology->id,
            'class_id' => $form2A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary Biology teacher for Form 2A',
        ]);

        TeacherSubject::create([
            'teacher_id' => $teacherTwo->id,
            'subject_id' => $physics->id,
            'class_id' => $form2A->id,
            'academic_year_id' => $academicYear->id,
            'is_primary' => true,
            'remarks' => 'Primary Physics teacher for Form 2A',
        ]);

        /*
        |--------------------------------------------------------------------------
        | 8. STUDENTS
        |--------------------------------------------------------------------------
        */

$studentUsers = [
    [
        'name' => 'Student One',
        'email' => 'student1@school.local',
        'admission_no' => 'ADM001',
        'gender' => 'male',
        'date_of_birth' => '2011-03-15',
        'class_id' => $form1A->id,
    ],
    [
        'name' => 'Student Two',
        'email' => 'student2@school.local',
        'admission_no' => 'ADM002',
        'gender' => 'female',
        'date_of_birth' => '2011-07-20',
        'class_id' => $form1A->id,
    ],
    [
        'name' => 'Student Three',
        'email' => 'student3@school.local',
        'admission_no' => 'ADM003',
        'gender' => 'male',
        'date_of_birth' => '2011-11-05',
        'class_id' => $form1A->id,
    ],
    [
        'name' => 'Student Four',
        'email' => 'student4@school.local',
        'admission_no' => 'ADM004',
        'gender' => 'female',
        'date_of_birth' => '2010-04-12',
        'class_id' => $form2A->id,
    ],
    [
        'name' => 'Student Five',
        'email' => 'student5@school.local',
        'admission_no' => 'ADM005',
        'gender' => 'male',
        'date_of_birth' => '2010-08-19',
        'class_id' => $form2A->id,
    ],
    [
        'name' => 'Student Six',
        'email' => 'student6@school.local',
        'admission_no' => 'ADM006',
        'gender' => 'female',
        'date_of_birth' => '2010-12-01',
        'class_id' => $form2A->id,
    ],
];

        $students = [];

        foreach ($studentUsers as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => 'student',
                'status' => 'active',
            ]);

            $student = Student::create([
                'user_id' => $user->id,
                'admission_no' => $data['admission_no'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'],
                'current_class_id' => $data['class_id'],
                'photo' => null,
                'results_access' => true,
                'fees_blocked' => false,
            ]);

            StudentClassHistory::create([
                'student_id' => $student->id,
                'class_id' => $data['class_id'],
                'academic_year_id' => $academicYear->id,
                'is_current' => true,
            ]);

            $students[] = $student;
        }

        /*
        |--------------------------------------------------------------------------
        | 9. STUDENT SUBJECT ASSIGNMENTS
        |--------------------------------------------------------------------------
        */

        foreach ($students as $student) {
            $studentClassId = $student->current_class_id;

            $coreSubjects = [$mathematics, $english, $biology];
            $electiveSubjects = [$physics, $chemistry, $computerScience];

            foreach ($coreSubjects as $subject) {
                StudentSubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'class_id' => $studentClassId,
                    'academic_year_id' => $academicYear->id,
                    'is_elective' => false,
                ]);
            }

            // Give each student 2 electives
            $chosenElectives = array_slice($electiveSubjects, 0, 2);

            foreach ($chosenElectives as $subject) {
                StudentSubject::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'class_id' => $studentClassId,
                    'academic_year_id' => $academicYear->id,
                    'is_elective' => true,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 10. PARENTS
        |--------------------------------------------------------------------------
        */

        $parentOneUser = User::create([
            'name' => 'Parent One',
            'email' => 'parent1@school.local',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'status' => 'active',
        ]);

        $parentTwoUser = User::create([
            'name' => 'Parent Two',
            'email' => 'parent2@school.local',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'status' => 'active',
        ]);

        $parentThreeUser = User::create([
            'name' => 'Parent Three',
            'email' => 'parent3@school.local',
            'password' => Hash::make('password'),
            'role' => 'parent',
            'status' => 'active',
        ]);

        $parentOne = ParentModel::create([
            'user_id' => $parentOneUser->id,
            'phone' => '71234567',
            'address' => 'Molepolole',
        ]);

        $parentTwo = ParentModel::create([
            'user_id' => $parentTwoUser->id,
            'phone' => '72345678',
            'address' => 'Gaborone',
        ]);

        $parentThree = ParentModel::create([
            'user_id' => $parentThreeUser->id,
            'phone' => '73456789',
            'address' => 'Mogoditshane',
        ]);

        $parentOne->students()->attach($students[0]->id, ['relationship' => 'Mother']);
        $parentOne->students()->attach($students[1]->id, ['relationship' => 'Mother']);

        $parentTwo->students()->attach($students[2]->id, ['relationship' => 'Father']);
        $parentTwo->students()->attach($students[3]->id, ['relationship' => 'Father']);

        $parentThree->students()->attach($students[4]->id, ['relationship' => 'Guardian']);
        $parentThree->students()->attach($students[5]->id, ['relationship' => 'Guardian']);

        /*
        |--------------------------------------------------------------------------
        | 11. SAMPLE MARKS
        |--------------------------------------------------------------------------
        */

        foreach ($students as $index => $student) {
            $studentSubjects = StudentSubject::where('student_id', $student->id)->get();

            foreach ($studentSubjects as $studentSubject) {
                $teacherAssignment = TeacherSubject::where('subject_id', $studentSubject->subject_id)
                    ->where('class_id', $studentSubject->class_id)
                    ->where('academic_year_id', $studentSubject->academic_year_id)
                    ->first();

                if (! $teacherAssignment) {
                    continue;
                }

                $midterm = 45 + (($index + $studentSubject->subject_id) % 35);
                $endterm = 50 + (($index + $studentSubject->subject_id + 3) % 35);
                $average = ($midterm + $endterm) / 2;

                $grade = match (true) {
                    $average >= 80 => 'A',
                    $average >= 70 => 'B',
                    $average >= 60 => 'C',
                    $average >= 50 => 'D',
                    default => 'E',
                };

                Mark::create([
                    'student_id' => $student->id,
                    'subject_id' => $studentSubject->subject_id,
                    'class_id' => $studentSubject->class_id,
                    'teacher_id' => $teacherAssignment->teacher_id,
                    'academic_year_id' => $studentSubject->academic_year_id,
                    'term_id' => $term1->id,
                    'midterm_score' => $midterm,
                    'endterm_score' => $endterm,
                    'grade' => $grade,
                    'remarks' => $average >= 50 ? 'Satisfactory performance' : 'Needs improvement',
                ]);
            }
        }
    }
}