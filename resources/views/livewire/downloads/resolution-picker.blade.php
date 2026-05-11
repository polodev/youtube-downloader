<?php

use App\Jobs\DownloadVideoJob;
use App\Models\Download;
use function Livewire\Volt\{state, action, on};

state([
    'downloadId' => null,
    'resolution' => 'best',
    'open'       => false,
]);

$resolutions = [
    'best'  => ['label' => 'Best Quality',    'note' => 'Highest available (auto-merge)'],
    '2160p' => ['label' => '4K — 2160p',      'note' => 'Ultra HD'],
    '1080p' => ['label' => 'Full HD — 1080p', 'note' => 'Recommended'],
    '720p'  => ['label' => 'HD — 720p',       'note' => ''],
    '480p'  => ['label' => '480p',            'note' => 'Standard'],
    '360p'  => ['label' => '360p',            'note' => 'Low quality'],
    'audio' => ['label' => 'Audio Only',      'note' => 'Best audio stream'],
];

on(['trigger-resolution-picker' => function (int $id) {
    $this->downloadId = $id;
    $this->resolution = 'best';
    $this->open       = true;
}]);

$confirm = action(function () use ($resolutions) {
    $download = Download::findOrFail($this->downloadId);
    $download->update([
        'status'     => 'pending',
        'resolution' => $this->resolution,
    ]);
    DownloadVideoJob::dispatch($download, $this->resolution);
    $this->open = false;
    $this->dispatch('download-queued');
});

$close = action(function () {
    $this->open = false;
});

?>

<div>
    @if($open)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">Choose Resolution</h3>
                <button wire:click="close" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="px-6 py-4 space-y-2">
                @foreach([
                    'best'  => ['label' => 'Best Quality',    'note' => 'Highest available'],
                    '2160p' => ['label' => '4K — 2160p',      'note' => 'Ultra HD'],
                    '1080p' => ['label' => 'Full HD — 1080p', 'note' => 'Recommended'],
                    '720p'  => ['label' => 'HD — 720p',       'note' => ''],
                    '480p'  => ['label' => '480p',            'note' => 'Standard'],
                    '360p'  => ['label' => '360p',            'note' => 'Low quality'],
                    'audio' => ['label' => 'Audio Only',      'note' => 'Best audio stream'],
                ] as $value => $opt)
                    <label class="flex items-center gap-3 p-3 rounded-lg cursor-pointer border-2 transition
                        {{ $resolution === $value ? 'border-red-400 bg-red-50' : 'border-transparent hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="resolution" value="{{ $value }}" class="accent-red-500">
                        <div class="flex-1">
                            <span class="font-medium text-gray-800 text-sm">{{ $opt['label'] }}</span>
                            @if($opt['note'])
                                <span class="text-xs text-gray-400 ml-2">{{ $opt['note'] }}</span>
                            @endif
                        </div>
                        @if($value === '1080p')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Popular</span>
                        @endif
                        @if($value === 'best')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Default</span>
                        @endif
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100">
                <button wire:click="close"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="confirm"
                    class="px-5 py-2 text-sm bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition">
                    Queue Download
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
