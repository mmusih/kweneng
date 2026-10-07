@props(['statuses'])
<form method="get" class="ops-filter">
    @if(request()->filled('year'))<input type="hidden" name="year" value="{{ request('year') }}">@endif
    <label>Request status<select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
    <button class="ops-button" type="submit">Filter</button>
    @if(request()->filled('status'))<a class="underline text-sm" href="{{ request()->url() }}">Clear filter</a>@endif
</form>
