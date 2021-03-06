<?php

/**
 * Extra localisation strings.
 */

require(__DIR__ . "/../inc/global.php");
require_admin();

$messages = array();
$errors = array();

page_header("Extra Localisation Strings", "page_admin_extra_strings");

?>

<h1>Extra Localisation Strings</h1>

<p class="backlink"><a href="<?php echo htmlspecialchars(url_for('admin')); ?>">&lt; Back to Site Status</a></p>

<ul>
	<li>From account_data_grouped():
	<ul>
		<li><?php echo ht("Addresses"); ?></li>
		<li><?php echo ht("Mining pools"); ?></li>
		<li><?php echo ht("Exchanges"); ?></li>
		<li><?php echo ht("Securities"); ?></li>
		<li><?php echo ht("Individual Securities"); ?></li>
		<li><?php echo ht("Finance"); ?></li>
		<li><?php echo ht("Other"); ?></li>
		<li><?php echo ht("Hidden"); ?></li>
	</ul>
	</li>
	</li>
	<li>From get_external_apis():
	<ul>
		<li><?php echo ht("Address balances"); ?></li>
		<li><?php echo ht("Mining pool wallets"); ?></li>
		<li><?php echo ht("Exchange wallets"); ?></li>
		<li><?php echo ht("Exchange tickers"); ?></li>
		<li><?php echo ht("Security exchanges"); ?></li>
		<li><?php echo ht("Individual securities"); ?></li>
		<li><?php echo ht("Other"); ?></li>
	</ul>
	</li>
</ul>

<?php
page_footer();
