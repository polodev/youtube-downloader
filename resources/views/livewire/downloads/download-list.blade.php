<?php

use App\Jobs\DownloadVideoJob;
use App\Jobs\FetchVideoTitleJob;
use App\Models\Download;
use function Livewire\Volt\{state, computed, on, action};

state([
    'refreshKey'       => 0,
    'pickerOpen'       => false,
    'pickerDownloadId' => null,
    'pickerResolution' => 'best',
    'directRunning'    => false,
]);

$downloads = computed(function () {
    $this->refreshKey;
    return Download::with('media')->latest()->get();
});

$refresh = action(function () {
    $this->refreshKey++;
});

on([
    'download-updated' => function () { $this->refreshKey++; },
    'download-queued'  => function () { $this->refreshKey++; },
]);

$openPicker = action(function (int $id) {
    $this->pickerDownloadId = $id;
    $this->pickerResolution = 'best';
    $this->pickerOpen       = true;
});

$closePicker = action(function () {
    $this->pickerOpen = false;
});

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
    $title = FetchVideoTitleJob::fetchTitle($download->link);
    if ($title) {
        $download->update(['title' => $title]);
    }
    $this->refreshKey++;
});

?>

<div @if($this->downloads->whereIn('status', ['pending','downloading'])->isNotEmpty() || $directRunning) wire:poll.3s="refresh" @endif>

    {{-- Edit modal (separate Volt component) --}}
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
                        <span wire:loading.remove wire:target="directDownload">&#9654; Download Now</span>
                        <span wire:loading wire:target="directDownload">Downloading…</span>
                    </button>
                    <button wire:click="queueDownload"
                        class="flex-1 px-4 py-2 text-sm bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition text-center">
                        &#128338; Queue
                    </button>
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    <strong>Download Now</strong> runs immediately (browser waits). <strong>Queue</strong> runs in background via worker.
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

    {{-- List --}}
    @if($this->downloads->isEmpty())
        <div class="px-6 py-10 text-center text-gray-400">
            No links saved yet. Add one above.
        </div>
    @else
        @foreach($this->downloads as $item)
        <div class="px-6 py-4 border-b border-gray-50 flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800 truncate">{{ $item->title ?: 'Untitled' }}</p>
                <a href="{{ $item->link }}" target="_blank"
                    class="text-sm text-blue-500 hover:underline truncate block">{{ $item->link }}</a>

                <div class="mt-1 flex flex-wrap gap-1">
                    @if($item->category)
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $item->category }}</span>
                    @endif
                    @if($item->type)
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $item->type === 'short' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($item->type) }}
                        </span>
                    @endif
                    @if($item->resolution)
                        <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">
                            {{ $item->resolution === 'audio' ? 'Audio Only' : strtoupper($item->resolution) }}
                        </span>
                    @endif
                </div>

                @if($item->hasMedia('videos'))
                    @php $media = $item->getFirstMedia('videos'); @endphp
                    <div class="mt-2">
                        <a href="{{ $media->getUrl() }}" download
                            class="inline-flex items-center gap-1 text-sm text-green-600 hover:text-green-800 font-medium">
                            &#8595; {{ $media->file_name }}
                        </a>
                        <span class="text-xs text-gray-400 ml-2">{{ round($media->size / 1048576, 1) }} MB</span>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @php
                    $statusColors = [
                        'pending'     => 'bg-yellow-100 text-yellow-700',
                        'downloading' => 'bg-blue-100 text-blue-700',
                        'done'        => 'bg-green-100 text-green-700',
                        'failed'      => 'bg-red-100 text-red-700',
                    ];
                @endphp
                <span class="text-xs px-2 py-1 rounded-full font-medium {{ $statusColors[$item->status] ?? '' }}">
                    {{ ucfirst($item->status) }}
                </span>

                <button wire:click="triggerEdit({{ $item->id }})"
                    class="text-gray-400 hover:text-blue-500 text-sm px-2 py-1.5 transition" title="Edit">
                    &#9998;
                </button>

                <button wire:click="fetchTitle({{ $item->id }})"
                    wire:loading.attr="disabled" wire:target="fetchTitle({{ $item->id }})"
                    class="text-gray-400 hover:text-purple-500 text-sm px-2 py-1.5 transition disabled:opacity-40"
                    title="Fetch title from YouTube">
                    <span wire:loading.remove wire:target="fetchTitle({{ $item->id }})">&#127987;</span>
                    <span wire:loading wire:target="fetchTitle({{ $item->id }})">…</span>
                </button>

                @if(in_array($item->status, ['pending', 'failed']))
                    <button wire:click="openPicker({{ $item->id }})"
                        class="bg-red-500 hover:bg-red-600 text-white text-sm px-3 py-1.5 rounded-lg transition">
                        Download
                    </button>
                @endif

                @if($item->status === 'done')
                    <button wire:click="openPicker({{ $item->id }})"
                        class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-3 py-1.5 rounded-lg transition"
                        title="Download again with different resolution">
                        &#8635; Re-dl
                    </button>
                @endif

                <form action="{{ route('downloads.destroy', $item) }}" method="POST"
                    onsubmit="return confirm('Delete this entry?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="text-gray-400 hover:text-red-500 text-sm px-2 py-1.5 transition">&times;</button>
                </form>
            </div>
        </div>
        @endforeach
    @endif
</div>
