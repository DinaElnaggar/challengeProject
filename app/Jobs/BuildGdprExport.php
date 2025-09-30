<?php

namespace App\Jobs;

use App\Models\GdprExport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BuildGdprExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $exportId)
    {
    }

    public function handle(): void
    {
        $export = GdprExport::find($this->exportId);
        if (! $export) {
            return;
        }

        $export->status = 'processing';
        $export->save();

        $user = User::find($export->user_id);

        // Build JSON files content
        $data = [
            'user' => $user ? $user->toArray() : null,
            // Extend: add related data (organizations, roles, permissions, etc.)
        ];

        $tempDir = storage_path('app/gdpr_exports/' . Str::uuid());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        file_put_contents($tempDir . '/user.json', json_encode($data['user'], JSON_PRETTY_PRINT));

        // Zip the files
        $zipPath = $tempDir . '/export.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($tempDir . '/user.json', 'user.json');
            $zip->close();
        } else {
            $export->status = 'failed';
            $export->save();
            return;
        }

        // Move to persistent storage
        $diskPath = 'gdpr_exports/' . basename($tempDir) . '.zip';
        Storage::disk('local')->put($diskPath, file_get_contents($zipPath));

        $export->status = 'ready';
        $export->file_path = $diskPath;
        $export->download_token = Str::random(64);
        $export->save();

        // Email user with download link
        if ($user) {
            $link = url('/api/users/' . $user->id . '/export/download?token=' . $export->download_token);
            Mail::raw("Your data export is ready. Download: {$link}", function ($message) use ($user) {
                $message->to($user->email)->subject('Your data export is ready');
            });
        }
    }
}

