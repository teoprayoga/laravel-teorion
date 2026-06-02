<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_private')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->date('event_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('body');
            $table->integer('score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
    }
};
