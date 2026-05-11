<?php

namespace App\Http\Controllers;

use App\Jobs\DownloadVideoJob;
use App\Models\Download;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index()
    {
        return view('downloads.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'link'     => 'required|url',
            'title'    => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'type'     => 'nullable|in:short,video',
        ]);

        Download::create([
            'link'     => $request->link,
            'title'    => $request->title,
            'category' => $request->category,
            'type'     => $request->type,
            'status'   => 'pending',
        ]);

        return redirect()->route('downloads.index')->with('success', 'Link saved successfully.');
    }

    public function download(Download $download)
    {
        if ($download->status === 'done') {
            return back()->with('error', 'Already downloaded.');
        }

        if (!file_exists('/usr/local/bin/yt-dlp')) {
            return back()->with('error', 'yt-dlp is not installed. Run: brew install yt-dlp');
        }

        $download->update(['status' => 'pending']);
        DownloadVideoJob::dispatch($download);

        return back()->with('success', 'Download queued! Check back shortly.');
    }

    public function destroy(Download $download)
    {
        $download->clearMediaCollection('videos');
        $download->delete();

        return redirect()->route('downloads.index')->with('success', 'Entry deleted.');
    }
}
