<?php

namespace App\Jobs;

use App\Models\Download;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class DownloadVideoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries = 2;

    const FORMAT_MAP = [
        'best'  => 'bestvideo+bestaudio/best',
        '2160p' => 'bestvideo[height<=2160]+bestaudio/best[height<=2160]',
        '1080p' => 'bestvideo[height<=1080]+bestaudio/best[height<=1080]',
        '720p'  => 'bestvideo[height<=720]+bestaudio/best[height<=720]',
        '480p'  => 'bestvideo[height<=480]+bestaudio/best[height<=480]',
        '360p'  => 'bestvideo[height<=360]+bestaudio/best[height<=360]',
        'audio' => 'bestaudio/best',
    ];

    public function __construct(
        public Download $download,
        public string $resolution = 'best',
    ) {}

    public function handle(): void
    {
        $this->download->update(['status' => 'downloading']);

        $outputDir = storage_path('app/public/videos');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $format   = self::FORMAT_MAP[$this->resolution] ?? self::FORMAT_MAP['best'];
        $template = $outputDir . '/%(title)s.%(ext)s';

        // Use --print after_move to get the exact final file path reliably
        $process = new Process([
            '/usr/local/bin/yt-dlp',
            '--no-playlist',
            '--ffmpeg-location', '/usr/local/bin/ffmpeg',
            '-f', $format,
            '--print', 'after_move:%(filepath)s',
            '-o', $template,
            $this->download->link,
        ]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->download->update(['status' => 'failed']);
            return;
        }

        $filePath = trim($process->getOutput());

        if ($filePath && file_exists($filePath)) {
            // Clear any previous media before adding new
            $this->download->clearMediaCollection('videos');
            $this->download->addMedia($filePath)->toMediaCollection('videos');
            $this->download->update([
                'title'  => $this->download->title ?: pathinfo($filePath, PATHINFO_FILENAME),
                'status' => 'done',
            ]);
        } else {
            $this->download->update(['status' => 'failed']);
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->download->update(['status' => 'failed']);
    }
}
