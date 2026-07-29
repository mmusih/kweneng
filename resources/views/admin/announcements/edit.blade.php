<x-app-layout>
    @php($announcementRoutePrefix = $announcementRoutePrefix ?? (request()->routeIs('office.*') ? 'office' : 'admin'))
    <x-slot name="header">
        <div class="mt-16 p-5 rounded-2xl kw-page-header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Announcement
            </h2>
        </div>
    </x-slot>

    <div class="py-12 kw-soft-section min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white kw-panel overflow-hidden">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" action="{{ route($announcementRoutePrefix . '.announcements.update', $announcement) }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6">
                            <div>
                                <x-input-label for="title" :value="__('Title')" />
                                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title"
                                    :value="old('title', $announcement->title)" required autofocus />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="message" :value="__('Message')" />
                                <textarea id="message" name="message" rows="5"
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>{{ old('message', $announcement->message) }}</textarea>
                                <x-input-error :messages="$errors->get('message')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="type" :value="__('Type')" />
                                    <select id="type" name="type"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Type</option>
                                        <option value="general" {{ old('type', $announcement->type) == 'general' ? 'selected' : '' }}>General</option>
                                        <option value="academic" {{ old('type', $announcement->type) == 'academic' ? 'selected' : '' }}>Academic</option>
                                        <option value="event" {{ old('type', $announcement->type) == 'event' ? 'selected' : '' }}>Event</option>
                                        <option value="urgent" {{ old('type', $announcement->type) == 'urgent' ? 'selected' : '' }}>Urgent</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('type')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="audience" :value="__('Audience')" />
                                    <select id="audience" name="audience"
                                        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Audience</option>
                                        <option value="all_parents" {{ old('audience', $announcement->audience) == 'all_parents' ? 'selected' : '' }}>All Parents</option>
                                        <option value="form_level" {{ old('audience', $announcement->audience) == 'form_level' ? 'selected' : '' }}>Form Level</option>
                                        <option value="class_group" {{ old('audience', $announcement->audience) == 'class_group' ? 'selected' : '' }}>Class Group</option>
                                        <option value="specific_parent" {{ old('audience', $announcement->audience) == 'specific_parent' ? 'selected' : '' }}>Specific Parent</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('audience')" class="mt-2" />
                                </div>
                            </div>

                            <div id="form-level-targets" class="target-field hidden">
                                <x-input-label for="target_form_levels" :value="__('Select Form Level(s)')" />
                                <select id="target_form_levels" name="target_form_levels[]" multiple
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($formLevels as $level)
                                        <option value="{{ $level }}"
                                            {{ in_array($level, old('target_form_levels', $selectedFormLevels ?? [])) ? 'selected' : '' }}>
                                            {{ $level }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl to select multiple forms.</p>
                                <x-input-error :messages="$errors->get('target_form_levels')" class="mt-2" />
                            </div>

                            <div id="class-group-targets" class="target-field hidden">
                                <x-input-label for="target_class_ids" :value="__('Select Class Group(s)')" />
                                <select id="target_class_ids" name="target_class_ids[]" multiple
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}"
                                            {{ in_array($class->id, old('target_class_ids', $selectedClassIds ?? [])) ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl to select multiple classes.</p>
                                <x-input-error :messages="$errors->get('target_class_ids')" class="mt-2" />
                            </div>

                            <div id="specific-parent-targets" class="target-field hidden">
                                <x-input-label for="target_parent_ids" :value="__('Select Parent(s)')" />
                                <select id="target_parent_ids" name="target_parent_ids[]" multiple
                                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($parents as $parent)
                                        <option value="{{ $parent->id }}"
                                            {{ in_array($parent->id, old('target_parent_ids', $selectedParentIds ?? [])) ? 'selected' : '' }}>
                                            {{ $parent->user->name ?? 'Unknown Parent' }} — {{ $parent->user->email ?? 'No email' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl to select multiple parents.</p>
                                <x-input-error :messages="$errors->get('target_parent_ids')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="publish_at" :value="__('Publish At (Optional)')" />
                                    <x-text-input id="publish_at" class="block mt-1 w-full" type="datetime-local"
                                        name="publish_at" :value="old('publish_at', optional($announcement->publish_at)->format('Y-m-d\\TH:i'))" />
                                    <x-input-error :messages="$errors->get('publish_at')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="expires_at" :value="__('Expires At (Optional)')" />
                                    <x-text-input id="expires_at" class="block mt-1 w-full" type="datetime-local"
                                        name="expires_at" :value="old('expires_at', optional($announcement->expires_at)->format('Y-m-d\\TH:i'))" />
                                    <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input id="is_published" type="checkbox" name="is_published" value="1"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                    {{ old('is_published', $announcement->is_published) ? 'checked' : '' }}>
                                <label for="is_published" class="ml-2 text-sm text-gray-600">
                                    Publish announcement
                                </label>
                            </div>

                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route($announcementRoutePrefix . '.announcements.index') }}"
                                    class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400">
                                    Cancel
                                </a>
                                <x-primary-button>Update Announcement</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateTargetFields() {
            const audience = document.getElementById('audience').value;
            document.querySelectorAll('.target-field').forEach(el => el.classList.add('hidden'));

            if (audience === 'form_level') {
                document.getElementById('form-level-targets').classList.remove('hidden');
            }

            if (audience === 'class_group') {
                document.getElementById('class-group-targets').classList.remove('hidden');
            }

            if (audience === 'specific_parent') {
                document.getElementById('specific-parent-targets').classList.remove('hidden');
            }
        }

        document.getElementById('audience').addEventListener('change', updateTargetFields);
        updateTargetFields();
    </script>
</x-app-layout>
