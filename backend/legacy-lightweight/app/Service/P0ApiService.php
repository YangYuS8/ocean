<?php

declare(strict_types=1);

namespace App\Service;

use App\Support\ApiException;
use App\Support\Validator;
use PDO;

final class P0ApiService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function dashboardSummary(): array
    {
        return [
            'pending_samples' => $this->countByQuery("SELECT COUNT(*) FROM samples WHERE status IN ('registered', 'received', 'testing')"),
            'today_inspection_tasks' => $this->countByQuery('SELECT COUNT(*) FROM inspection_tasks WHERE DATE(planned_at) = CURDATE()'),
            'open_exceptions' => $this->countByQuery("SELECT COUNT(*) FROM exceptions WHERE status = 'open'"),
            'queued_analysis_jobs' => $this->countByQuery("SELECT COUNT(*) FROM analysis_jobs WHERE status IN ('queued', 'running')"),
        ];
    }

    public function listInspectionTasks(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $conditions = [];
        $params = [];

        if (($query['status'] ?? '') !== '') {
            $conditions[] = 't.status = :status';
            $params['status'] = $query['status'];
        }
        if (($query['assigned_to'] ?? '') !== '') {
            $conditions[] = 't.assigned_to = :assigned_to';
            $params['assigned_to'] = (int) $query['assigned_to'];
        }
        if (($query['task_type'] ?? '') !== '') {
            $conditions[] = 't.task_type = :task_type';
            $params['task_type'] = $query['task_type'];
        }
        if (($query['planned_date_from'] ?? '') !== '') {
            $conditions[] = 't.planned_at >= :planned_date_from';
            $params['planned_date_from'] = $query['planned_date_from'] . ' 00:00:00';
        }
        if (($query['planned_date_to'] ?? '') !== '') {
            $conditions[] = 't.planned_at <= :planned_date_to';
            $params['planned_date_to'] = $query['planned_date_to'] . ' 23:59:59';
        }
        if (($query['keyword'] ?? '') !== '') {
            $conditions[] = '(t.task_code LIKE :keyword OR t.title LIKE :keyword OR t.location_text LIKE :keyword)';
            $params['keyword'] = '%' . $query['keyword'] . '%';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $total = $this->countWithWhere('SELECT COUNT(*) FROM inspection_tasks t ' . $where, $params);

        $sql = "SELECT t.id, t.task_code, t.title, t.task_type, t.priority, t.status, t.location_text,
                       t.planned_at, t.due_at, u.id AS assigned_to_id, u.display_name AS assigned_to_name
                FROM inspection_tasks t
                LEFT JOIN users u ON u.id = t.assigned_to
                {$where}
                ORDER BY t.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'task_code' => $row['task_code'],
                'title' => $row['title'],
                'task_type' => $row['task_type'],
                'priority' => $row['priority'],
                'status' => $row['status'],
                'location_text' => $row['location_text'],
                'planned_at' => $row['planned_at'],
                'due_at' => $row['due_at'],
                'assigned_to' => $row['assigned_to_id'] === null ? null : [
                    'id' => (int) $row['assigned_to_id'],
                    'display_name' => $row['assigned_to_name'],
                ],
            ];
        }, $stmt->fetchAll());

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function getInspectionTask(int $id): array
    {
        $sql = "SELECT t.*, au.display_name AS assigned_to_name, cu.display_name AS created_by_name
                FROM inspection_tasks t
                LEFT JOIN users au ON au.id = t.assigned_to
                LEFT JOIN users cu ON cu.id = t.created_by
                WHERE t.id = :id";
        $task = $this->fetchOne($sql, ['id' => $id], 'NOT_FOUND', 'inspection task not found');

        return [
            'id' => (int) $task['id'],
            'task_code' => $task['task_code'],
            'title' => $task['title'],
            'description' => $task['description'],
            'task_type' => $task['task_type'],
            'priority' => $task['priority'],
            'status' => $task['status'],
            'location_text' => $task['location_text'],
            'planned_at' => $task['planned_at'],
            'due_at' => $task['due_at'],
            'assigned_to' => $task['assigned_to'] === null ? null : [
                'id' => (int) $task['assigned_to'],
                'display_name' => $task['assigned_to_name'],
            ],
            'created_by' => $task['created_by'] === null ? null : [
                'id' => (int) $task['created_by'],
                'display_name' => $task['created_by_name'],
            ],
            'started_at' => $task['started_at'],
            'submitted_at' => $task['submitted_at'],
            'created_at' => $task['created_at'],
            'updated_at' => $task['updated_at'],
        ];
    }

    public function startInspectionTask(int $id, array $payload): array
    {
        Validator::requireFields($payload, ['operator_id']);
        $task = $this->fetchOne('SELECT id, status FROM inspection_tasks WHERE id = :id', ['id' => $id], 'NOT_FOUND', 'inspection task not found');
        if ($task['status'] !== 'assigned') {
            throw new ApiException('INVALID_STATE', 'inspection task cannot be started from current state', 409);
        }
        $this->assertUserExists((int) $payload['operator_id']);

        $stmt = $this->pdo->prepare('UPDATE inspection_tasks SET status = :status, started_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => 'in_progress', 'id' => $id]);

        return [
            'id' => $id,
            'status' => 'in_progress',
            'started_at' => $this->scalar('SELECT started_at FROM inspection_tasks WHERE id = :id', ['id' => $id]),
        ];
    }

    public function submitInspectionTask(int $id, array $payload): array
    {
        Validator::requireFields($payload, ['operator_id']);
        $task = $this->fetchOne('SELECT id, status FROM inspection_tasks WHERE id = :id', ['id' => $id], 'NOT_FOUND', 'inspection task not found');
        if ($task['status'] !== 'in_progress') {
            throw new ApiException('INVALID_STATE', 'inspection task cannot be submitted from current state', 409);
        }
        $this->assertUserExists((int) $payload['operator_id']);

        $stmt = $this->pdo->prepare('UPDATE inspection_tasks SET status = :status, submitted_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => 'submitted', 'id' => $id]);

        return [
            'id' => $id,
            'status' => 'submitted',
            'submitted_at' => $this->scalar('SELECT submitted_at FROM inspection_tasks WHERE id = :id', ['id' => $id]),
        ];
    }

    public function listSamples(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $conditions = [];
        $params = [];

        foreach (['sample_code', 'sample_type', 'status'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $conditions[] = "s.{$field} = :{$field}";
                $params[$field] = $query[$field];
            }
        }
        if (($query['inspection_task_id'] ?? '') !== '') {
            $conditions[] = 's.inspection_task_id = :inspection_task_id';
            $params['inspection_task_id'] = (int) $query['inspection_task_id'];
        }
        if (($query['collector_id'] ?? '') !== '') {
            $conditions[] = 's.collector_id = :collector_id';
            $params['collector_id'] = (int) $query['collector_id'];
        }
        if (($query['collection_date_from'] ?? '') !== '') {
            $conditions[] = 's.collection_time >= :collection_date_from';
            $params['collection_date_from'] = $query['collection_date_from'] . ' 00:00:00';
        }
        if (($query['collection_date_to'] ?? '') !== '') {
            $conditions[] = 's.collection_time <= :collection_date_to';
            $params['collection_date_to'] = $query['collection_date_to'] . ' 23:59:59';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $total = $this->countWithWhere('SELECT COUNT(*) FROM samples s ' . $where, $params);

        $sql = "SELECT s.*, u.display_name AS collector_name
                FROM samples s
                LEFT JOIN users u ON u.id = s.collector_id
                {$where}
                ORDER BY s.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'sample_code' => $row['sample_code'],
                'inspection_task_id' => $row['inspection_task_id'] === null ? null : (int) $row['inspection_task_id'],
                'sample_type' => $row['sample_type'],
                'name' => $row['name'],
                'status' => $row['status'],
                'collection_time' => $row['collection_time'],
                'location_text' => $row['location_text'],
                'collector_id' => $row['collector_id'] === null ? null : (int) $row['collector_id'],
                'collector_name' => $row['collector_name'],
            ];
        }, $stmt->fetchAll());

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function createSample(array $payload): array
    {
        Validator::requireFields($payload, ['sample_code', 'sample_type']);

        if ($this->scalar('SELECT COUNT(*) FROM samples WHERE sample_code = :sample_code', ['sample_code' => $payload['sample_code']]) > 0) {
            throw new ApiException('VALIDATION_ERROR', 'sample_code must be unique', 422);
        }

        $inspectionTaskId = $payload['inspection_task_id'] ?? null;
        if ($inspectionTaskId !== null) {
            $this->assertExists('inspection_tasks', (int) $inspectionTaskId, 'inspection task not found');
        }

        foreach (['collector_id'] as $field) {
            if (($payload[$field] ?? null) !== null) {
                $this->assertUserExists((int) $payload[$field]);
            }
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO samples (sample_code, inspection_task_id, sample_type, name, status, collection_time, location_text, collector_id, notes)
             VALUES (:sample_code, :inspection_task_id, :sample_type, :name, :status, :collection_time, :location_text, :collector_id, :notes)'
        );
        $stmt->execute([
            'sample_code' => $payload['sample_code'],
            'inspection_task_id' => $inspectionTaskId,
            'sample_type' => $payload['sample_type'],
            'name' => $payload['name'] ?? null,
            'status' => 'registered',
            'collection_time' => $payload['collection_time'] ?? null,
            'location_text' => $payload['location_text'] ?? null,
            'collector_id' => $payload['collector_id'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        $id = (int) $this->scalar('SELECT LAST_INSERT_ID()');
        return [
            'id' => $id,
            'sample_code' => $payload['sample_code'],
            'status' => 'registered',
            'created_at' => $this->scalar('SELECT created_at FROM samples WHERE id = :id', ['id' => $id]),
        ];
    }

    public function getSample(int $id): array
    {
        $sql = "SELECT s.*, cu.display_name AS collector_name, ru.display_name AS received_by_name
                FROM samples s
                LEFT JOIN users cu ON cu.id = s.collector_id
                LEFT JOIN users ru ON ru.id = s.received_by
                WHERE s.id = :id";
        $sample = $this->fetchOne($sql, ['id' => $id], 'NOT_FOUND', 'sample not found');

        return [
            'id' => (int) $sample['id'],
            'sample_code' => $sample['sample_code'],
            'inspection_task_id' => $sample['inspection_task_id'] === null ? null : (int) $sample['inspection_task_id'],
            'sample_type' => $sample['sample_type'],
            'name' => $sample['name'],
            'status' => $sample['status'],
            'collection_time' => $sample['collection_time'],
            'location_text' => $sample['location_text'],
            'collector' => $sample['collector_id'] === null ? null : [
                'id' => (int) $sample['collector_id'],
                'display_name' => $sample['collector_name'],
            ],
            'received_by' => $sample['received_by'] === null ? null : [
                'id' => (int) $sample['received_by'],
                'display_name' => $sample['received_by_name'],
            ],
            'received_at' => $sample['received_at'],
            'notes' => $sample['notes'],
            'created_at' => $sample['created_at'],
            'updated_at' => $sample['updated_at'],
        ];
    }

    public function listSampleResults(int $sampleId, array $query): array
    {
        $this->assertExists('samples', $sampleId, 'sample not found');
        $conditions = ['sr.sample_id = :sample_id'];
        $params = ['sample_id' => $sampleId];
        foreach (['result_type', 'status'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $conditions[] = "sr.{$field} = :{$field}";
                $params[$field] = $query[$field];
            }
        }
        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT sr.*, u.display_name AS entered_by_name
                FROM sample_results sr
                LEFT JOIN users u ON u.id = sr.entered_by
                {$where}
                ORDER BY sr.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(fn(array $row): array => $this->transformSampleResult($row), $stmt->fetchAll());
    }

    public function createSampleResult(int $sampleId, array $payload): array
    {
        Validator::requireFields($payload, ['result_type']);
        $sample = $this->fetchOne('SELECT id, status FROM samples WHERE id = :id', ['id' => $sampleId], 'NOT_FOUND', 'sample not found');
        if (in_array($sample['status'], ['invalid', 'archived'], true)) {
            throw new ApiException('INVALID_STATE', 'sample cannot accept results in current state', 409);
        }
        if (($payload['entered_by'] ?? null) !== null) {
            $this->assertUserExists((int) $payload['entered_by']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO sample_results (sample_id, result_type, status, raw_value, normalized_value, conclusion, entered_by, entered_at, notes)
             VALUES (:sample_id, :result_type, :status, :raw_value, :normalized_value, :conclusion, :entered_by, NOW(), :notes)'
        );
        $stmt->execute([
            'sample_id' => $sampleId,
            'result_type' => $payload['result_type'],
            'status' => 'draft',
            'raw_value' => array_key_exists('raw_value', $payload) ? json_encode($payload['raw_value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'normalized_value' => array_key_exists('normalized_value', $payload) ? json_encode($payload['normalized_value'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'conclusion' => $payload['conclusion'] ?? null,
            'entered_by' => $payload['entered_by'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ]);

        if (in_array($sample['status'], ['registered', 'received'], true)) {
            $update = $this->pdo->prepare('UPDATE samples SET status = :status WHERE id = :id');
            $update->execute(['status' => 'testing', 'id' => $sampleId]);
        }

        $id = (int) $this->scalar('SELECT LAST_INSERT_ID()');
        return [
            'id' => $id,
            'sample_id' => $sampleId,
            'status' => 'draft',
            'created_at' => $this->scalar('SELECT created_at FROM sample_results WHERE id = :id', ['id' => $id]),
        ];
    }

    public function listExceptions(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $conditions = [];
        $params = [];
        foreach (['resource_type', 'category', 'severity', 'status'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $conditions[] = "e.{$field} = :{$field}";
                $params[$field] = $query[$field];
            }
        }
        foreach (['resource_id', 'reported_by'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $conditions[] = "e.{$field} = :{$field}";
                $params[$field] = (int) $query[$field];
            }
        }
        if (($query['created_from'] ?? '') !== '') {
            $conditions[] = 'e.created_at >= :created_from';
            $params['created_from'] = $query['created_from'] . ' 00:00:00';
        }
        if (($query['created_to'] ?? '') !== '') {
            $conditions[] = 'e.created_at <= :created_to';
            $params['created_to'] = $query['created_to'] . ' 23:59:59';
        }
        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $total = $this->countWithWhere('SELECT COUNT(*) FROM exceptions e ' . $where, $params);

        $sql = "SELECT e.*, u.display_name AS reported_by_name
                FROM exceptions e
                LEFT JOIN users u ON u.id = e.reported_by
                {$where}
                ORDER BY e.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'resource_type' => $row['resource_type'],
                'resource_id' => (int) $row['resource_id'],
                'category' => $row['category'],
                'severity' => $row['severity'],
                'title' => $row['title'],
                'status' => $row['status'],
                'reported_by' => $row['reported_by'] === null ? null : [
                    'id' => (int) $row['reported_by'],
                    'display_name' => $row['reported_by_name'],
                ],
                'created_at' => $row['created_at'],
            ];
        }, $stmt->fetchAll());

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function createException(array $payload): array
    {
        Validator::requireFields($payload, ['resource_type', 'resource_id', 'category', 'title']);
        $this->assertResourceExists((string) $payload['resource_type'], (int) $payload['resource_id']);
        if (($payload['reported_by'] ?? null) !== null) {
            $this->assertUserExists((int) $payload['reported_by']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO exceptions (resource_type, resource_id, category, severity, title, description, status, reported_by)
             VALUES (:resource_type, :resource_id, :category, :severity, :title, :description, :status, :reported_by)'
        );
        $stmt->execute([
            'resource_type' => $payload['resource_type'],
            'resource_id' => (int) $payload['resource_id'],
            'category' => $payload['category'],
            'severity' => $payload['severity'] ?? 'medium',
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'status' => 'open',
            'reported_by' => $payload['reported_by'] ?? null,
        ]);

        $id = (int) $this->scalar('SELECT LAST_INSERT_ID()');
        return [
            'id' => $id,
            'status' => 'open',
            'created_at' => $this->scalar('SELECT created_at FROM exceptions WHERE id = :id', ['id' => $id]),
        ];
    }

    public function resolveException(int $id, array $payload): array
    {
        Validator::requireFields($payload, ['resolved_by']);
        $exception = $this->fetchOne('SELECT id, status FROM exceptions WHERE id = :id', ['id' => $id], 'NOT_FOUND', 'exception not found');
        if ($exception['status'] !== 'open') {
            throw new ApiException('INVALID_STATE', 'exception cannot be resolved from current state', 409);
        }
        $this->assertUserExists((int) $payload['resolved_by']);

        $stmt = $this->pdo->prepare('UPDATE exceptions SET status = :status, resolved_by = :resolved_by, resolved_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => 'resolved',
            'resolved_by' => (int) $payload['resolved_by'],
            'id' => $id,
        ]);

        return [
            'id' => $id,
            'status' => 'resolved',
            'resolved_at' => $this->scalar('SELECT resolved_at FROM exceptions WHERE id = :id', ['id' => $id]),
        ];
    }

    public function listAnalysisJobs(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);
        $conditions = [];
        $params = [];
        foreach (['job_type', 'status'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $conditions[] = "aj.{$field} = :{$field}";
                $params[$field] = $query[$field];
            }
        }
        foreach (['sample_id', 'queued_by'] as $field) {
            if (($query[$field] ?? '') !== '') {
                $conditions[] = "aj.{$field} = :{$field}";
                $params[$field] = (int) $query[$field];
            }
        }
        if (($query['queued_from'] ?? '') !== '') {
            $conditions[] = 'aj.queued_at >= :queued_from';
            $params['queued_from'] = $query['queued_from'] . ' 00:00:00';
        }
        if (($query['queued_to'] ?? '') !== '') {
            $conditions[] = 'aj.queued_at <= :queued_to';
            $params['queued_to'] = $query['queued_to'] . ' 23:59:59';
        }
        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $total = $this->countWithWhere('SELECT COUNT(*) FROM analysis_jobs aj ' . $where, $params);

        $sql = "SELECT aj.*, u.display_name AS queued_by_name
                FROM analysis_jobs aj
                LEFT JOIN users u ON u.id = aj.queued_by
                {$where}
                ORDER BY aj.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $data = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'sample_id' => (int) $row['sample_id'],
                'job_type' => $row['job_type'],
                'status' => $row['status'],
                'queued_by' => $row['queued_by'] === null ? null : [
                    'id' => (int) $row['queued_by'],
                    'display_name' => $row['queued_by_name'],
                ],
                'queued_at' => $row['queued_at'],
            ];
        }, $stmt->fetchAll());

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function createAnalysisJob(array $payload): array
    {
        Validator::requireFields($payload, ['sample_id', 'job_type']);
        $sampleId = (int) $payload['sample_id'];
        $sample = $this->fetchOne('SELECT id, status FROM samples WHERE id = :id', ['id' => $sampleId], 'NOT_FOUND', 'sample not found');
        if (in_array($sample['status'], ['invalid', 'archived'], true)) {
            throw new ApiException('INVALID_STATE', 'sample cannot accept analysis jobs in current state', 409);
        }
        if (($payload['queued_by'] ?? null) !== null) {
            $this->assertUserExists((int) $payload['queued_by']);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO analysis_jobs (sample_id, job_type, status, params_json, result_summary, error_message, queued_by, queued_at)
             VALUES (:sample_id, :job_type, :status, :params_json, NULL, NULL, :queued_by, NOW())'
        );
        $stmt->execute([
            'sample_id' => $sampleId,
            'job_type' => $payload['job_type'],
            'status' => 'queued',
            'params_json' => array_key_exists('params', $payload) ? json_encode($payload['params'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'queued_by' => $payload['queued_by'] ?? null,
        ]);

        $id = (int) $this->scalar('SELECT LAST_INSERT_ID()');
        return [
            'id' => $id,
            'sample_id' => $sampleId,
            'job_type' => $payload['job_type'],
            'status' => 'queued',
            'queued_at' => $this->scalar('SELECT queued_at FROM analysis_jobs WHERE id = :id', ['id' => $id]),
        ];
    }

    public function getAnalysisJob(int $id): array
    {
        $sql = "SELECT aj.*, u.display_name AS queued_by_name
                FROM analysis_jobs aj
                LEFT JOIN users u ON u.id = aj.queued_by
                WHERE aj.id = :id";
        $job = $this->fetchOne($sql, ['id' => $id], 'NOT_FOUND', 'analysis job not found');

        return [
            'id' => (int) $job['id'],
            'sample_id' => (int) $job['sample_id'],
            'job_type' => $job['job_type'],
            'status' => $job['status'],
            'params' => $this->decodeJsonField($job['params_json']),
            'result_summary' => $job['result_summary'],
            'error_message' => $job['error_message'],
            'queued_by' => $job['queued_by'] === null ? null : [
                'id' => (int) $job['queued_by'],
                'display_name' => $job['queued_by_name'],
            ],
            'queued_at' => $job['queued_at'],
            'started_at' => $job['started_at'],
            'finished_at' => $job['finished_at'],
        ];
    }

    private function transformSampleResult(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'sample_id' => (int) $row['sample_id'],
            'result_type' => $row['result_type'],
            'status' => $row['status'],
            'raw_value' => $this->decodeJsonField($row['raw_value']),
            'normalized_value' => $this->decodeJsonField($row['normalized_value']),
            'conclusion' => $row['conclusion'],
            'entered_by' => $row['entered_by'] === null ? null : [
                'id' => (int) $row['entered_by'],
                'display_name' => $row['entered_by_name'],
            ],
            'entered_at' => $row['entered_at'],
        ];
    }

    private function decodeJsonField(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function assertResourceExists(string $resourceType, int $resourceId): void
    {
        $tableMap = [
            'inspection_task' => 'inspection_tasks',
            'sample' => 'samples',
            'sample_result' => 'sample_results',
        ];

        if (!isset($tableMap[$resourceType])) {
            throw new ApiException('VALIDATION_ERROR', 'unsupported resource_type', 422);
        }

        $this->assertExists($tableMap[$resourceType], $resourceId, sprintf('%s not found', $resourceType));
    }

    private function assertExists(string $table, int $id, string $message): void
    {
        $count = $this->scalar(sprintf('SELECT COUNT(*) FROM %s WHERE id = :id', $table), ['id' => $id]);
        if ((int) $count === 0) {
            throw new ApiException('NOT_FOUND', $message, 404);
        }
    }

    private function assertUserExists(int $userId): void
    {
        $this->assertExists('users', $userId, 'user not found');
    }

    private function fetchOne(string $sql, array $params, string $errorCode, string $message): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new ApiException($errorCode, $message, $errorCode === 'NOT_FOUND' ? 404 : 400);
        }

        return $row;
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    private function countByQuery(string $sql): int
    {
        return (int) $this->pdo->query($sql)->fetchColumn();
    }

    private function countWithWhere(string $sql, array $params): int
    {
        return (int) $this->scalar($sql, $params);
    }

    private function pagination(array $query): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($query['page_size'] ?? 20)));
        $offset = ($page - 1) * $pageSize;

        return [$page, $pageSize, $offset];
    }
}
