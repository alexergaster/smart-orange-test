<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->string('external_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('product')->nullable();
            $table->decimal('budget_uah', 12, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('manager')->nullable();
            $table->text('comment')->nullable();

            $table->index('email');
            $table->index('phone');
            $table->index('status');

            $table->dateTime('created_at');
            $table->dateTime('next_contact_at')->nullable();
            $table->timestamp('imported_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
