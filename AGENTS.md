<claude-mem-context>
# Memory Context

# [yt-downloader] recent context, 2026-05-12 2:00pm GMT+6

Legend: 🎯session 🔴bugfix 🟣feature 🔄refactor ✅change 🔵discovery ⚖️decision 🚨security_alert 🔐security_note
Format: ID TIME TYPE TITLE
Fetch details: get_observations([IDs]) | Search: mem-search skill

Stats: 13 obs (5,109t read) | 236,742t work | 98% savings

### May 12, 2026
590 1:38p 🔵 Laravel Project Folder Rename Breaks Video Functionality
591 " 🔵 Root Cause Identified: Laravel Herd Link Tied to Old Folder Name
592 " 🔵 HTTP vs HTTPS Mismatch: Herd Not Secured for New Domain Name
593 " 🔵 Pre-existing Spatie MediaLibrary FileIsTooBig Error in Queue Worker
594 1:39p 🔵 HTTPS Actually Works; Storage Symlink Already Points to Correct New Path
595 " 🔵 Spatie MediaLibrary max_file_size Already Fixed to 10 GB; Queue Worker Not Running
596 " 🔵 Video Serving Confirmed Working — System Fully Functional After Folder Rename
597 1:40p 🔵 Old youtube-downloader.test Domain Dead; New yt-downloader.test Fully Functional in Browser
598 " 🔵 Orphaned Herd Nginx Config and TLS Certificates for youtube-downloader.test Persist on Disk
599 1:41p 🔵 One Pending Job Stuck in Queue; yt-dlp and ffmpeg Binaries Confirmed at Hardcoded Paths
600 " 🔵 Pending Queue Job Is FetchVideoTitleJob, Not a Download — Video Already Completed
601 " 🟣 Laravel Debugbar Installed as Dev Dependency
602 1:45p 🟣 Laravel Debugbar v4.2.8 Successfully Installed

Access 237k tokens of past work via get_observations([IDs]) or mem-search skill.
</claude-mem-context>