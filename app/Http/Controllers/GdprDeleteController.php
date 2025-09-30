<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Models\GdprDeleteRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GdprDeleteController extends Controller
{
    use ApiResponses;

    // POST /api/users/{id}/gdpr-delete -> create a delete request
    public function create(Request $request, int $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->notFound('User not found');
        }
        if (auth('api')->id() !== $user->id) {
            Gate::authorize('users.delete', $user->id);
        }
        $req = GdprDeleteRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => $request->input('reason'),
        ]);
        return $this->success(['request' => $req]);
    }

    // POST /api/gdpr-delete/{requestId}/approve -> owner/admin approves
    public function approve(Request $request, int $requestId)
    {
        $req = GdprDeleteRequest::find($requestId);
        if (! $req || $req->status !== 'pending') {
            return $this->notFound('Request not found or not pending');
        }
        // Only owner/admin can approve (reuse users.delete gate for simplicity)
        Gate::authorize('users.delete', $req->user_id);
        $req->status = 'approved';
        $req->approved_by = auth('api')->id();
        $req->approved_at = now();
        $req->save();
        return $this->success(['request' => $req]);
    }

    // POST /api/gdpr-delete/{requestId}/reject
    public function reject(Request $request, int $requestId)
    {
        $req = GdprDeleteRequest::find($requestId);
        if (! $req || $req->status !== 'pending') {
            return $this->notFound('Request not found or not pending');
        }
        Gate::authorize('users.delete', $req->user_id);
        $req->status = 'rejected';
        $req->approved_by = auth('api')->id();
        $req->approved_at = now();
        $req->save();
        return $this->success(['request' => $req]);
    }

    // POST /api/gdpr-delete/{requestId}/process -> actually delete the user after approval
    public function process(Request $request, int $requestId)
    {
        $req = GdprDeleteRequest::find($requestId);
        if (! $req || $req->status !== 'approved') {
            return $this->fail('Request not approved');
        }
        Gate::authorize('users.delete', $req->user_id);
        $user = User::find($req->user_id);
        if (! $user) {
            return $this->notFound('User not found');
        }
        // Perform deletion: soft delete first; in real apps, scrub PII or hard-delete as policy
        $user->delete();
        $req->status = 'processed';
        $req->processed_at = now();
        $req->save();
        return $this->success(['message' => 'User deletion processed']);
    }
}

