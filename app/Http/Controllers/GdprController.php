<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Jobs\BuildGdprExport;
use App\Models\GdprExport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class GdprController extends Controller
{
    use ApiResponses;

    // POST /api/users/{id}/export -> queue export
    public function requestExport(Request $request, int $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->notFound('User not found');
        }
        if (auth('api')->id() !== $user->id) {
            Gate::authorize('users.update', $user->id);
        }

        $export = GdprExport::create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        BuildGdprExport::dispatch($export->id)->onQueue('default');

        return $this->success(['message' => 'Export requested']);
    }

    // GET /api/users/{id}/export/download?token=... -> one-time download
    public function downloadExport(Request $request, int $id)
    {
        $request->validate(['token' => 'required|string']);
        $export = GdprExport::where('user_id', $id)->where('download_token', $request->query('token'))->first();
        if (! $export || $export->status !== 'ready') {
            return $this->notFound('Export not found or not ready');
        }
        if ($export->downloaded_at) {
            return $this->fail('Export already downloaded');
        }
        if (! Storage::disk('local')->exists($export->file_path)) {
            return $this->notFound('File missing');
        }

        $export->downloaded_at = now();
        $export->save();

        return response()->download(storage_path('app/' . $export->file_path))->deleteFileAfterSend(false);
    }
}

