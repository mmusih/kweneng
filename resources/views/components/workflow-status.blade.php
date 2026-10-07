@props(['status'])
<span class="ops-badge" data-status="{{ $status }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
