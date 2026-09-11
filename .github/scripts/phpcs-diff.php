<?php

/**
 * Report PHPCS messages that occur on lines added by a Git diff.
 *
 * Usage: php phpcs-diff.php <phpcs-report.json> <diff.patch>
 */

if (3 !== $argc) {
	fwrite(STDERR, "Usage: php phpcs-diff.php <phpcs-report.json> <diff.patch>\n");
	exit(2);
}

$report_contents = file_get_contents($argv[1]);
$diff_lines      = file($argv[2], FILE_IGNORE_NEW_LINES);

if (false === $report_contents || false === $diff_lines) {
	fwrite(STDERR, "Unable to read the PHPCS report or Git diff.\n");
	exit(2);
}

$report = json_decode($report_contents, true);

if (! is_array($report) || ! isset($report['files']) || ! is_array($report['files'])) {
	fwrite(STDERR, "PHPCS did not produce a valid JSON report.\n");
	exit(2);
}

$added_ranges = array();
$current_file = null;
$expect_new_file_header = false;
$in_hunk = false;

foreach ($diff_lines as $diff_line) {
	if (0 === strpos($diff_line, 'diff --git ')) {
		$current_file           = null;
		$expect_new_file_header = false;
		$in_hunk                = false;
		continue;
	}

	if (! $in_hunk && 0 === strpos($diff_line, '--- ')) {
		$expect_new_file_header = true;
		continue;
	}

	if ($expect_new_file_header && 0 === strpos($diff_line, '+++ ')) {
		$expect_new_file_header = false;
		$current_file = substr($diff_line, 4);

		if ('/dev/null' === $current_file) {
			$current_file = null;
			continue;
		}

		if (0 === strpos($current_file, 'b/')) {
			$current_file = substr($current_file, 2);
		}

		$current_file = str_replace('\\', '/', $current_file);
		continue;
	}

	if (null === $current_file) {
		continue;
	}

	if (preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $diff_line, $matches)) {
		$in_hunk = true;
		$start = (int) $matches[1];
		$count = isset($matches[2]) ? (int) $matches[2] : 1;

		if ($count > 0) {
			$added_ranges[$current_file][] = array($start, $start + $count - 1);
		}
	}
}

$error_count   = 0;
$warning_count = 0;

foreach ($report['files'] as $file => $file_report) {
	$file = str_replace('\\', '/', $file);

	if (0 === strpos($file, './')) {
		$file = substr($file, 2);
	}

	if (! isset($added_ranges[$file]) || empty($file_report['messages'])) {
		continue;
	}

	foreach ($file_report['messages'] as $message) {
		$line         = (int) $message['line'];
		$is_added     = false;
		$source       = isset($message['source']) ? $message['source'] : 'PHPCS';
		$column       = isset($message['column']) ? (int) $message['column'] : 1;
		$message_text = isset($message['message']) ? $message['message'] : 'Coding standards violation.';

		foreach ($added_ranges[$file] as $range) {
			if ($line >= $range[0] && $line <= $range[1]) {
				$is_added = true;
				break;
			}
		}

		if (! $is_added) {
			continue;
		}

		$is_error = isset($message['type']) && 'ERROR' === strtoupper($message['type']);
		$level    = $is_error ? 'error' : 'warning';

		if ($is_error) {
			++$error_count;
		} else {
			++$warning_count;
		}

		$property_file   = str_replace(array('%', "\r", "\n", ':', ','), array('%25', '%0D', '%0A', '%3A', '%2C'), $file);
		$property_source = str_replace(array('%', "\r", "\n", ':', ','), array('%25', '%0D', '%0A', '%3A', '%2C'), $source);
		$annotation_text = str_replace(array('%', "\r", "\n"), array('%25', '%0D', '%0A'), $message_text);

		echo sprintf(
			"::%s file=%s,line=%d,col=%d,title=PHPCS (%s)::%s\n",
			$level,
			$property_file,
			$line,
			$column,
			$property_source,
			$annotation_text
		);
	}
}

echo sprintf(
	"PHPCS added-line results: %d error(s), %d warning(s).\n",
	$error_count,
	$warning_count
);

exit($error_count > 0 ? 1 : 0);
