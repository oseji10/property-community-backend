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
        Schema::table('properties', function (Blueprint $table) {
            // Add views column if it doesn't exist
            if (!Schema::hasColumn('properties', 'views')) {
                $table->unsignedBigInteger('views')->default(0)->after('featuredUntil');
            }
            
            // Add promotionPackageId to link with promotion packages
            if (!Schema::hasColumn('properties', 'promotionPackageId')) {
                $table->unsignedBigInteger('promotionPackageId')->nullable()->after('featuredUntil');
                $table->foreign('promotionPackageId')->references('packageId')->on('properties_promotion_packages')->onDelete('set null');
            }
            
            // Add average_rating column for property ratings
            if (!Schema::hasColumn('properties', 'average_rating')) {
                $table->decimal('average_rating', 3, 1)->nullable()->after('views');
            }
            
            // Add total_ratings column
            if (!Schema::hasColumn('properties', 'total_ratings')) {
                $table->unsignedInteger('total_ratings')->default(0)->after('average_rating');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['promotionPackageId']);
            $table->dropColumn(['views', 'promotionPackageId', 'average_rating', 'total_ratings']);
        });
    }
};