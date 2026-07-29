<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 p-6 bg-white rounded-lg shadow-md border border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-gray-800">New Message to School</h2>
                <a href="{{ route('parent.messages.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <form action="{{ route('parent.messages.store') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            placeholder="e.g. Query about fees, Term dates, etc." required maxlength="255">
                        @error('subject')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                        <textarea id="body" name="body" rows="7"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                            placeholder="Write your message here..." required maxlength="5000">{{ old('body') }}</textarea>
                        @error('body')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-medium text-white transition"
                            style="background:#2C3E6B;" onmouseover="this.style.background='#1e2d4f'"
                            onmouseout="this.style.background='#2C3E6B'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
