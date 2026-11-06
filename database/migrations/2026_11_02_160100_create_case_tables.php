<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core entities (proposal B5). Administrative/civil/economic cases share these tables;
 * their type-specific fields live in administrative_case_details and civil_case_details.
 */
return new class extends Migration
{
    public function up(): void
    {
        $workflow = function (Blueprint $table): void {
            $table->string('status', 16)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('return_comment')->nullable();
        };

        Schema::create('cases', function (Blueprint $table) use ($workflow) {
            $table->id();
            $table->ulid('public_ref')->unique();
            $table->string('type', 32)->index();
            $table->foreignId('court_id')->nullable()->constrained('courts');
            $table->foreignId('court_branch_id')->nullable()->constrained('court_branches');
            $table->foreignId('department_id')->nullable()->constrained('departments');
            $table->foreignId('division_id')->nullable()->constrained('divisions');
            $table->foreignId('judge_id')->nullable()->constrained('judges');
            $table->foreignId('prosecutor_id')->nullable()->constrained('prosecutors');
            $table->foreignId('prosecution_office_id')->nullable()->constrained('prosecution_offices');
            $table->foreignId('assigned_monitor_id')->nullable()->constrained('monitors')->nullOnDelete();
            $table->string('court_case_number', 64)->nullable()->index();
            $table->string('indictment_number', 64)->nullable()->index();
            $table->string('police_case_number', 64)->nullable()->index();
            $table->text('summary')->nullable();
            $workflow($table);
            $table->string('legacy_module', 64)->nullable();
            $table->string('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['legacy_module', 'legacy_id']);
            $table->index(['court_id', 'court_case_number']);
        });

        Schema::create('case_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('role', 24)->index();
            // Encrypted at rest (Laravel encrypted cast). Never exposed outside the staff panel.
            $table->text('name')->nullable();
            // Blind index (HMAC) of the normalised full name and of each name token, for exact search.
            $table->string('name_index', 64)->nullable()->index();
            $table->text('name_tokens')->nullable();
            $table->string('gender', 16)->nullable()->index();
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities');
            $table->foreignId('economic_status_id')->nullable()->constrained('economic_statuses');
            $table->foreignId('defendant_position_id')->nullable()->constrained('defendant_positions');
            $table->string('counsel_type', 16)->nullable();
            $table->boolean('counsel_at_police')->nullable();
            $table->string('legacy_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('case_party_id')->constrained('case_parties')->cascadeOnDelete();
            $table->foreignId('criminal_offence_id')->constrained('criminal_offences');
            $table->string('stage', 24)->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['case_party_id', 'criminal_offence_id', 'stage']);
        });

        Schema::create('case_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->string('stage', 48);
            $table->date('date')->nullable();
            $table->string('outcome', 32)->nullable();
            $table->foreignId('applicant_id')->nullable()->constrained('applicants');
            $table->foreignId('prosecutor_id')->nullable()->constrained('prosecutors');
            $table->boolean('is_direct_indictment')->default(false);
            $table->string('appellant', 24)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['case_id', 'stage']);
            $table->index(['stage', 'date']);
        });

        Schema::create('hearings', function (Blueprint $table) use ($workflow) {
            $table->id();
            $table->ulid('public_ref')->unique();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('monitor_id')->nullable()->constrained('monitors')->nullOnDelete();
            $table->date('hearing_date')->index();
            $table->string('hearing_type', 32)->nullable();
            $table->foreignId('court_id')->nullable()->constrained('courts');
            $table->foreignId('judge_id')->nullable()->constrained('judges');
            $table->foreignId('prosecutor_id')->nullable()->constrained('prosecutors');
            $table->string('venue', 16)->nullable();
            $table->boolean('is_public')->nullable();
            $table->boolean('started_late')->default(false);
            $table->unsignedSmallInteger('delay_minutes')->nullable();
            $table->string('delay_cause', 16)->nullable();
            $table->boolean('was_postponed')->default(false);
            $table->jsonb('postponement_reasons')->nullable();
            $table->boolean('phone_used')->default(false);
            $table->jsonb('phone_users')->nullable();
            $table->boolean('security_adequate')->nullable();
            $table->text('security_comment')->nullable();
            $table->string('recording', 16)->nullable();
            $table->boolean('interpretation_needed')->default(false);
            $table->boolean('interpretation_provided')->nullable();
            $table->jsonb('participants')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('client_uuid')->nullable()->unique();
            $workflow($table);
            $table->string('legacy_module', 64)->nullable();
            $table->string('legacy_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['legacy_module', 'legacy_id']);
        });

        Schema::create('hearing_party', function (Blueprint $table) {
            $table->foreignId('hearing_id')->constrained('hearings')->cascadeOnDelete();
            $table->foreignId('case_party_id')->constrained('case_parties')->cascadeOnDelete();
            $table->primary(['hearing_id', 'case_party_id']);
        });

        Schema::create('measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('case_party_id')->nullable()->constrained('case_parties')->nullOnDelete();
            $table->string('kind', 24)->index();
            $table->foreignId('security_measure_type_id')->nullable()->constrained('security_measure_types');
            $table->boolean('requested')->default(true);
            $table->date('requested_on')->nullable();
            $table->boolean('granted')->nullable();
            $table->date('decided_on')->nullable();
            $table->decimal('amount_eur', 14, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sentences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('case_party_id')->constrained('case_parties')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('instance', 16)->default('first_instance');
            $table->unsignedInteger('prison_months')->nullable();
            $table->unsignedInteger('suspended_months')->nullable();
            $table->decimal('fine_eur', 12, 2)->nullable();
            $table->text('supplementary_punishment')->nullable();
            $table->timestamps();

            $table->unique(['case_party_id', 'kind', 'instance']);
        });

        Schema::create('administrative_case_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->cascadeOnDelete();
            $table->text('first_instance_decision')->nullable();
            $table->date('first_instance_decision_date')->nullable();
            $table->boolean('decision_reasoned')->nullable();
            $table->date('decision_received_date')->nullable();
            $table->string('legal_remedy_type', 24)->nullable();
            $table->date('legal_remedy_date')->nullable();
            $table->string('second_instance_outcome', 24)->nullable();
            $table->date('second_instance_decision_date')->nullable();
            $table->boolean('conflict_filed')->default(false);
            $table->date('conflict_filed_date')->nullable();
            $table->string('plaintiff_type', 24)->nullable();
            $table->string('respondent_body')->nullable();
            $table->string('representation', 24)->nullable();
            $table->date('first_review_date')->nullable();
            $table->date('last_review_date')->nullable();
            $table->timestamps();
        });

        Schema::create('civil_case_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('cases')->cascadeOnDelete();
            $table->string('claim_type', 24)->nullable();
            $table->decimal('claim_value_eur', 14, 2)->nullable();
            $table->date('filed_date')->nullable();
            $table->string('plaintiff_type', 24)->nullable();
            $table->string('respondent_type', 24)->nullable();
            $table->string('representation', 24)->nullable();
            $table->boolean('legal_aid')->nullable();
            $table->boolean('mediation_attempted')->nullable();
            $table->string('outcome', 24)->nullable();
            $table->date('decision_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'civil_case_details', 'administrative_case_details', 'sentences', 'measures', 'hearing_party',
            'hearings', 'case_stages', 'charges', 'case_parties', 'cases',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
