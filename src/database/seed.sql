INSERT INTO `roles` (`code`, `name`)
VALUES
  ('inspector', '巡检员'),
  ('analyst', '分析员'),
  ('admin', '管理员')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `users` (`username`, `display_name`, `email`, `status`)
VALUES
  ('admin', '系统管理员', 'admin@example.com', 'active'),
  ('inspector01', '巡检员01', 'inspector01@example.com', 'active'),
  ('analyst01', '分析员01', 'analyst01@example.com', 'active')
ON DUPLICATE KEY UPDATE
  `display_name` = VALUES(`display_name`),
  `email` = VALUES(`email`),
  `status` = VALUES(`status`);

INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `users` u
JOIN `roles` r ON (
  (u.username = 'admin' AND r.code = 'admin') OR
  (u.username = 'inspector01' AND r.code = 'inspector') OR
  (u.username = 'analyst01' AND r.code = 'analyst')
);

INSERT INTO `inspection_tasks` (
  `task_code`,
  `title`,
  `description`,
  `task_type`,
  `priority`,
  `status`,
  `location_text`,
  `planned_at`,
  `due_at`,
  `assigned_to`,
  `created_by`
)
SELECT
  'IT-20260311-001',
  '东海浮标例行巡检',
  '检查设备外观并采集基础样本。',
  'routine_inspection',
  'normal',
  'assigned',
  '东海 A 区 3 号点位',
  NOW(),
  DATE_ADD(NOW(), INTERVAL 8 HOUR),
  inspector.id,
  admin.id
FROM `users` admin
JOIN `users` inspector ON inspector.username = 'inspector01'
WHERE admin.username = 'admin'
ON DUPLICATE KEY UPDATE
  `title` = VALUES(`title`),
  `description` = VALUES(`description`),
  `task_type` = VALUES(`task_type`),
  `priority` = VALUES(`priority`),
  `status` = VALUES(`status`),
  `location_text` = VALUES(`location_text`),
  `planned_at` = VALUES(`planned_at`),
  `due_at` = VALUES(`due_at`),
  `assigned_to` = VALUES(`assigned_to`),
  `created_by` = VALUES(`created_by`);
