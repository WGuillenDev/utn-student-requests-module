<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiver_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedTinyInteger('order')->comment('Evaluation order used by the engine');
            $table->enum('type', [
                'Approved requirement with minimum grade',
                'Accumulated credits or courses',
                'Terminal plan membership',
                'Always manual review',
            ]);
            $table->foreignId('required_course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            $table->decimal('minimum_grade', 5, 2)->nullable()->comment('Parameter N of type (a)');
            $table->unsignedSmallInteger('minimum_accumulated')->nullable()->comment('Parameter K of type (b)');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'order']);
        });

        Schema::create('validation_precedents', function (Blueprint $table) {
            $table->id();
            $table->string('institution', 150);
            $table->string('external_course', 150);
            $table->foreignId('course_id')->comment('Equivalent internal UTN course')->constrained('courses')->restrictOnDelete();
            $table->enum('result', ['Approved', 'Denied']);
            $table->string('resolution_number', 60)->comment('Reference resolution for the precedent');
            $table->timestamps();

            $table->index(['institution', 'external_course']);
        });

        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('type', ['Requirement Waiver', 'Validation']);
            $table->foreignId('course_id')->comment('Course to enroll / internal course being requested')->constrained('courses')->restrictOnDelete();
            $table->foreignId('required_course_id')->nullable()->comment('Unmet requirement')->constrained('courses')->nullOnDelete();
            $table->string('origin_institution', 150)->nullable()->comment('Validation only');
            $table->string('external_course', 150)->nullable()->comment('Validation only');
            $table->foreignId('validation_precedent_id')->nullable()->comment('Precedent found')
                ->constrained('validation_precedents')->nullOnDelete();
            $table->enum('engine_result', ['Automatically Approved', 'Not Approved', 'Requires Manual Review'])
                ->nullable()->comment('First conclusive result returned by the engine');
            $table->foreignId('violated_rule_id')->nullable()->comment('Rule that produced Not Approved')
                ->constrained('waiver_rules')->nullOnDelete();
            $table->enum('status', ['Pending Review', 'In Review', 'Approved', 'Denied'])
                ->default('Pending Review');
            $table->date('estimated_resolution_date')->nullable()->comment('Auto-assigned to 5 business days if not entered within 24h');
            $table->foreignId('reviewer_id')->nullable()->comment('Reviewing user (Docencia/Committee)')
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('request_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->string('comment', 255)->nullable()->comment('Justification for the change; required for denials (app layer)');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable()->comment('Time the email notification was sent to the student');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_status_history');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('validation_precedents');
        Schema::dropIfExists('waiver_rules');
    }
};
