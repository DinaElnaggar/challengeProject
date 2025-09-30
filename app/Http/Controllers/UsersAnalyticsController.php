<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Models\LoginDaily;
use App\Models\LoginEvent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsersAnalyticsController extends Controller
{
    use ApiResponses;

    // GET /api/users/top-logins?window=7d|30d (per org; from aggregates, fall back to events)
    public function topLogins(Request $request)
    {
        $request->validate([
            'org_id' => 'required|integer|exists:organizations,id',
            'window' => 'nullable|string|in:7d,30d',
        ]);
        $orgId = (int) $request->input('org_id');
        $window = $request->input('window', '7d');
        $days = $window === '30d' ? 30 : 7;
        $since = now()->subDays($days)->toDateString();

        // Prefer aggregates
        $aggregate = DB::table('login_daily')
            ->select('user_id', DB::raw('SUM(count) as total'))
            ->where('organization_id', $orgId)
            ->where('date', '>=', $since)
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        if ($aggregate->isEmpty()) {
            // Fallback to events if no aggregates yet
            $aggregate = DB::table('login_events')
                ->select('user_id', DB::raw('COUNT(*) as total'))
                ->where('organization_id', $orgId)
                ->where('logged_in_at', '>=', now()->subDays($days))
                ->groupBy('user_id')
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        }

        $userIds = $aggregate->pluck('user_id')->all();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $data = $aggregate->map(function ($row) use ($users) {
            $u = $users[$row->user_id] ?? null;
            return [
                'user_id' => $row->user_id,
                'name' => $u?->name,
                'email' => $u?->email,
                'logins' => (int) $row->total,
            ];
        })->values();

        return $this->success(['top' => $data]);
    }

    // GET /api/users/inactive?window=hour|day|week|month (org-scoped; cursor-paginated)
    public function inactive(Request $request)
    {
        $request->validate([
            'org_id' => 'required|integer|exists:organizations,id',
            'window' => 'nullable|string|in:hour,day,week,month',
            'cursor' => 'nullable',
        ]);
        $orgId = (int) $request->input('org_id');
        $window = $request->input('window', 'week');

        $threshold = match ($window) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => now()->subWeek(),
        };

        // Users in org whose last_login_at is null or older than threshold
        $query = User::select('users.id', 'users.name', 'users.email', 'users.last_login_at')
            ->join('organization_user_roles as our', 'our.user_id', '=', 'users.id')
            ->where('our.organization_id', $orgId)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('users.last_login_at')
                  ->orWhere('users.last_login_at', '<', $threshold);
            })
            ->orderBy('users.last_login_at', 'asc')
            ->orderBy('users.id', 'asc');

        $perPage = 25;
        $results = $query->cursorPaginate($perPage, ['*'], 'cursor', $request->input('cursor'));

        return $this->success([
            'data' => $results->items(),
            'next_cursor' => $results->nextCursor()?->encode(),
            'prev_cursor' => $results->previousCursor()?->encode(),
        ]);
    }
}

