<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery — YouTube Downloader</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-white shadow-sm sticky top-0 z-40">
    <div class="px-[10%] py-3 flex items-center gap-6">
        <a href="{{ route('downloads.index') }}"
            class="text-xl font-bold text-gray-800 hover:text-red-500 transition">YouTube Downloader</a>
        <a href="{{ route('video-gallery') }}"
            class="text-sm font-semibold text-red-500 border-b-2 border-red-500 pb-0.5">&#127909; Video Gallery</a>
    </div>
</header>

<main class="px-[10%] py-8">
    <livewire:downloads.video-cards :show-filters="true" :done-only="true" />
</main>

@livewireScripts
</body>
</html>
