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
        Schema::table('docs', function (Blueprint $table) {
            $table->dropUnique('docs_slug_unique');

            $table->string('version')->default('main')->after('id');

            $table->unique(['version', 'slug']);

            $table->index('version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docs', function (Blueprint $table) {
            $table->dropIndex('docs_version_slug_unique');
            $table->dropIndex('docs_version_index');
            $table->dropColumn('version');

            // Restore the original unique constraint on slug
            $table->unique('slug');
        });
    }
};
