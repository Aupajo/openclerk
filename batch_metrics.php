<?php

/**
 * A batch script to calculate performance metrics from data that has been collected.
 * This always executes (no job framework) so it should be used sparingly or as necessary.
 *
 * Arguments (in command line, use "-" for no argument):
 *   $key/1 required the automated key
 */

require(__DIR__ . "/inc/global.php");
require(__DIR__ . "/_batch.php");

require_batch_key();
batch_header("Batch metrics", "batch_metrics");

crypto_log("Current time: " . date('r'));

{
	// "What database queries take the longest?"
	$report_type = "db_slow_queries";

	// select the worst ten queries
	$q = db()->prepare("SELECT query_id, SUM(query_count) AS qc, SUM(query_time) AS qt, MIN(page_id) AS pid FROM performance_metrics_slow_queries
			GROUP BY query_id ORDER BY SUM(query_count) / SUM(query_time) LIMIT 10");
	$q->execute();
	$data = $q->fetchAll();

	$q = db()->prepare("INSERT INTO performance_reports SET report_type=?");
	$q->execute(array($report_type));
	$report_id = db()->lastInsertId();

	foreach ($data as $row) {
		$q = db()->prepare("INSERT INTO performance_report_slow_queries SET report_id=?, query_id=?, query_count=?, query_time=?, page_id=?");
		$q->execute(array($report_id, $row['query_id'], $row['qc'], $row['qt'], $row['pid']));
	}

	crypto_log("Created report '$report_type'");
}

// we've processed all the data we want; delete old metrics data
if (false) {
	$q = db()->prepare("DELETE FROM performance_metrics_slow_queries");
	$q->execute();

	crypto_log("Deleted old metric data.");
}

	// "What tables take the longest to query?"
	// "What URLs take the longest to request?"
	// "How long does it take for a page to be generated?"
	// "What pages are taking the longest to load?"
	// "What pages have the most database queries?"
	// "What pages spend the most time in PHP as opposed to the database?"

	// "What jobs take the longest?"
	// "How many jobs are running per hour?"
	// "How many ticker jobs are running per hour?"
	// "What jobs have the most database queries?"
	// "What jobs spend the most time in PHP as opposed to the database?"
	// "Which jobs time out the most?"
	// "How many blockchain requests fail?"
	// "What jobs take the longest requesting URLs?"

	// "How many jobs are being queued at once?"
	// "Which queue types take the longest?"

	// "What graph types take the longest to render?"
	// "What are the most common graph types?"
	// "How many ticker graphs are being requested?"


batch_footer();
