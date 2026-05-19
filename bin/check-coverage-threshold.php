#!/usr/bin/env php
<?php

declare(strict_types=1);

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php bin/check-coverage-threshold.php <clover.xml> <minimum-percent>\n");

    exit(1);
}

[$script, $reportPath, $thresholdArgument] = $argv;

if (!is_file($reportPath)) {
    fwrite(STDERR, sprintf("Coverage report not found: %s\n", $reportPath));

    exit(1);
}

if (!is_numeric($thresholdArgument)) {
    fwrite(STDERR, sprintf("Coverage threshold must be numeric, got: %s\n", $thresholdArgument));

    exit(1);
}

$document = new DOMDocument();

if (!$document->load($reportPath)) {
    fwrite(STDERR, sprintf("Coverage report could not be parsed: %s\n", $reportPath));

    exit(1);
}

$xpath = new DOMXPath($document);
$metrics = $xpath->query('/coverage/project/metrics')->item(0);

if (!$metrics instanceof DOMElement) {
    fwrite(STDERR, "Project metrics were not found in Clover report.\n");

    exit(1);
}

$statements = (int) $metrics->getAttribute('statements');
$coveredStatements = (int) $metrics->getAttribute('coveredstatements');
$threshold = (float) $thresholdArgument;
$lineCoverage = $statements === 0 ? 100.0 : ($coveredStatements / $statements) * 100;

fwrite(
    STDOUT,
    sprintf(
        "Line coverage threshold check: %.2f%% covered (minimum %.2f%%)\n",
        $lineCoverage,
        $threshold,
    ),
);

if ($lineCoverage + 0.00001 < $threshold) {
    fwrite(STDERR, "Coverage threshold not met.\n");

    exit(1);
}
