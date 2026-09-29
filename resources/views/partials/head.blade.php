<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' | '.\App\Models\Setting::siteName() : \App\Models\Setting::siteName() }}</title>
@if ($metaDescription)
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:description" content="{{ $metaDescription }}">
@endif
<meta property="og:site_name" content="{{ \App\Models\Setting::siteName() }}">
<meta property="og:title" content="{{ $title ?: \App\Models\Setting::siteName() }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=fraunces:500,600,600i|instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
