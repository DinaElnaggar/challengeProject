<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'organization_id', 'date']);
            $table->index(['organization_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_daily');
    }
};

