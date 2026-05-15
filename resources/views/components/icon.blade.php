@props(['name' => 'circle', 'class' => 'w-5 h-5'])

@php
    // Set ikon TailAdmin-style (heroicons outline) yang sering dipakai.
    $paths = [
        'home'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8a1 1 0 001 1h3m10-9l2 2m-2-2v8a1 1 0 01-1 1h-3m-6 0h6"/>',
        'users'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5a4 4 0 11-8 0 4 4 0 018 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'menu-list'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>',
        'book'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5 5.754 5 4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
        'pencil'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>',
        'trash'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>',
        'plus'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
        'search'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>',
        'cog'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317a1 1 0 011.35-.936c.382.143.78.32 1.146.522.36.196.685.421.964.668a1 1 0 001.275 0c.279-.247.604-.472.964-.668.366-.202.764-.379 1.146-.522a1 1 0 011.35.936V5.5l1.182.342a1 1 0 01.668.668L20.5 7.675h1.183a1 1 0 01.936 1.35c-.143.382-.32.78-.522 1.146-.196.36-.421.685-.668.964a1 1 0 000 1.275c.247.279.472.604.668.964.202.366.379.764.522 1.146a1 1 0 01-.936 1.35H20.5l-.342 1.182a1 1 0 01-.668.668L18.325 18.5v1.183a1 1 0 01-1.35.936c-.382-.143-.78-.32-1.146-.522-.36-.196-.685-.421-.964-.668a1 1 0 00-1.275 0c-.279.247-.604.472-.964.668-.366.202-.764.379-1.146.522a1 1 0 01-1.35-.936V18.5l-1.182-.342a1 1 0 01-.668-.668L5.675 16.325H4.5a1 1 0 01-.936-1.35M12 15a3 3 0 100-6 3 3 0 000 6z"/>',
        'sparkles'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
        'logout'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
        'bell'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        'check'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>',
        'close'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>',
        'chart'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
        'doc-text'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'photo'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'rocket'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
        'leaf'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 21c8 0 14-6 14-14V3H10C5 3 3 7 3 12v9zM7 17c4-2 6-6 8-12"/>',
        'shield'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M12 21a9 9 0 01-9-9V5l9-2 9 2v7a9 9 0 01-9 9z"/>',
        'history'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h7M3 4h11M3 16h11m-7 4l4-4-4-4m6 0a9 9 0 100 8"/>',
        'arrow-right' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>',
        'sun'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2M5.6 5.6l1.4 1.4m10 10l1.4 1.4M3 12h2m14 0h2M5.6 18.4l1.4-1.4m10-10l1.4-1.4M12 8a4 4 0 100 8 4 4 0 000-8z"/>',
        'moon'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>',
        'monitor'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17h6M12 17v4m-7-4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2z"/>',
        'key'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 7a4 4 0 11-3.464 6.064L7 18H4v-3l5.964-5.964A4 4 0 0115 7zm0 0a1 1 0 100 2 1 1 0 000-2z"/>',
        'school'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 4-9 4-9-4 9-4zm0 8v8m-5-6v4l5 2 5-2v-4"/>',
        'shield-check'=> '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M12 21a9 9 0 01-9-9V5l9-2 9 2v7a9 9 0 01-9 9z"/>',
        'lock'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 11V7a4 4 0 118 0v4M5 11h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/>',
        'eye'         => '<path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12zm11 3a3 3 0 100-6 3 3 0 000 6z"/>',
        'eye-off'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.7 5.1A11 11 0 0112 5c7 0 11 7 11 7a18 18 0 01-3.2 4.2M6.6 6.6A18 18 0 001 12s4 7 11 7c1.6 0 3.1-.3 4.4-.9M9.9 9.9A3 3 0 0014 14"/>',
        'chat'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8a10 10 0 01-4-.8L3 21l1.8-5A8 8 0 013 12c0-4.4 4-8 9-8s9 3.6 9 8z"/>',
        'send'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>',
        'play'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/>',
        'stop'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 5h14v14H5z"/>',
        'clock'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'flag'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 21v-7m0 0V5a2 2 0 012-2h6l1 2h7v9H6"/>',
        'trophy'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zm10 1h3v3a3 3 0 01-3 3M7 5H4v3a3 3 0 003 3"/>',
        'printer'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/>',
        'arrow-up'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>',
        'arrow-down'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>',
    ];
    $svg = $paths[$name] ?? $paths['arrow-right'];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"
     class="{{ $class }}" aria-hidden="true">
    {!! $svg !!}
</svg>
