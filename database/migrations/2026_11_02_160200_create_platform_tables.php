<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-cutting tables: workflow history, attachments, saved filters, publications,
 * export logs, researcher profiles, autosaved drafts, recently used values and the
 * legacy-migration bookkeeping tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('from_status', 16)->nullable();
            $table->string('to_status', 16);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->jsonb('snapshot')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('disk', 32);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('size');
            $table->string('description')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('resource', 64);
            $table->string('name');
            $table->jsonb('filters');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->index(['resource', 'user_id']);
        });

        Schema::create('publications', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type', 24);
            $table->string('title_sq');
            $table->string('title_en')->nullable();
            $table->string('title_sr')->nullable();
            $table->text('summary_sq')->nullable();
            $table->text('summary_en')->nullable();
            $table->text('summary_sr')->nullable();
            $table->date('published_on')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->string('file_disk', 32)->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('context', 16)->index();
            $table->string('resource', 64);
            $table->string('format', 8);
            $table->jsonb('filters')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedBigInteger('token_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('researcher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('institution');
            $table->string('position')->nullable();
            $table->text('research_purpose');
            $table->string('status', 16)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->string('agreement_version', 16)->nullable();
            $table->timestamp('agreement_accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('form_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('form_key', 128);
            // Encrypted (may contain party names typed into a form).
            $table->text('data');
            $table->timestamps();

            $table->unique(['user_id', 'form_key']);
        });

        Schema::create('user_recent_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('field', 64);
            $table->unsignedBigInteger('value_id');
            $table->timestamp('used_at');

            $table->unique(['user_id', 'field', 'value_id']);
            $table->index(['user_id', 'field', 'used_at']);
        });

        Schema::create('legacy_review_items', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_module', 64)->index();
            $table->string('legacy_table', 64);
            $table->string('legacy_id');
            $table->string('field', 64)->default('');
            $table->text('value')->nullable();
            $table->string('reason', 64);
            // Encrypted: the raw legacy row can contain personal names.
            $table->text('payload')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->unique(['legacy_table', 'legacy_id', 'field', 'reason']);
        });

        Schema::create('legacy_id_map', function (Blueprint $table) {
            $table->id();
            $table->string('legacy_table', 64);
            $table->string('legacy_key');
            $table->string('target_type', 64);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['legacy_table', 'legacy_key', 'target_type']);
        });
    }

    public function down(): void
    {
        foreach ([
            'legacy_id_map', 'legacy_review_items', 'user_recent_values', 'form_drafts', 'researcher_profiles',
            'export_logs', 'publications', 'saved_filters', 'attachments', 'workflow_transitions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
