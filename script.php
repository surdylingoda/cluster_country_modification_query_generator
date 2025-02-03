#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Uid\Ulid;

/**
 * This script reads JSON data from 'package_data.json' in the same directory.
 * The JSON structure should be like:
 *
 * {
 *   "whitelist": {
 *     "01JJ1Y2VXY3RFJQN4W51564R4K": "JIM",
 *     "01JJ1XT1EB10N12XF41JG3X7X3": "KLN"
 *   },
 *   "blacklist": {
 *     "01JJ1XJ975Z561Z311PY6EDC4B": "JIMKLN"
 *   }
 * }
 */


// 1. Read the JSON data from package_data.json in the current script directory
$dataFile = __DIR__ . '/package_data.json';

if (!file_exists($dataFile)) {
    fwrite(STDERR, "Error: Cannot find 'package_data.json' next to this script.\n");
    exit(1);
}

$jsonString = file_get_contents($dataFile);
$data = json_decode($jsonString, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Invalid JSON in 'package_data.json'.\n");
    exit(1);
}

// 2. Prepare to write the output to file: package_cluster_query_{timestamp}.sql
$timestamp = date("Ymd_His");
$filename = "output/package_cluster_query_{$timestamp}.sql";
$fileHandle = fopen($filename, 'w');

if (!$fileHandle) {
    fwrite(STDERR, "Failed to open file '{$filename}' for writing.\n");
    exit(1);
}

// 3. Define the lists we want to process
$lists = ['whitelist', 'blacklist'];

foreach ($lists as $listType) {
    if (!isset($data[$listType]) || !is_array($data[$listType])) {
        // If the list key doesn't exist or isn't an array, skip it
        continue;
    }

    // 4. Iterate over each ULID => cluster mapping
    foreach ($data[$listType] as $ulid => $clusters) {
        // Convert ULID (base32) to a 32-char hex string using the ULID library
        try {
            $ulidHex = Ulid::fromString($ulid)->toHex();
            if(str_starts_with($ulidHex, '0x')) {
                $ulidHex = substr($ulidHex, 2);
            }
        } catch (\Throwable $e) {
            // If the ULID is invalid, skip or handle error
            $errorMsg = "Skipping invalid ULID '$ulid': " . $e->getMessage() . "\n";
            fwrite(STDERR, $errorMsg);
            continue;
        }

        // Split the clusters string into individual characters
        // e.g. "JIM" => ['J','I','M']
        $clustersArray = str_split($clusters);

        // Build the query text
        $queryLines = [];
        $queryLines[] = "-- {$listType} {$ulid} {$clusters}";
        $queryLines[] = "INSERT IGNORE INTO package_visibility_{$listType} (package_id, country_id)";
        $queryLines[] = "(SELECT DISTINCT UNHEX('{$ulidHex}'), c.id";
        $queryLines[] = "   FROM country c";
        $queryLines[] = "   JOIN geo_country gc ON gc.country_code = c.alpha2_code";
        $queryLines[] = "   JOIN geo_cluster_country gcc ON gcc.geo_country_id = gc.id";
        $queryLines[] = "   JOIN geo_cluster gc2 ON gc2.id = gcc.geo_cluster_id";
        $queryLines[] = "   WHERE gc2.cluster_name IN ('" . implode("','", $clustersArray) . "'));";
        $queryLines[] = "";

        // Combine into a single string
        $query = implode("\n", $queryLines);

        // 5. Print to stdout
        echo $query . "\n";

        // 6. Write to file
        fwrite($fileHandle, $query . "\n");
    }
}

// 7. Close the file handle
fclose($fileHandle);

echo "Queries saved to file: {$filename}\n";

exit(0);

