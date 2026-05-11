<?php

use App\Models\Download;
use function Livewire\Volt\{state, computed, usesPagination};

usesPagination();

state([
    'filterType'     => '',
    'filterCategory' => '',
    'sortOrder'      => 'desc',
]);

$categories = computed(fn() =>
    Download::where('status', 'done')
        ->whereNotNull('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category')
);

$videos = computed(function () {
    return Download::with('media')
        ->where('status', 'done')
        ->when($this->filterType,     fn($q) => $q->where('type', $this->filterType))
        ->when($this->filterCategory, fn($q) => $q->where('category', $this->filterCategory))
        ->orderBy('created_at', $this->sortOrder)
        ->paginate(30);
});

$resetFilters = \Livewire\Volt\action(function () {
    $this->filterType     = '';
    $this->filterCategory = '';
    $this->sortOrder      = 'desc';
    $this->resetPage();
});

?>

<div>
    {{-- Filters bar --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">
        {{-- Type filter --}}
        <select wire:model.live="filterType"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white">
            <option value="">All Types</option>
            <option value="video">Video</option>
            <option value="short">Short</option>
        </select>

        {{-- Category filter --}}
        <select wire:model.live="filterCategory"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white">
            <option value="">All Categories</option>
            @foreach($this->categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>

        {{-- Sort --}}
        <select wire:model.live="sortOrder"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white">
            <option value="desc">Newest First</option>
            <option value="asc">Oldest First</option>
        </select>

        @if($filterType || $filterCategory || $sortOrder !== 'desc')
            <button wire:click="resetFilters"
                class="text-sm text-red-500 hover:text-red-700 underline">Reset</button>
        @endif

        <span class="ml-auto text-sm text-gray-400">{{ $this->videos->total() }} video(s)</span>
    </div>

    {{-- Grid --}}
    @if($this->videos->isEmpty())
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">&#127909;</p>
            <p class="text-lg">No videos yet. Download some first.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->videos as $item)
                @php $media = $item->getFirstMedia('videos'); @endphp
                <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col">
                    {{-- Video player --}}
                    <div class="relative bg-black aspect-video max-h-56 overflow-hidden">
                        @if($media)
                            <video
                                class="w-full h-full object-contain"
                                controls
                                preload="metadata"
                                src="{{ $media->getUrl() }}">
                            </video>
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-500">
                                <span class="text-4xl">&#9654;</span>
                            </div>
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="p-4 flex flex-col gap-2 flex-1">
                        <p class="font-semibold text-gray-800 text-sm leading-snug line-clamp-2" title="{{ $item->title }}">
                            {{ $item->title ?: 'Untitled' }}
                        </p>

                        <div class="flex flex-wrap gap-1">
                            @if($item->category)
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $item->category }}</span>
                            @endif
                            @if($item->type)
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $item->type === 'short' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ ucfirst($item->type) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-50">
                            <span class="text-xs text-gray-400">{{ $item->created_at->format('M d, Y') }}</span>
                            <div class="flex items-center gap-3">
                                @if($media)
                                    <a href="{{ $media->getUrl() }}" download="{{ $media->file_name }}"
                                        class="text-xs text-green-600 hover:text-green-800 font-medium flex items-center gap-1">
                                        &#8595; Download
                                        <span class="text-gray-400">({{ round($media->size / 1048576, 1) }}MB)</span>
                                    </a>
                                @endif
                                <a href="{{ $item->link }}" target="_blank"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium">YT &#8599;</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $this->videos->links() }}
        </div>
    @endif
</div>
