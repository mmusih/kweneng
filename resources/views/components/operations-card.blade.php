@props(['href', 'title', 'description', 'action' => 'Open workspace', 'icon' => 'dashboard'])
<a href="{{ $href }}" class="ops-card">
    <span class="ops-card-icon"><x-icon :name="$icon" class="w-5 h-5" /></span>
    <div><h3>{{ $title }}</h3><p>{{ $description }}</p><small>{{ $action }} <span aria-hidden="true">→</span></small></div>
</a>
