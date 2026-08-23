@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'border-gray-300 focus:border-zinc-500 focus:ring-zinc-500 rounded-md shadow-sm']) }}>
