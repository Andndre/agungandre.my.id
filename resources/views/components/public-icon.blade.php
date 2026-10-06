@props(['name' => 'arrow-up-right'])

<svg {{ $attributes->class('size-4') }} xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('arrow-left')
            <path d="m12 19-7-7 7-7" /><path d="M19 12H5" />
            @break
        @case('arrow-right')
            <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
            @break
        @case('menu')
            <path d="M4 12h16" /><path d="M4 6h16" /><path d="M4 18h16" />
            @break
        @default
            <path d="M7 7h10v10" /><path d="M7 17 17 7" />
    @endswitch
</svg>
