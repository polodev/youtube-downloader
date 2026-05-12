<?php

use App\Jobs\DownloadVideoJob;
use App\Jobs\FetchVideoTitleJob;
use App\Models\Download;
use function Livewire\Volt\{state, computed, on, action, usesPagination, mount};

usesPagination();

state([
    'limit'            => 0,
    'showFilters'      => false,
    'doneOnly'         => false,
    'filterType'       => '',
    'filterCategory'   => '',
    'sortOrder'        => 'desc',
    'refreshKey'       => 0,
    'pickerOpen'       => false,
    'pickerDownloadId' => null,
    'pickerResolution' => 'best',
    'directRunning'    => false,
]);

mount(function (int $limit = 0, bool $showFilters = false, bool $doneOnly = false) {
    $this->limit       = $limit;
    $this->showFilters = $showFilters;
    $this->doneOnly    = $doneOnly;
});

$categories = computed(fn() => Download::categories());

$videos = computed(function () {
    $this->refreshKey;
    $query = Download::with('media')
        ->when($this->doneOnly,       fn($q) => $q->where('status', 'done'))
        ->when($this->filterType,     fn($q) => $q->where('type', $this->filterType))
        ->when($this->filterCategory, fn($q) => $q->where('category', $this->filterCategory))
        ->orderBy('created_at', $this->sortOrder);

    if ($this->limit > 0) {
        return $query->limit($this->limit)->get();
    }

    return $query->paginate(30);
});

$hasActive = computed(function () {
    $this->refreshKey;
    return Download::whereIn('status', ['pending', 'downloading'])->exists();
});

$refresh = action(fn() => $this->refreshKey++);

on([
    'download-updated' => fn() => $this->refreshKey++,
    'download-queued'  => fn() => $this->refreshKey++,
]);

$openPicker = action(function (int $id) {
    $this->pickerDownloadId = $id;
    $this->pickerResolution = 'best';
    $this->pickerOpen       = true;
});

$closePicker = action(fn() => $this->pickerOpen = false);

$queueDownload = action(function () {
    $download = Download::findOrFail($this->pickerDownloadId);
    $download->clearMediaCollection('videos');
    $download->update(['status' => 'pending', 'resolution' => $this->pickerResolution]);
    DownloadVideoJob::dispatch($download, $this->pickerResolution);
    $this->pickerOpen = false;
    $this->refreshKey++;
});

$directDownload = action(function () {
    $download = Download::findOrFail($this->pickerDownloadId);
    $download->clearMediaCollection('videos');
    $download->update(['status' => 'pending', 'resolution' => $this->pickerResolution]);
    $this->pickerOpen    = false;
    $this->directRunning = true;
    $this->refreshKey++;
    DownloadVideoJob::dispatchSync($download, $this->pickerResolution);
    $this->directRunning = false;
    $this->refreshKey++;
});

$triggerEdit = action(function (int $id) {
    $this->dispatch('trigger-edit', id: $id);
});

$fetchTitle = action(function (int $id) {
    $download = Download::findOrFail($id);
    $title    = FetchVideoTitleJob::fetchTitle($download->link);
    if ($title) {
        $download->update(['title' => $title]);
    }
    $this->refreshKey++;
});

$deleteItem = action(function (int $id) {
    $download = Download::findOrFail($id);
    $download->clearMediaCollection('videos');
    $download->delete();
    $this->refreshKey++;
});

$resetFilters = action(function () {
    $this->filterType     = '';
    $this->filterCategory = '';
    $this->sortOrder      = 'desc';
    $this->resetPage();
});

?>

