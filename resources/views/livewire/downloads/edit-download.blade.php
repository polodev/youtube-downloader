<?php

use App\Models\Download;
use function Livewire\Volt\{state, action, on};

state([
    'downloadId' => null,
    'title'      => '',
    'link'       => '',
    'category'   => '',
    'type'       => '',
    'open'       => false,
]);

on(['trigger-edit' => function (int $id) {
    $download = Download::findOrFail($id);
    $this->downloadId = $download->id;
    $this->title      = $download->title ?? '';
    $this->link       = $download->link ?? '';
    $this->category   = $download->category ?? '';
    $this->type       = $download->type ?? '';
    $this->open       = true;
}]);

$save = action(function () {
    $this->validate([
        'link'     => 'required|url',
        'title'    => 'nullable|string|max:255',
        'category' => 'nullable|string|max:255',
        'type'     => 'nullable|in:short,video',
    ]);

    $download = Download::findOrFail($this->downloadId);
    $linkChanged = $download->link !== $this->link;

    if ($linkChanged) {
        $download->clearMediaCollection('videos');
    }

    $download->update([
        'title'      => $this->title ?: null,
        'link'       => $this->link,
        'category'   => $this->category ?: null,
        'type'       => $this->type ?: null,
        'resolution' => $linkChanged ? null : $download->resolution,
        'status'     => $linkChanged ? 'pending' : $download->status,
    ]);

    $this->open = false;
    $this->dispatch('download-updated');
});

$close = action(function () {
    $this->open = false;
});

?>

<div>
    @if($open)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 mx-4">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-800">Edit Entry</h3>
                <button wire:click="close" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">YouTube Link <span class="text-red-500">*</span></label>
                    <input type="url" wire:model="link"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                    @error('link') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Title</label>
                    <input type="text" wire:model="title" placeholder="Video title"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Category</label>
                        <input type="text" wire:model="category" placeholder="e.g. Music"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Type</label>
                        <select wire:model="type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400">
                            <option value="">— select —</option>
                            <option value="video">Video</option>
                            <option value="short">Short</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button wire:click="close"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button wire:click="save"
                    class="px-4 py-2 text-sm bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg transition">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
