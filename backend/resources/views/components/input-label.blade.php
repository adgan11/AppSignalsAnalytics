@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-muted-foreground']) }}>
    {{ $value ?? $slot }}
</label>
