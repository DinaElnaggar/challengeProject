<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamps();

            $table->index(['organization_id', 'logged_in_at']);
            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_events');
    }
};

