<?php

namespace App\Jobs;

use App\Models\LoginEvent;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RecordLoginEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public ?int $organizationId,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $loggedInAt = null,
    ) {}

    public function handle(): void
    {
        $loggedAt = $this->loggedInAt ? \Illuminate\Support\Carbon::parse($this->loggedInAt) : now();

        DB::transaction(function () use ($loggedAt) {
            // Create event row
            LoginEvent::create([
                'user_id' => $this->userId,
                'organization_id' => $this->organizationId,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'logged_in_at' => $loggedAt,
            ]);

            // Update user's last_login_at and login_count atomically
            User::where('id', $this->userId)
                ->update([
                    'last_login_at' => $loggedAt,
                ]);
            DB::table('users')->where('id', $this->userId)->increment('login_count');
        });
    }
}

