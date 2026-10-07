<x-app-layout>
    <x-slot name="header"><div class="ops-hero"><div><p class="ops-eyebrow">School finance · {{ today()->format('F Y') }}</p><h1>Finance Dashboard</h1><p>Follow collections, outstanding fees, staff reimbursements, and supplier payments in one place.</p></div><a class="ops-button" href="{{ route('finance.payments.create') }}"><x-icon name="plus" class="w-4 h-4" /> Record payment</a></div></x-slot>
    @include('finance.partials.overview')
</x-app-layout>
