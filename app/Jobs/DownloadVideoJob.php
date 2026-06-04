<?php

namespace App\Jobs;

use App\Models\Download;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;
use App\Jobs\FetchVideoTitleJob;

class DownloadVideoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries = 2;

    const FORMAT_MAP = [
        'best'  => 'bestvideo[vcodec^=avc1][ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best',
        '2160p' => 'bestvideo[vcodec^=avc1][ext=mp4][height<=2160]+bestaudio[ext=m4a]/best[ext=mp4][height<=2160]/best[height<=2160]',
        '1080p' => 'bestvideo[vcodec^=avc1][ext=mp4][height<=1080]+bestaudio[ext=m4a]/best[ext=mp4][height<=1080]/best[height<=1080]',
        '720p'  => 'bestvideo[vcodec^=avc1][ext=mp4][height<=720]+bestaudio[ext=m4a]/best[ext=mp4][height<=720]/best[height<=720]',
        '480p'  => 'bestvideo[vcodec^=avc1][ext=mp4][height<=480]+bestaudio[ext=m4a]/best[ext=mp4][height<=480]/best[height<=480]',
        '360p'  => 'bestvideo[vcodec^=avc1][ext=mp4][height<=360]+bestaudio[ext=m4a]/best[ext=mp4][height<=360]/best[height<=360]',
        'audio' => 'bestaudio[ext=m4a]/bestaudio/best',
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

        // Use --print after_move to get the exact final file path reliably.
        $command = [
            '/usr/local/bin/yt-dlp',
            '--no-playlist',
            '--ffmpeg-location', '/usr/local/bin/ffmpeg',
            '-f', $format,
            '--write-subs',
            '--write-auto-subs',
            '--sub-langs', 'en.*',
            '--convert-subs', 'srt',
        ];

        if ($this->resolution !== 'audio') {
            $command[] = '--merge-output-format';
            $command[] = 'mp4';
        }

        $command = [
            ...$command,
            '--print', 'after_move:%(filepath)s',
            '-o', $template,
            $this->download->link,
        ];

        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->download->update(['status' => 'failed']);
            return;
        }

        $filePath = $this->extractDownloadedMediaPath($process->getOutput());

        if ($filePath && file_exists($filePath)) {
            $this->download->clearMediaCollection('videos');
            $this->download->addMedia($filePath)->toMediaCollection('videos');

            if ($captionPath = $this->findCaptionPath($filePath)) {
                $this->download->clearMediaCollection('captions');
                $this->download->addMedia($captionPath)->toMediaCollection('captions');
            } else {
                $this->download->clearMediaCollection('captions');
            }

            $realTitle = FetchVideoTitleJob::fetchTitle($this->download->link)
                ?? pathinfo($filePath, PATHINFO_FILENAME);

            $this->download->update([
                'title'  => $realTitle,
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

    private function extractDownloadedMediaPath(string $output): ?string
    {
        $mediaExtensions = ['mp4', 'm4a', 'webm', 'mkv', 'mp3'];

        foreach (array_reverse(preg_split('/\R/', trim($output)) ?: []) as $line) {
            $path = trim($line);

            if (
                $path !== ''
                && file_exists($path)
                && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $mediaExtensions, true)
            ) {
                return $path;
            }
        }

        return null;
    }

    private function findCaptionPath(string $mediaPath): ?string
    {
        $basePath = preg_replace('/\.[^.]+$/', '', $mediaPath);

        if (!$basePath) {
            return null;
        }

        $captionPaths = [
            ...(glob($basePath . '.srt') ?: []),
            ...(glob($basePath . '.*.srt') ?: []),
        ];

        return collect($captionPaths)
            ->sortBy(fn(string $path) => str_ends_with($path, '.en.srt') ? 0 : 1)
            ->first();
    }
}