<div @if($this->hasActive || $directRunning) wire:poll.3s="refresh" @endif>

    {{-- Edit modal --}}
    <livewire:downloads.edit-download />

    {{-- Resolution picker modal --}}
    @if($pickerOpen)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Choose Resolution</h3>
                <button wire:click="closePicker" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="px-6 py-4 space-y-2">
                @foreach([
                    'best'  => ['label' => 'Best Quality',    'note' => 'Highest available', 'badge' => ''],
                    '2160p' => ['label' => '4K — 2160p',      'note' => 'Ultra HD',           'badge' => ''],
                    '1080p' => ['label' => 'Full HD — 1080p', 'note' => '',                    'badge' => 'Popular'],
                    '720p'  => ['label' => 'HD — 720p',       'note' => '',                    'badge' => ''],
                    '480p'  => ['label' => '480p',            'note' => 'Standard',            'badge' => ''],
                    '360p'  => ['label' => '360p',            'note' => 'Low quality',         'badge' => ''],
                    'audio' => ['label' => 'Audio Only',      'note' => 'Best audio stream',   'badge' => ''],
                ] as $value => $opt)
                    <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer border-2 transition
                        {{ $pickerResolution === $value ? 'border-red-400 bg-red-50' : 'border-transparent hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="pickerResolution" value="{{ $value }}" class="accent-red-500">
                        <div class="flex-1">
                            <span class="font-medium text-gray-800 text-sm">{{ $opt['label'] }}</span>
                            @if($opt['note'])
                                <span class="text-xs text-gray-400 ml-2">{{ $opt['note'] }}</span>
                            @endif
                        </div>
                        @if($opt['badge'])
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ $opt['badge'] }}</span>
                        @endif
                        @if($value === 'best')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Default</span>
                        @endif
                    </label>
                @endforeach
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                <div class="flex gap-3">
                    <button wire:click="closePicker"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button wire:click="directDownload" wire:loading.attr="disabled" wire:target="directDownload"
                        class="flex-1 px-4 py-2 text-sm bg-gray-700 hover:bg-gray-800 disabled:opacity-50 text-white font-semibold rounded-lg transition text-center">
                        <span wire:loading.remove wire:target="directDownload">&#9654; Now</span>
                        <span wire:loading wire:target="directDownload">Downloading…</span>
                    </button>
                    <button wire:click="queueDownload"
                        class="flex-1 px-4 py-2 text-sm bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition text-center">
                        &#128338; Queue
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    <strong>Now</strong> blocks until done. <strong>Queue</strong> runs via worker in background.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Direct download running overlay --}}
    @if($directRunning)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl px-8 py-6 text-center">
            <div class="text-3xl mb-3 animate-spin inline-block">&#8635;</div>
            <p class="font-semibold text-gray-800">Downloading…</p>
            <p class="text-sm text-gray-400 mt-1">Please wait, this may take a few minutes.</p>
        </div>
    </div>
    @endif

    {{-- Filter bar --}}
    @if($showFilters)
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <select wire:model.live="filterType"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white">
            <option value="">All Types</option>
            <option value="video">Video</option>
            <option value="short">Short</option>
        </select>

        <select wire:model.live="filterCategory"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white">
            <option value="">All Categories</option>
            @foreach($this->categories as $cat)
                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
            @endforeach
        </select>

        <select wire:model.live="sortOrder"
            class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white">
            <option value="desc">Newest First</option>
            <option value="asc">Oldest First</option>
        </select>

        @if($filterType || $filterCategory || $sortOrder !== 'desc')
            <button wire:click="resetFilters" class="text-sm text-red-500 hover:text-red-700 underline">Reset</button>
        @endif

        <span class="ml-auto text-sm text-gray-400">{{ $this->videos->total() }} video(s)</span>
    </div>
    @endif

    {{-- Grid --}}
    @php
        $items = $this->videos instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $this->videos->getCollection()
            : $this->videos;

        $statusColors = [
            'pending'     => 'bg-yellow-100 text-yellow-700',
            'downloading' => 'bg-blue-100 text-blue-700',
            'done'        => 'bg-green-100 text-green-700',
            'failed'      => 'bg-red-100 text-red-700',
        ];
    @endphp

    @if($items->isEmpty())
        <div class="text-center py-20 text-gray-400">
            <p class="text-5xl mb-4">&#127909;</p>
            <p class="text-lg">No videos yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @foreach($items as $item)
            @php $media = $item->getFirstMedia('videos'); @endphp
            <div class="bg-white rounded-xl shadow overflow-hidden flex flex-col">

                {{-- Action bar (top of card) --}}
                <div class="flex items-center gap-1 px-2 py-1.5 bg-gray-50 border-b border-gray-100">
                    {{-- Edit --}}
                    <button wire:click="triggerEdit({{ $item->id }})"
                        title="Edit"
                        class="p-1.5 rounded text-gray-400 hover:text-blue-500 hover:bg-blue-50 transition text-sm">
                        &#9998;
                    </button>

                    {{-- Fetch title --}}
                    <button wire:click="fetchTitle({{ $item->id }})"
                        wire:loading.attr="disabled" wire:target="fetchTitle({{ $item->id }})"
                        title="Fetch title from YouTube"
                        class="p-1.5 rounded text-gray-400 hover:text-purple-500 hover:bg-purple-50 transition text-sm disabled:opacity-40">
                        <span wire:loading.remove wire:target="fetchTitle({{ $item->id }})">&#8635;</span>
                        <span wire:loading wire:target="fetchTitle({{ $item->id }})">…</span>
                    </button>

                    <div class="flex-1"></div>

                    {{-- Status badge --}}
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColors[$item->status] ?? '' }}">
                        {{ ucfirst($item->status) }}
                    </span>

                    {{-- Download / Re-download --}}
                    @if(in_array($item->status, ['pending', 'failed']))
                        <button wire:click="openPicker({{ $item->id }})"
                            title="Download"
                            class="p-1.5 rounded text-white bg-red-500 hover:bg-red-600 transition text-xs font-semibold px-2">
                            &#8595; DL
                        </button>
                    @elseif($item->status === 'done')
                        <button wire:click="openPicker({{ $item->id }})"
                            title="Re-download at different resolution"
                            class="p-1.5 rounded text-white bg-gray-600 hover:bg-gray-700 transition text-xs font-semibold px-2">
                            &#8635; Re-DL
                        </button>
                    @endif

                    {{-- Delete --}}
                    <button wire:click="deleteItem({{ $item->id }})"
                        wire:confirm="Delete this entry and its file?"
                        title="Delete"
                        class="p-1.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition text-sm">
                        &times;
                    </button>
                </div>

                {{-- Video player or status placeholder --}}
                <div class="bg-black overflow-hidden" style="max-height:200px;">
                    @if($media)
                        <video class="w-full h-full object-contain" style="max-height:200px;"
                            controls preload="metadata" src="{{ $media->getUrl() }}"></video>
                    @else
                        <div class="flex items-center justify-center bg-gray-800" style="height:160px;">
                            @if($item->status === 'downloading')
                                <div class="text-center text-white">
                                    <div class="text-2xl animate-spin inline-block mb-1">&#8635;</div>
                                    <p class="text-xs">Downloading…</p>
                                </div>
                            @elseif($item->status === 'pending')
                                <div class="text-center text-gray-400">
                                    <div class="text-3xl mb-1">&#9654;</div>
                                    <p class="text-xs">Pending</p>
                                </div>
                            @else
                                <div class="text-center text-red-400">
                                    <div class="text-3xl mb-1">&#9888;</div>
                                    <p class="text-xs">Failed</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="p-3 flex flex-col gap-1.5 flex-1">
                    {{-- Badges --}}
                    <div class="flex flex-wrap gap-1">
                        @if($item->category)
                            <span class="text-xs font-semibold bg-red-100 text-red-700 px-2 py-0.5 rounded-full uppercase tracking-wide">
                                {{ $item->category }}
                            </span>
                        @endif
                        @if($item->type)
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $item->type === 'short' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ ucfirst($item->type) }}
                            </span>
                        @endif
                        @if($item->resolution)
                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">
                                {{ $item->resolution === 'audio' ? 'Audio' : strtoupper($item->resolution) }}
                            </span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <p class="font-semibold text-gray-800 text-sm leading-snug line-clamp-2" title="{{ $item->title }}">
                        {{ $item->title ?: 'Untitled' }}
                    </p>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between mt-auto pt-1.5 border-t border-gray-50 text-xs">
                        <span class="text-gray-400">{{ $item->created_at->format('M d, Y') }}</span>
                        <div class="flex items-center gap-2">
                            @if($media)
                                <a href="{{ $media->getUrl() }}" download="{{ $media->file_name }}"
                                    class="text-green-600 hover:text-green-800 font-medium">
                                    &#8595; {{ round($media->size / 1048576, 1) }}MB
                                </a>
                            @endif
                            <a href="{{ $item->link }}" target="_blank"
                                class="text-red-500 hover:text-red-700 font-medium">YT &#8599;</a>
                        </div>
                    </div>
                </div>

            </div>
            @endforeach
        </div>

        {{-- Pagination (only when not using limit) --}}
        @if($limit === 0)
            <div class="mt-8">
                {{ $this->videos->links() }}
            </div>
        @endif
    @endif
</div>
