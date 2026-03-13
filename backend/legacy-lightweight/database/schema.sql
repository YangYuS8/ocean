CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL,
  `display_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `role_id` BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  KEY `idx_user_roles_role_id` (`role_id`),
  CONSTRAINT `fk_user_roles_user_id`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_user_roles_role_id`
    FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inspection_tasks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_code` VARCHAR(50) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `task_type` VARCHAR(50) NOT NULL,
  `priority` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(20) NOT NULL DEFAULT 'assigned',
  `location_text` VARCHAR(255) NULL,
  `planned_at` DATETIME NULL,
  `due_at` DATETIME NULL,
  `assigned_to` BIGINT UNSIGNED NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `started_at` DATETIME NULL,
  `submitted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inspection_tasks_task_code` (`task_code`),
  KEY `idx_inspection_tasks_status` (`status`),
  KEY `idx_inspection_tasks_assigned_to` (`assigned_to`),
  KEY `idx_inspection_tasks_planned_at` (`planned_at`),
  KEY `idx_inspection_tasks_due_at` (`due_at`),
  CONSTRAINT `fk_inspection_tasks_assigned_to`
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_inspection_tasks_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `samples` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sample_code` VARCHAR(50) NOT NULL,
  `inspection_task_id` BIGINT UNSIGNED NULL,
  `sample_type` VARCHAR(50) NOT NULL,
  `name` VARCHAR(200) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'registered',
  `collection_time` DATETIME NULL,
  `location_text` VARCHAR(255) NULL,
  `collector_id` BIGINT UNSIGNED NULL,
  `received_by` BIGINT UNSIGNED NULL,
  `received_at` DATETIME NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_samples_sample_code` (`sample_code`),
  KEY `idx_samples_inspection_task_id` (`inspection_task_id`),
  KEY `idx_samples_status` (`status`),
  KEY `idx_samples_collector_id` (`collector_id`),
  KEY `idx_samples_collection_time` (`collection_time`),
  CONSTRAINT `fk_samples_inspection_task_id`
    FOREIGN KEY (`inspection_task_id`) REFERENCES `inspection_tasks` (`id`),
  CONSTRAINT `fk_samples_collector_id`
    FOREIGN KEY (`collector_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_samples_received_by`
    FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sample_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sample_id` BIGINT UNSIGNED NOT NULL,
  `result_type` VARCHAR(50) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `raw_value` JSON NULL,
  `normalized_value` JSON NULL,
  `conclusion` VARCHAR(255) NULL,
  `entered_by` BIGINT UNSIGNED NULL,
  `entered_at` DATETIME NULL,
  `review_status` VARCHAR(20) NULL,
  `reviewed_by` BIGINT UNSIGNED NULL,
  `reviewed_at` DATETIME NULL,
  `review_comment` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sample_results_sample_id` (`sample_id`),
  KEY `idx_sample_results_result_type` (`result_type`),
  KEY `idx_sample_results_status` (`status`),
  KEY `idx_sample_results_review_status` (`review_status`),
  KEY `idx_sample_results_entered_by` (`entered_by`),
  CONSTRAINT `fk_sample_results_sample_id`
    FOREIGN KEY (`sample_id`) REFERENCES `samples` (`id`),
  CONSTRAINT `fk_sample_results_entered_by`
    FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_sample_results_reviewed_by`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exceptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resource_type` VARCHAR(50) NOT NULL,
  `resource_id` BIGINT UNSIGNED NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `severity` VARCHAR(20) NOT NULL DEFAULT 'medium',
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'open',
  `reported_by` BIGINT UNSIGNED NULL,
  `resolved_by` BIGINT UNSIGNED NULL,
  `resolved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exceptions_resource` (`resource_type`, `resource_id`),
  KEY `idx_exceptions_status` (`status`),
  KEY `idx_exceptions_severity` (`severity`),
  KEY `idx_exceptions_category` (`category`),
  KEY `idx_exceptions_reported_by` (`reported_by`),
  KEY `idx_exceptions_created_at` (`created_at`),
  CONSTRAINT `fk_exceptions_reported_by`
    FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_exceptions_resolved_by`
    FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `analysis_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sample_id` BIGINT UNSIGNED NOT NULL,
  `job_type` VARCHAR(50) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
  `params_json` JSON NULL,
  `result_summary` TEXT NULL,
  `error_message` TEXT NULL,
  `queued_by` BIGINT UNSIGNED NULL,
  `queued_at` DATETIME NULL,
  `started_at` DATETIME NULL,
  `finished_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_analysis_jobs_sample_id` (`sample_id`),
  KEY `idx_analysis_jobs_job_type` (`job_type`),
  KEY `idx_analysis_jobs_status` (`status`),
  KEY `idx_analysis_jobs_queued_by` (`queued_by`),
  KEY `idx_analysis_jobs_queued_at` (`queued_at`),
  CONSTRAINT `fk_analysis_jobs_sample_id`
    FOREIGN KEY (`sample_id`) REFERENCES `samples` (`id`),
  CONSTRAINT `fk_analysis_jobs_queued_by`
    FOREIGN KEY (`queued_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
