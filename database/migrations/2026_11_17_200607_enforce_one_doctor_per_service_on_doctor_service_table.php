<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicateServiceIds = DB::table('doctor_service')
            ->select('service_id')
            ->groupBy('service_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('service_id');

        foreach ($duplicateServiceIds as $serviceId) {
            $doctorIdToKeep = DB::table('doctor_service')
                ->where('service_id', $serviceId)
                ->min('doctor_id');

            DB::table('doctor_service')
                ->where('service_id', $serviceId)
                ->where('doctor_id', '<>', $doctorIdToKeep)
                ->delete();
        }

        Schema::table('doctor_service', function (Blueprint $table) {
            $table->unique('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_service', function (Blueprint $table) {
            $table->dropUnique(['service_id']);
        });
    }
};
