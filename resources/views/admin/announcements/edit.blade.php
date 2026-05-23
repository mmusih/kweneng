<x-app-layout>
    <x-slot name="header">
        <div class="mt-16 flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Announcement
            </h2>
        </div>

    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <!-- Title -->
                            <div>
                                <x-input-label for="title" :value="__('Title')" />
                                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title"
                                    :value="old('title', $announcement->title)" required autofocus />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <!-- Message -->
                            <div>
                                <x-input-label for="message" :value="__('Message')" />
                                <textarea id="message" name="message" rows="5"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>{{ old('message', $announcement->message) }}</textarea>
                                <x-input-error :messages="$errors->get('message')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Type -->
                                <div>
                                    <x-input-label for="type" :value="__('Type')" />
                                    <select id="type" name="type"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Type</option>
                                        <option value="general"
                                            {{ old('type', $announcement->type) == 'general' ? 'selected' : '' }}>
                                            General</option>
                                        <option value="academic"
                                            {{ old('type', $announcement->type) == 'academic' ? 'selected' : '' }}>
                                            Academic</option>
                                        <option value="event"
                                            {{ old('type', $announcement->type) == 'event' ? 'selected' : '' }}>Event
                                        </option>
                                        <option value="urgent"
                                            {{ old('type', $announcement->type) == 'urgent' ? 'selected' : '' }}>Urgent
                                        </option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>

                                <!-- Audience -->
                                <div>
                                    <x-input-label for="audience" :value="__('Audience')" />
                                    <select id="audience" name="audience"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Audience</option>
                                        <option value="all"
                                            {{ old('audience', $announcement->audience) == 'all' ? 'selected' : '' }}>
                                            All Users</option>
                                        <option value="parents"
                                            {{ old('audience', $announcement->audience) == 'parents' ? 'selected' : '' }}>
                                            Parents Only</option>
                                        <option value="teachers"
                                            {{ old('audience', $announcement->audience) == 'teachers' ? 'selected' : '' }}>
                                            Teachers Only</option>
                                        <option value="students"
                                            {{ old('audience', $announcement->audience) == 'students' ? 'selected' : '' }}>
                                            Students Only</option>
                                        <option value="specific_class"
                                            {{ old('audience', $announcement->audience) == 'specific_class' ? 'selected' : '' }}>
                                            Specific Class</option>
                                        <option value="specific_subject"
                                            {{ old('audience', $announcement->audience) == 'specific_subject' ? 'selected' : '' }}>
                                            Specific Subject</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('audience')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Class (conditional) -->
                            <div id="class-field"
                                class="{{ $announcement->audience !== 'specific_class' ? 'hidden' : '' }}">
                                <x-input-label for="class_id" :value="__('Specific Class')" />
                                <select id="class_id" name="class_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Class</option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ old('class_id', $announcement->class_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
                            </div>

                            <!-- Subject (conditional) -->
                            <div id="subject-field"
                                class="{{ $announcement->audience !== 'specific_subject' ? 'hidden' : '' }}">
                                <x-input-label for="subject_id" :value="__('Specific Subject')" />
                                <select id="subject_id" name="subject_id"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Subject</option>
                                    @foreach ($subjects as $subject)
                                        <option value="{{ $subject->id }}"
                                            {{ old('subject_id', $announcement->subject_id) == $subject->id ? 'selected' : '' }}>
                                            {{ $subject->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Publish At -->
                                <div>
                                    <x-input-label for="publish_at" :value="__('Publish At (Optional)')" />
                                    <x-text-input id="publish_at" class="block mt-1 w-full" type="datetime-local"
                                        name="publish_at" :value="old('publish_at', $announcement->publish_at?->format('Y-m-d\TH:i'))" />
                                    <x-input-error :messages="$errors->get('publish_at')" class="mt-2" />
                                </div>

                                <!-- Expires At -->
                                <div>
                                    <x-input-label for="expires_at" :value="__('Expires At (Optional)')" />
                                    <x-text-input id="expires_at" class="block mt-1 w-full" type="datetime-local"
                                        name="expires_at" :value="old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i'))" />
                                    <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Published Status -->
                            <div class="flex items-center">
                                <input id="is_published" type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    name="is_published"
                                    {{ old('is_published', $announcement->is_published) ? 'checked' : '' }}>
                                <label for="is_published" class="ml-2 block text-sm text-gray-900">
                                    {{ __('Published') }}
                                </label>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <a href="{{ route('admin.announcements.index') }}"
                                    class="mr-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    {{ __('Cancel') }}
                                </a>

                                <x-primary-button class="ml-4">
                                    {{ __('Update Announcement') }}
                                </x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const audienceSelect = document.getElementById('audience');
            const classField = document.getElementById('class-field');
            const subjectField = document.getElementById('subject-field');

            // Toggle fields based on audience selection
            audienceSelect.addEventListener('change', function() {
                classField.classList.add('hidden');
                subjectField.classList.add('hidden');

                if (this.value === 'specific_class') {
                    classField.classList.remove('hidden');
                } else if (this.value === 'specific_subject') {
                    subjectField.classList.remove('hidden');
                }
            });

            // Initialize on page load
            if (audienceSelect.value === 'specific_class') {
                classField.classList.remove('hidden');
            } else if (audienceSelect.value === 'specific_subject') {
                subjectField.classList.remove('hidden');
            }
        });
    </script>
</x-app-layout>
