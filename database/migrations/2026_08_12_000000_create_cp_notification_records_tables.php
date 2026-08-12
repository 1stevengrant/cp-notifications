<?php

use Ghijk\CpNotifications\Repositories\EloquentAcknowledgementRepository;
use Ghijk\CpNotifications\Repositories\EloquentSnoozeRepository;
use Ghijk\CpNotifications\Repositories\EloquentNudgeDeliveryRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(EloquentAcknowledgementRepository::TABLE, function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('notification_id')->index();
            $table->string('user_id')->index();
            $table->dateTimeTz('acknowledged_at')->index();
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::create(EloquentSnoozeRepository::TABLE, function (Blueprint $table): void {
            $table->string('notification_id')->index();
            $table->string('user_id')->index();
            $table->dateTimeTz('snoozed_until')->index();
            $table->unique(['notification_id', 'user_id']);
        });

        Schema::create(EloquentNudgeDeliveryRepository::TABLE, function (Blueprint $table): void {
            $table->string('notification_id')->index();
            $table->string('user_id')->index();
            $table->dateTimeTz('last_sent_at')->index();
            $table->unsignedInteger('send_count');
            $table->unique(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(EloquentNudgeDeliveryRepository::TABLE);
        Schema::dropIfExists(EloquentSnoozeRepository::TABLE);
        Schema::dropIfExists(EloquentAcknowledgementRepository::TABLE);
    }
};
