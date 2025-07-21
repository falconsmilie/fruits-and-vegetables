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

        // Build VALUES (?, ?, ?), (?, ?, ?), ...
        $values = implode(',', array_fill(0, count($rows), $placeholders));

        // Generate ON DUPLICATE KEY UPDATE ... clause
        $updateParts = [];
        foreach ($columns as $col) {
            // Only update quantityInGrams in this example
            if ($col === 'quantity_in_grams') {
                $updateParts[] = "$col = VALUES($col)";
            }
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
            $table,
            implode(',', $columns),
            $values,
            implode(', ', $updateParts)
        );

        // Flatten parameters
        $params = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $params[] = $row[$col];
            }
        }

        $conn->executeStatement($sql, $params);
    }
}
