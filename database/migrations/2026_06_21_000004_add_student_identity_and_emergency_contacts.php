<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'nationality')) {
                $table->string('nationality', 100)->nullable()->after('date_of_birth');
            }

            if (! Schema::hasColumn('students', 'identity_document_type')) {
                $table->string('identity_document_type', 40)->nullable()->after('nationality');
            }

            if (! Schema::hasColumn('students', 'identity_document_number')) {
                $table->string('identity_document_number', 100)->nullable()->after('identity_document_type');
            }

            if (! Schema::hasColumn('students', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('photo');
            }

            if (! Schema::hasColumn('students', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship', 100)->nullable()->after('emergency_contact_name');
            }

            if (! Schema::hasColumn('students', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_relationship');
            }

            if (! Schema::hasColumn('students', 'emergency_contact_alt_phone')) {
                $table->string('emergency_contact_alt_phone', 50)->nullable()->after('emergency_contact_phone');
            }

            if (! Schema::hasColumn('students', 'emergency_contact_address')) {
                $table->text('emergency_contact_address')->nullable()->after('emergency_contact_alt_phone');
            }

            if (! Schema::hasColumn('students', 'medical_notes')) {
                $table->text('medical_notes')->nullable()->after('emergency_contact_address');
            }

            if (! Schema::hasColumn('students', 'profile_updated_by_parent_at')) {
                $table->timestamp('profile_updated_by_parent_at')->nullable()->after('medical_notes');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index(['nationality', 'identity_document_type'], 'students_identity_lookup_index');
            $table->unique(['identity_document_type', 'identity_document_number'], 'students_identity_document_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_identity_document_unique');
            $table->dropIndex('students_identity_lookup_index');

            $table->dropColumn([
                'nationality',
                'identity_document_type',
                'identity_document_number',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_phone',
                'emergency_contact_alt_phone',
                'emergency_contact_address',
                'medical_notes',
                'profile_updated_by_parent_at',
            ]);
        });
    }
};
