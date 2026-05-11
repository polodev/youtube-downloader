<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Downloader</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
<div class="max-w-4xl mx-auto py-10 px-4">

    <div class="flex items-center gap-6 mb-8">
        <a href="{{ route('downloads.index') }}"
            class="text-3xl font-bold text-gray-800 hover:text-red-500 transition">YouTube Downloader</a>
        <a href="{{ route('video-gallery') }}"
            class="text-sm font-semibold text-gray-500 hover:text-red-500 transition">&#127909; Video Gallery</a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Add link form --}}
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Add YouTube Link</h2>
        <form action="{{ route('downloads.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">YouTube Link <span class="text-red-500">*</span></label>
                <input type="url" name="link" value="{{ old('link') }}" required
                    placeholder="https://www.youtube.com/watch?v=..."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                @error('link')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Title <span class="text-gray-400">(optional)</span></label>
                    <input type="text" name="title" value="{{ old('title') }}"
                        placeholder="Video title"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Category <span class="text-gray-400">(optional)</span></label>
                    <input type="text" name="category" value="{{ old('category') }}"
                        placeholder="e.g. Music, Tutorial"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Type <span class="text-gray-400">(optional)</span></label>
                    <select name="type"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                        <option value="">— select —</option>
                        <option value="video" {{ old('type') === 'video' ? 'selected' : '' }}>Video</option>
                        <option value="short" {{ old('type') === 'short' ? 'selected' : '' }}>Short</option>
                    </select>
                </div>
            </div>
            <button type="submit"
                class="bg-red-500 hover:bg-red-600 text-white font-semibold px-6 py-2 rounded-lg transition">
                Save Link
            </button>
        </form>
    </div>

    {{-- Downloads list via Volt component --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-700">Saved Links</h2>
        </div>
        <livewire:downloads.download-list />
    </div>

</div>
@livewireScripts
</body>
</html>
