@php
    $paths = [
        'shield' => 'M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z M9 12l2 2 4-4',
        'map' => 'M9 4l6 2 6-2v14l-6 2-6-2-6 2V6l6-2z M9 4v14 M15 6v14',
        'ship' => 'M3 14l1.5 6h15L21 14 M12 4v10 M6 10l6-3 6 3 M8 20V10 M16 20V10',
        'calendar' => 'M7 3v3 M17 3v3 M4 8h16 M5 6h14v15H5z',
        'store' => 'M4 9h16v11H4z M3 9l2-5h14l2 5 M9 20v-6h6v6',
        'ticket' => 'M4 8h16v8H4z M9 8v8 M13 8v8',
        'key' => 'M15 7a4 4 0 11-4 4l-6 6v3h3l6-6a4 4 0 014-7z',
        'languages' => 'M4 6h10 M9 4v2c0 5-2 8-5 10 M11 12c1 3 3 5 6 6 M14 20l4-9 4 9 M15.5 17h5',
        'upload' => 'M12 15V4 M8 8l4-4 4 4 M4 16v3h16v-3',
        'chart' => 'M4 20V10 M10 20V4 M16 20v-7 M22 20H2',
        'globe' => 'M12 3a9 9 0 100 18 9 9 0 000-18z M3 12h18 M12 3c3 4 3 14 0 18 M12 3c-3 4-3 14 0 18',
        'database' => 'M4 6c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3z M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6 M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3',
        'book' => 'M4 5h9a3 3 0 013 3v12a3 3 0 00-3-3H4z M20 5h-4v12h4z',
        'library' => 'M6 4v16 M11 4v16 M16 6l4 14 M3 20h18',
        'history' => 'M3 12a9 9 0 109-9 9 9 0 00-7.5 4 M3 4v4h4 M12 8v5l4 2',
        'plus' => 'M12 5v14 M5 12h14',
        'pencil' => 'M4 20h4l11-11-4-4L4 16z',
        'trash' => 'M4 7h16 M9 7V4h6v3 M6 7l1 13h10l1-13',
        'close' => 'M6 6l12 12 M18 6L6 18',
        'grid' => 'M4 4h7v7H4z M13 4h7v7h-7z M4 13h7v7H4z M13 13h7v7h-7z',
        'layers' => 'M12 3l9 5-9 5-9-5 9-5z M3 13l9 5 9-5',
        'save' => 'M5 4h11l3 3v13H5z M8 4v6h8V4 M8 20v-6h8v6',
        'sparkles' => 'M12 4l1.5 4.5L18 10l-4.5 1.5L12 16l-1.5-4.5L6 10l4.5-1.5L12 4z M18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8L18 16z',
        'plug' => 'M9 3v6 M15 3v6 M6 9h12v3a6 6 0 01-12 0z M12 18v3',
    ];
    $d = $paths[$name] ?? $paths['grid'];
@endphp

<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">
    @foreach (explode(' M', ltrim($d, 'M')) as $segment)
        <path d="M{{ $segment }}"></path>
    @endforeach
</svg>