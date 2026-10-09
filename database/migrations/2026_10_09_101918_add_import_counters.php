
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_imports', function (Blueprint $table) {
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
        });

        Schema::table('application_import_batches', function (Blueprint $table) {
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('application_import_batches', function (Blueprint $table) {
            $table->dropColumn(['imported_rows', 'skipped_rows']);
        });

        Schema::table('application_imports', function (Blueprint $table) {
            $table->dropColumn(['imported_rows', 'skipped_rows']);
        });
    }
};
