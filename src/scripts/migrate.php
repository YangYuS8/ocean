<?php

declare(strict_types=1);

use App\Infrastructure\Database;

require_once dirname(__DIR__) . '/bootstrap.php';

Database::execSqlFile(dirname(__DIR__) . '/database/schema.sql');

fwrite(STDOUT, "Schema migration completed.\n");
