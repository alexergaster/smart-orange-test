<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('processed_batches')->default(0);
            $table->string('status', 20)->default('processing');
            $table->timestamps();
        });

        Schema::create('application_import_batches', function (Blueprint $table) {
            $table->id();

            $table->uuid('import_id');
            $table->unsignedInteger('batch_index');
            $table->unsignedInteger('rows_count');
            $table->char('checksum', 64);

            $table->foreign('import_id')
                ->references('id')
                ->on('application_imports')
                ->cascadeOnDelete();

            $table->unique(['import_id', 'batch_index']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->uuid('import_id')->nullable();
            $table->unsignedInteger('import_row_number')->nullable();

            $table->index('import_id');

            $table->unique([
                'import_id',
                'import_row_number',
            ], 'applications_import_row_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique('applications_import_row_unique');
            $table->dropIndex(['import_id']);
            $table->dropColumn([
                'import_id',
                'import_row_number',
            ]);
        });

        Schema::dropIfExists('application_import_batches');
        Schema::dropIfExists('application_imports');

    }
};
