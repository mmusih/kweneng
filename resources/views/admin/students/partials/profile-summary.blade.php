@php
    $editUrl = route('admin.students.edit', array_merge(['student' => $student], request()->only(['search', 'class_id', 'page', 'term_id'])));
    $missingDetails = $student->profileCompletionIssues();
@endphp
<section class="profile-summary" aria-labelledby="student-name">
    <div class="profile-summary-main">
        <div class="profile-photo-column">
            @if($student->photo)
                <a href="{{ asset('storage/'.$student->photo) }}" target="_blank" rel="noopener" class="profile-photo-link" aria-label="View full photo of {{ $studentName }} (opens in a new tab)">
                    <x-student-photo :student="$student" :portrait="true" />
                </a>
                <span class="profile-photo-hint">Select photo to view full size</span>
            @else
                <x-student-photo :student="$student" :portrait="true" />
                <span class="profile-photo-hint">No photo uploaded</span>
            @endif
            <a href="{{ $editUrl }}#student-photo-form" class="profile-photo-action">{{ $student->photo ? 'Change photo' : 'Upload photo' }}</a>
        </div>
        <div class="profile-summary-info">
            <p class="profile-eyebrow">Student overview</p>
            <h3 id="student-name">{{ $studentName }}</h3>
            <p class="profile-email">{{ $studentEmail }}</p>
            <div class="profile-badges">
                <span>{{ $currentClass?->name ?? 'No class assigned' }}</span>
                <span>{{ ucfirst($student->gender ?: 'Gender not provided') }}</span>
                @if($age !== null)<span>{{ $age }} years old</span>@endif
            </div>
            <dl class="profile-facts">
                <div><dt>Admission reference</dt><dd>{{ $student->admission_no ?: 'Not provided' }}</dd></div>
                <div><dt>Date of birth</dt><dd>{{ $student->date_of_birth?->format('j M Y') ?? 'Not provided' }}</dd></div>
                <div><dt>Academic year</dt><dd>{{ $currentAcademicYear ?: 'Not assigned' }}</dd></div>
                <div><dt>Account status</dt><dd>{{ ucfirst($student->user?->status ?? 'Not recorded') }}</dd></div>
            </dl>
            <div class="profile-access" aria-label="School access status">
                <span class="{{ $student->results_access ? 'profile-status-ok' : 'profile-status-alert' }}">Results access: <strong>{{ $student->results_access ? 'Enabled' : 'Blocked' }}</strong></span>
                <span class="{{ $student->fees_blocked ? 'profile-status-alert' : 'profile-status-ok' }}">Fees access: <strong>{{ $student->fees_blocked ? 'Blocked' : 'Enabled' }}</strong></span>
                @if($student->user?->must_change_password)<span class="profile-status-notice">Password change required</span>@endif
            </div>
        </div>
    </div>
    @if(count($missingDetails))
        <div class="profile-completion">
            <div>
                <strong>Some profile details are still missing</strong>
                <p>Parents can provide these later. You can upload a photo at any time.</p>
                <details><summary>View missing details ({{ count($missingDetails) }})</summary><ul>@foreach($missingDetails as $detail)<li>{{ $detail }}</li>@endforeach</ul></details>
            </div>
            <a href="{{ $editUrl }}">Update details <span aria-hidden="true">→</span></a>
        </div>
    @endif
</section>
