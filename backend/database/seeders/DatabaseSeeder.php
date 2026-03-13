<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
            'planned_at' => now(),
            'due_at' => now()->addHours(8),
            'assigned_to' => $inspectorId,
            'created_by' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
