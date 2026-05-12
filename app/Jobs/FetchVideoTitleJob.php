<?php

namespace App\Jobs;

use App\Models\Download;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;

class FetchVideoTitleJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;
    public int $tries   = 2;

    public function __construct(public Download $download) {}

    public function handle(): void
    {
        $title = self::fetchTitle($this->download->link);

        if ($title) {
            $this->download->update(['title' => $title]);
        }
    }

    public static function fetchTitle(string $url): ?string
    {
        $process = new Process([
            '/usr/local/bin/yt-dlp',
            '--no-playlist',
            '--print', '%(title)s',
            $url,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $title = trim($process->getOutput());

        return $title !== '' ? $title : null;
    }
}
