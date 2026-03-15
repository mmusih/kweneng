<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Terms
                </h2>
                <a href="{{ route('admin.dashboard') }}"
                    class="text-white hover:text-blue-100 text-sm font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-xl font-semibold">Manage Terms</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Control term status, report settings, and exam-stage locking.
                            </p>
                        </div>

                        <a href="{{ route('admin.terms.create') }}"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add New Term
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 p-4 text-green-800 border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-4 text-red-800 border border-red-200">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Term Name
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Academic Year
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dates
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Term Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exam Locks
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($terms as $term)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $term->name }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $term->academicYear->year_name ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $term->start_date->format('M j, Y') }} -
                                            {{ $term->end_date->format('M j, Y') }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @switch($term->status)
                                                @case('active')
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Active
                                                    </span>
                                                @break

                                                @case('finalized')
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                        Finalized
                                                    </span>
                                                @break

                                                @case('locked')
                                                    <span
                                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Locked
                                                    </span>
                                                @break
                                            @endswitch
                                        </td>

                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-medium text-gray-500 w-20">Midterm</span>
                                                    @if ($term->midterm_locked)
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                            Locked
                                                        </span>
                                                    @else
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Open
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-medium text-gray-500 w-20">Endterm</span>
                                                    @if ($term->endterm_locked)
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Locked
                                                        </span>
                                                    @else
                                                        <span
                                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                            Open
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.terms.edit', $term) }}"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </a>

                                                @if ($term->status !== 'locked')
                                                    @if (!$term->midterm_locked)
                                                        <form action="{{ route('admin.terms.lock-midterm', $term) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="text-blue-600 hover:text-blue-900"
                                                                onclick="return confirm('Lock midterm marks for this term?')">
                                                                Lock Midterm
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('admin.terms.unlock-midterm', $term) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="text-blue-400 hover:text-blue-600"
                                                                onclick="return confirm('Unlock midterm marks for this term?')">
                                                                Unlock Midterm
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if (!$term->endterm_locked)
                                                        <form action="{{ route('admin.terms.lock-endterm', $term) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="text-green-600 hover:text-green-900"
                                                                onclick="return confirm('Lock endterm marks for this term?')">
                                                                Lock Endterm
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form action="{{ route('admin.terms.unlock-endterm', $term) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            <button type="submit"
                                                                class="text-green-400 hover:text-green-600"
                                                                onclick="return confirm('Unlock endterm marks for this term?')">
                                                                Unlock Endterm
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif

                                                @if ($term->status === 'active')
                                                    <form action="{{ route('admin.terms.finalize', $term) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit"
                                                            class="text-purple-600 hover:text-purple-900"
                                                            onclick="return confirm('Finalize this term?')">
                                                            Finalize
                                                        </button>
                                                    </form>
                                                @elseif($term->status === 'finalized')
                                                    <form action="{{ route('admin.terms.lock', $term) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-gray-600 hover:text-gray-900"
                                                            onclick="return confirm('Lock this term fully? This cannot be undone easily.')">
                                                            Lock Term
                                                        </button>
                                                    </form>
                                                @endif

                                                @if ($term->status !== 'locked' && $term->status !== 'finalized')
                                                    <form action="{{ route('admin.terms.destroy', $term) }}"
                                                        method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900"
                                                            onclick="return confirm('Delete this term?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                                No terms found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $terms->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>
