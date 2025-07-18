<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('imageable');
            $table->string('type');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->string('mime_type');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->text('alt_text')->nullable();
            $table->integer('sort_order')->default(1);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id']);
            $table->index(['imageable_type', 'imageable_id', 'type']);
            $table->index(['imageable_type', 'imageable_id', 'is_primary']);
            $table->index(['imageable_type', 'imageable_id', 'is_active']);
            $table->index(['imageable_type', 'imageable_id', 'sort_order']);
            $table->index(['type', 'is_active']);
            $table->index('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};