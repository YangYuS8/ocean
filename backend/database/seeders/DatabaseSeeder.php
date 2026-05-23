<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('roles')->updateOrInsert(['code' => 'inspector'], ['name' => '巡检员', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('roles')->updateOrInsert(['code' => 'analyst'], ['name' => '分析员', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('roles')->updateOrInsert(['code' => 'admin'], ['name' => '管理员', 'created_at' => now(), 'updated_at' => now()]);

        DB::table('users')->updateOrInsert(['username' => 'admin'], [
            'display_name' => '系统管理员',
            'email' => 'admin@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->updateOrInsert(['username' => 'inspector01'], [
            'display_name' => '巡检员01',
            'email' => 'inspector01@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->updateOrInsert(['username' => 'analyst01'], [
            'display_name' => '分析员01',
            'email' => 'analyst01@example.com',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $adminId = DB::table('users')->where('username', 'admin')->value('id');
        $inspectorId = DB::table('users')->where('username', 'inspector01')->value('id');
        $analystId = DB::table('users')->where('username', 'analyst01')->value('id');

        $roleMap = [
            $adminId => DB::table('roles')->where('code', 'admin')->value('id'),
            $inspectorId => DB::table('roles')->where('code', 'inspector')->value('id'),
            $analystId => DB::table('roles')->where('code', 'analyst')->value('id'),
        ];

        foreach ($roleMap as $userId => $roleId) {
            DB::table('user_roles')->updateOrInsert(['user_id' => $userId, 'role_id' => $roleId], []);
        }

        DB::table('inspection_tasks')->updateOrInsert(['task_code' => 'IT-20260311-001'], [
            'title' => '东海浮标例行巡检',
            'description' => '检查设备外观并采集基础样本。',
            'task_type' => 'routine_inspection',
            'priority' => 'normal',
            'status' => 'assigned',
            'location_text' => '东海 A 区 3 号点位',
            'planned_at' => $now,
            'due_at' => $now->copy()->addHours(8),
            'assigned_to' => $inspectorId,
            'created_by' => $adminId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $inspectionTaskId = DB::table('inspection_tasks')->where('task_code', 'IT-20260311-001')->value('id');

        DB::table('samples')->updateOrInsert(['sample_code' => 'SP-20260311-001'], [
            'inspection_task_id' => $inspectionTaskId,
            'sample_type' => 'water',
            'name' => '东海 A 区表层水样',
            'status' => 'testing',
            'collection_time' => $now,
            'location_text' => '东海 A 区 3 号点位',
            'collector_id' => $inspectorId,
            'notes' => 'v1.0.0 baseline seeded core chain sample.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sampleId = DB::table('samples')->where('sample_code', 'SP-20260311-001')->value('id');

        DB::table('sample_results')->updateOrInsert([
            'sample_id' => $sampleId,
            'result_type' => 'salinity',
        ], [
            'status' => 'draft',
            'raw_value' => json_encode(['value' => 31.2, 'unit' => 'ppt'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'normalized_value' => json_encode(['value' => 31.2, 'unit' => 'ppt'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'conclusion' => 'within_expected_range',
            'entered_by' => $analystId,
            'entered_at' => $now,
            'notes' => 'Seeded salinity result for baseline verification.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sampleResultId = DB::table('sample_results')
            ->where('sample_id', $sampleId)
            ->where('result_type', 'salinity')
            ->value('id');

        DB::table('exceptions')->updateOrInsert([
            'resource_type' => 'sample_result',
            'resource_id' => $sampleResultId,
            'category' => 'threshold_alert',
        ], [
            'severity' => 'medium',
            'title' => '盐度结果需要复核',
            'description' => '作为 v1.0.0 基线种子数据，用于验证异常链路。',
            'status' => 'open',
            'reported_by' => $analystId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('analysis_jobs')->updateOrInsert([
            'sample_id' => $sampleId,
            'job_type' => 'quality_assessment',
        ], [
            'status' => 'queued',
            'params_json' => json_encode(['source' => 'database_seeder', 'sample_result_id' => $sampleResultId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'result_summary' => null,
            'error_message' => null,
            'queued_by' => $analystId,
            'queued_at' => $now,
            'started_at' => null,
            'finished_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
