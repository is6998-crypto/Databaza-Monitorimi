<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reference data (proposal B5): admin-managed, soft-deletable, bilingual (+ Serbian-ready),
 * with an is_active flag. legacy_id keeps the link to the legacy system for re-runnable imports.
 */
return new class extends Migration
{
    public function up(): void
    {
        $names = function (Blueprint $table, string $prefix = 'name'): void {
            $table->string("{$prefix}_sq");
            $table->string("{$prefix}_en")->nullable();
            $table->string("{$prefix}_sr")->nullable();
        };

        $common = function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
            $table->string('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        };

        Schema::create('courts', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $names($table);
            $table->string('code', 32)->nullable()->unique();
            $table->string('level', 16)->index();
            $table->string('city')->nullable();
            $common($table);
        });

        Schema::create('court_branches', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts');
            $names($table);
            $table->string('city')->nullable();
            $common($table);
        });

        Schema::create('departments', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $names($table);
            $table->string('code', 32)->nullable();
            $common($table);
        });

        Schema::create('divisions', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $names($table);
            $common($table);
        });

        Schema::create('prosecution_offices', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $names($table);
            $table->string('level', 16)->index();
            $table->string('city')->nullable();
            $common($table);
        });

        // People: names are not translated. They are internal only and never published.
        Schema::create('judges', function (Blueprint $table) use ($common) {
            $table->id();
            $table->string('name');
            $table->foreignId('court_id')->nullable()->constrained('courts');
            $common($table);
        });

        Schema::create('prosecutors', function (Blueprint $table) use ($common) {
            $table->id();
            $table->string('name');
            $table->foreignId('prosecution_office_id')->nullable()->constrained('prosecution_offices');
            $common($table);
        });

        Schema::create('monitors', function (Blueprint $table) use ($common) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->string('code', 32)->nullable();
            $table->string('phone', 32)->nullable();
            $common($table);
        });

        Schema::create('court_monitor', function (Blueprint $table) {
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->foreignId('monitor_id')->constrained('monitors')->cascadeOnDelete();
            $table->primary(['court_id', 'monitor_id']);
        });

        Schema::create('applicants', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $names($table);
            $common($table);
        });

        Schema::create('criminal_codes', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $table->string('code', 32)->unique();
            $names($table);
            $table->unsignedSmallInteger('year')->nullable();
            $common($table);
        });

        Schema::create('categories', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $names($table);
            $common($table);
        });

        Schema::create('criminal_offences', function (Blueprint $table) use ($names, $common) {
            $table->id();
            $table->foreignId('criminal_code_id')->constrained('criminal_codes');
            $table->string('article_number', 32);
            $names($table, 'title');
            $table->string('chapter')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $common($table);
            $table->index(['criminal_code_id', 'article_number']);
        });

        foreach (['nationalities', 'defendant_positions', 'economic_statuses', 'security_measure_types'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($names, $common) {
                $table->id();
                $names($table);
                $common($table);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'security_measure_types', 'economic_statuses', 'defendant_positions', 'nationalities',
            'criminal_offences', 'categories', 'criminal_codes', 'applicants', 'court_monitor', 'monitors',
            'prosecutors', 'judges', 'prosecution_offices', 'divisions', 'departments', 'court_branches', 'courts',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
