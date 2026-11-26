<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')
                ->default(UserRole::Member->value)
                ->after('company_id');
        });

        DB::table('users')
            ->whereNull('company_id')
            ->update(['role' => UserRole::GlobalAdmin->value]);

        DB::table('users')
            ->whereNotNull('company_id')
            ->update(['role' => UserRole::CompanyAdmin->value]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
