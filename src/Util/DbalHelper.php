<?php
namespace App\Util;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class DbalHelper
{
    /**
     * @throws Exception
     */
    public static function insertBatch(Connection $conn, string $table, array $rows): void
    {
        if (empty($rows)) return;

        $columns = array_keys($rows[0]);

        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';

        $values = implode(',', array_fill(0, count($rows), $placeholders));

        $updateParts = [];
        foreach ($columns as $col) {
            if ($col === 'quantity_in_grams') {
                $updateParts[] = "$col = VALUES($col)";
            }
        }

        $updateClause = !empty($updateParts)
            ? 'ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts)
            : '';

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s %s',
            $table,
            implode(',', $columns),
            $values,
            $updateClause
        );

        $params = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $params[] = $row[$col];
            }
        }

        $conn->executeStatement($sql, $params);
    }
}
