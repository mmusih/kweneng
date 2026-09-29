<div class="mx-auto max-w-7xl px-4 py-6 space-y-5">
    @if(session('success'))<div role="status" class="rounded-lg border border-green-300 bg-green-50 p-4 text-green-900">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-900"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    {{ $slot }}
</div>
