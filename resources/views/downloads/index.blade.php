<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Downloader</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            height: 42px;
            border-color: #d1d5db;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding: 0;
            line-height: 1.5;
            color: #374151;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #f87171;
            outline: none;
            box-shadow: 0 0 0 2px rgb(252 165 165 / 0.5);
        }
        .select2-dropdown { border-color: #d1d5db; border-radius: 0.5rem; }
        .select2-container--default .select2-results__option--highlighted { background-color: #ef4444; }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-white shadow-sm sticky top-0 z-40">
    <div class="px-[10%] py-3 flex items-center gap-6">
        <a href="{{ route('downloads.index') }}"
            class="text-xl font-bold text-gray-800 hover:text-red-500 transition">YouTube Downloader</a>
        <a href="{{ route('video-gallery') }}"
            class="text-sm font-semibold text-gray-500 hover:text-red-500 transition">&#127909; Video Gallery</a>
    </div>
</header>

<div class="px-[10%] py-8">

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="w-full lg:w-1/2 mb-8">
    <div class="bg-white rounded-xl shadow p-6">
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
                    <select name="category" id="category-create">
                        <option value=""></option>
                        @foreach(\App\Models\Download::categories() as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                {{ ucfirst($cat) }}
                            </option>
                        @endforeach
                    </select>
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
    </div>

    <livewire:downloads.video-cards :limit="30" />

</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@livewireScripts
<script>
    $(document).ready(function () {
        // Create form category
        $('#category-create').select2({
            placeholder: '— select category —',
            allowClear: true,
            width: '100%',
        });

        // Edit modal category — init once, re-sync value via custom event
        $(document).on('select2:init-edit-category', function (e) {
            const $sel = $('#edit-category-select');
            if (!$sel.length) return;
            if (!$sel.hasClass('select2-hidden-accessible')) {
                $sel.select2({ placeholder: '— select —', allowClear: true, width: '100%' });
                $sel.on('change', function () {
                    window.editCategoryWire && window.editCategoryWire.set('category', this.value || '');
                });
            }
            $sel.val(e.detail?.value || '').trigger('change.select2');
        });
    });
</script>
</body>
</html>
