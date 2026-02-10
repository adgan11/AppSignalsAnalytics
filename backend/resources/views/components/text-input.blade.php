@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-input bg-background text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-ring rounded-md shadow-sm']) }}>
