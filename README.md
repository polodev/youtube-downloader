# YouTube Downloader

A self-hosted Laravel application to save YouTube links and download videos locally. Supports resolution selection, a card-based gallery UI, and queue-based background downloading.

## Features

- Save YouTube links with optional title, category, and type (Video / Short)
- Auto-fetch video title from YouTube metadata via yt-dlp (on save and on demand)
- Choose download resolution before queueing — Best, 4K, 1080p, 720p, 480p, 360p, or Audio Only
- **Queue** downloads in the background via Laravel queues (browser doesn't wait)
- **Download Now** for immediate synchronous downloads with a loading overlay
- Download English captions as SRT files when YouTube provides manual or auto captions
- Re-download any video at a different resolution
- Media stored and managed via [Spatie Laravel Media Library](https://github.com/spatie/laravel-medialibrary)
- Edit saved links — changing the URL automatically clears the old file and resets status
- Card-based gallery UI on the homepage (latest 30) and Video Gallery page (all)
- Action buttons on each card — edit, fetch title, download/re-download, delete
- Status placeholders on cards for pending / downloading / failed states
- Video Gallery page with HTML5 player, filterable by type, category, and date order
- Paginated gallery — 30 videos per page
- Livewire Volt reactive UI — no page reloads, live status polling while downloads run
- Powered by [yt-dlp](https://github.com/yt-dlp/yt-dlp) + [ffmpeg](https://ffmpeg.org/) for high-quality video/audio merging

## Requirements

- PHP 8.2+
- Composer
- MySQL
- [yt-dlp](https://github.com/yt-dlp/yt-dlp)
- [ffmpeg](https://ffmpeg.org/)
- [Laravel Herd](https://herd.laravel.com/) or any local PHP server (Valet, Sail, `php artisan serve`)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/polodev/youtube-downloader.git
cd youtube-downloader
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=youtube_downloader
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Create the database

```bash
mysql -u root -e "CREATE DATABASE youtube_downloader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run migrations and create the storage symlink

```bash
php artisan migrate
php artisan storage:link
```

### 6. Install yt-dlp and ffmpeg

**macOS:**
```bash
brew install yt-dlp ffmpeg
```

**Linux:**
```bash
sudo apt install ffmpeg
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp
```

> If `yt-dlp` is not at `/usr/local/bin/yt-dlp`, update the path in `app/Jobs/DownloadVideoJob.php`.

### 7. Serve the application

**With Herd / Valet:**
```bash
herd link youtube-downloader
herd secure youtube-downloader
# Visit https://youtube-downloader.test
```

**Or with the built-in server:**
```bash
php artisan serve
# Visit http://localhost:8000
```

### 8. Start the queue worker

Downloads run in the background via Laravel queues. Keep this running in a terminal:

```bash
php artisan queue:work database --timeout=1200 --memory=2048 --tries=3
```

## Usage

1. Open the app and paste a YouTube URL into **Add YouTube Link**
2. Optionally fill in Title, Category, and Type (Video / Short) — title will be fetched from YouTube automatically if left blank
3. Click **Save Link**
4. On the homepage card grid, click **↓ DL** on any card, choose a resolution, then **Queue** (background) or **Now** (immediate)
5. Status updates live on the cards while the download runs
6. Once done, the card shows an inline video player with download and YT links
7. Visit **Video Gallery** to browse all downloaded videos with filters

## Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 |
| Reactive UI | Livewire 4 + Volt |
| Media storage | Spatie Laravel Media Library 11 |
| Downloader | yt-dlp |
| Video merge | ffmpeg |
| Database | MySQL |
| Styling | Tailwind CSS (CDN) |
