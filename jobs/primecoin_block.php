<?php

/**
 * Get current Primecoin block number.
 * Since this isn't based off Abe, we scrape HTML instead (ergh).
 */

$currency = "xpm";
$block_table = "primecoin_blocks";
require(__DIR__ . "/_cryptocoinexplorer_block.php");
