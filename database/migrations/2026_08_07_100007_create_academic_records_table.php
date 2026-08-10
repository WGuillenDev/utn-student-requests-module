<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained('academic_periods')->nullOnDelete();
            $table->enum('status', [
                'Approved',
                'Failed',
                'Credited by Equivalence',
                'Credited by Validation',
                'Requirement Waived',
            ]);
            $table->decimal('grade', 5, 2)->nullable();
            // equivalence_id: references `equivalences`, a table owned by the Curricular
            // Repository module (out of scope for Student Requests). Left without a real FK until that module exists.
            $table->unsignedBigInteger('equivalence_id')->nullable()->comment('Reference resolution for the credit');
            $table->timestamps();

            $table->index(['student_id', 'course_id']);
            $table->index(['course_id', 'status']);
            $table->index('equivalence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
