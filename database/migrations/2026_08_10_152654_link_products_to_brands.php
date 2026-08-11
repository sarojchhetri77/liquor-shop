<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Replace the free-text `brand` column with a relation to the new
     * `brands` table, promoting every distinct existing value to a brand.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });

        $names = DB::table('products')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->pluck('brand');

        foreach ($names as $name) {
            $brandId = DB::table('brands')->insertGetId([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('products')->where('brand', $name)->update(['brand_id' => $brandId]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('category_id');
        });

        DB::table('products')
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->update(['products.brand' => DB::raw('brands.name')]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('brand_id');
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $suffix = 1;

        while (DB::table('brands')->where('slug', $slug)->exists()) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
};
