@php
  $paths = [
    'chart' => '<path d="M4 19V9"/><path d="M10 19V5"/><path d="M16 19v-7"/><path d="M21 19H3"/>',
    'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
    'cart' => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M3 4h2l2.4 11h11L21 7H6"/>',
    'inbox' => '<path d="M12 3v10"/><path d="M8 9l4 4 4-4"/><path d="M4 15v4h16v-4"/>',
    'undo' => '<path d="M4 10h9a5 5 0 010 10H8"/><path d="M8 6l-4 4 4 4"/>',
    'tag' => '<path d="M20 12l-8 8-9-9V4h7l10 8z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
    'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2 2 2 0 11-4 0 1.7 1.7 0 00-2.9-1.2l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 004.6 15a2 2 0 010-4 1.7 1.7 0 001.2-2.9l-.1-.1a2 2 0 112.8-2.8l.1.1A1.7 1.7 0 0011.5 4a2 2 0 014 0 1.7 1.7 0 002.9 1.2l.1-.1a2 2 0 112.8 2.8l-.1.1A1.7 1.7 0 0019.4 11a2 2 0 010 4z"/>',
    'external' => '<path d="M14 4h6v6"/><path d="M20 4l-9 9"/><path d="M19 14v5H5V5h5"/>',
    'stack' => '<path d="M4 7l8-4 8 4-8 4-8-4z"/><path d="M4 12l8 4 8-4"/><path d="M4 17l8 4 8-4"/>',
    'alert' => '<path d="M12 3l9 16H3l9-16z"/><path d="M12 9v5"/><circle cx="12" cy="17" r="1"/>',
    'download' => '<path d="M12 4v10"/><path d="M8 10l4 4 4-4"/><path d="M4 20h16"/>',
    'money' => '<rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
    'trend' => '<path d="M4 17l5-6 4 3 6-8"/><path d="M15 6h5v5"/>',
    'wallet' => '<rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18"/><circle cx="17" cy="14.5" r="1.2"/>',
    'bag' => '<path d="M6 8h12l1 12H5L6 8z"/><path d="M9 8V6a3 3 0 016 0v2"/>',
    'trophy' => '<path d="M8 4h8v5a4 4 0 01-8 0V4z"/><path d="M8 6H5v2a3 3 0 003 3"/><path d="M16 6h3v2a3 3 0 01-3 3"/><path d="M10 17h4"/><path d="M12 13v4"/><path d="M9 21h6"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3 10h18"/>',
    'home' => '<path d="M4 11l8-7 8 7"/><path d="M6 10v10h12V10"/>',
    'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 19c0-3 2.5-5 6-5s6 2 6 5"/><circle cx="17" cy="9" r="2.2"/><path d="M21 19c0-2.2-1.6-3.8-3.8-4.2"/>',
  ];
@endphp
<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">{!! $paths[$name] ?? $paths['box'] !!}</svg>
