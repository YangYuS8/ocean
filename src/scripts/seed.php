<?php

declare(strict_types=1);

use App\Infrastructure\Database;

require_once dirname(__DIR__) . '/bootstrap.php';

Database::execSqlFile(dirname(__DIR__) . '/database/seed.sql');

fwrite(STDOUT, "Seed data applied.\n");
