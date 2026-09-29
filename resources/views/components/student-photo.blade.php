@props(['student', 'portrait' => false])
<div {{ $attributes->class(['relative shrink-0 overflow-hidden bg-[#D3D9D4] text-[#124E66]', 'student-portrait' => $portrait, 'h-24 w-24 rounded-full' => ! $portrait]) }}>
    <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold" aria-hidden="true">{{ mb_substr($student->user?->name ?? 'Student', 0, 1) }}</span>
    @if($student->photo)
        <img src="{{ asset('storage/'.$student->photo) }}" alt="{{ $student->user?->name }} profile photo" class="relative h-full w-full {{ $portrait ? 'object-contain' : 'object-cover' }}" onerror="this.remove()">
    @else
        <span class="sr-only">No student photo uploaded</span>
    @endif
</div>
