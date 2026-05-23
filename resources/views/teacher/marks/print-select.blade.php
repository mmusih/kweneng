<x-app-layout>
    <x-slot name="header">
        <div
            class="mt-16 p-6 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-lg flex items-center justify-center">
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-2xl text-white leading-tight">
                    Print Class Results
                </h2>
                <a href="{{ route('teacher.dashboard') }}"
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
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="mb-6">
                        <h3 class="text-2xl font-semibold">Select Class & Term</h3>
                        <p class="text-gray-600 mt-1">Choose a class and term to generate the results sheet.</p>
                    </div>

                    <!-- Step 1: Select Class -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-xl border border-gray-200">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 1: Select a Class</h4>

                        @if ($assignedClasses->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($assignedClasses as $class)
                                    <div class="border border-gray-200 rounded-xl p-4 bg-white hover:shadow-md hover:border-indigo-400 transition-all duration-200 cursor-pointer class-card"
                                        data-class-id="{{ $class->id }}"
                                        data-academic-year-id="{{ $class->academic_year_id }}">
                                        <h5 class="font-semibold text-gray-800 text-lg">{{ $class->name }}</h5>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $class->academicYear->year_name ?? 'N/A' }}</p>
                                        <p class="text-xs text-gray-500 mt-2">Level {{ $class->level }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-yellow-800">You are not assigned to any classes.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Step 2: Select Term -->
                    <div id="step2" class="hidden mb-8 p-6 bg-blue-50 rounded-xl border border-blue-200">
                        <h4 class="text-lg font-semibold mb-4 text-gray-800">Step 2: Select a Term</h4>

                        <div id="active-term-banner"
                            class="hidden rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 mb-4">
                            <div class="font-semibold text-sm">Active term detected</div>
                            <div id="active-term-label" class="text-sm mt-1"></div>
                        </div>

                        <div id="term-list" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>

                        <div id="no-terms-notice"
                            class="hidden rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 text-sm">
                            No terms found for this class's academic year.
                        </div>

                        <div class="mt-6">
                            <button type="button" id="open-print-btn"
                                class="hidden inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Open Print View
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let selectedClassId = null;
            let selectedTermId = null;

            const step2 = document.getElementById('step2');
            const termList = document.getElementById('term-list');
            const noTermsNotice = document.getElementById('no-terms-notice');
            const activeTermBanner = document.getElementById('active-term-banner');
            const activeTermLabel = document.getElementById('active-term-label');
            const openPrintBtn = document.getElementById('open-print-btn');

            document.querySelectorAll('.class-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    // Highlight selected card
                    document.querySelectorAll('.class-card').forEach(function(c) {
                        c.classList.remove('ring-2', 'ring-indigo-500', 'border-indigo-500',
                            'bg-indigo-50');
                    });
                    card.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-500', 'bg-indigo-50');

                    selectedClassId = card.getAttribute('data-class-id');
                    const academicYearId = card.getAttribute('data-academic-year-id');

                    selectedTermId = null;
                    openPrintBtn.classList.add('hidden');
                    termList.innerHTML = '';
                    noTermsNotice.classList.add('hidden');
                    activeTermBanner.classList.add('hidden');

                    loadTerms(academicYearId);

                    step2.classList.remove('hidden');
                    setTimeout(function() {
                        step2.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                });
            });

            function loadTerms(academicYearId) {
                termList.innerHTML = '<p class="text-sm text-gray-500 col-span-2">Loading terms...</p>';

                fetch("{{ url('/teacher/marks/terms') }}/" + academicYearId)
                    .then(function(res) {
                        if (!res.ok) throw new Error('Failed to load terms');
                        return res.json();
                    })
                    .then(function(terms) {
                        termList.innerHTML = '';

                        if (!terms || terms.length === 0) {
                            noTermsNotice.classList.remove('hidden');
                            return;
                        }

                        const activeTerm = terms.find(function(t) {
                            return t.status === 'active';
                        });

                        if (activeTerm) {
                            activeTermLabel.textContent = activeTerm.name + ' — currently active';
                            activeTermBanner.classList.remove('hidden');
                        } else {
                            activeTermBanner.classList.add('hidden');
                        }

                        terms.forEach(function(term) {
                            const card = document.createElement('div');
                            card.className =
                                'border border-gray-200 rounded-xl p-4 bg-white hover:shadow-md hover:border-indigo-400 transition-all duration-200 cursor-pointer term-card';
                            card.setAttribute('data-term-id', term.id);

                            let badge = '';
                            if (term.status === 'active') {
                                badge =
                                    '<span class="inline-block mt-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-800">Active</span>';
                            } else if (term.status === 'finalized') {
                                badge =
                                    '<span class="inline-block mt-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">Finalized</span>';
                            } else if (term.status === 'locked') {
                                badge =
                                    '<span class="inline-block mt-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">Locked</span>';
                            }

                            card.innerHTML =
                                '<h5 class="font-semibold text-gray-800">' + term.name + '</h5>' +
                                badge;

                            card.addEventListener('click', function() {
                                document.querySelectorAll('.term-card').forEach(function(c) {
                                    c.classList.remove('ring-2', 'ring-indigo-500',
                                        'border-indigo-500', 'bg-indigo-50');
                                });
                                card.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-500',
                                    'bg-indigo-50');

                                selectedTermId = term.id;
                                openPrintBtn.classList.remove('hidden');
                            });

                            termList.appendChild(card);

                            // Auto-select active term
                            if (term.status === 'active') {
                                card.click();
                            }
                        });
                    })
                    .catch(function(err) {
                        termList.innerHTML =
                            '<p class="text-sm text-red-600 col-span-2">Error loading terms. Please try again.</p>';
                    });
            }

            openPrintBtn.addEventListener('click', function() {
                if (!selectedClassId || !selectedTermId) return;
                const url = "{{ route('teacher.marks.print') }}?class_id=" + selectedClassId + "&term_id=" +
                    selectedTermId;
                window.open(url, '_blank');
            });
        </script>
    @endpush

</x-app-layout>
