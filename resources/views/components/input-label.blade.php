@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700 dark:text-brand-200']) }}>
    {{ $value ?? $slot }}
</label>
