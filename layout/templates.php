<?php

function page_header($page_title, $page_id = false, $options = array()) {

	define('PAGE_RENDER_START', microtime(true));
	header('Content-type: text/html; charset=utf-8');

	$html_classes = array();
	if (has_required_admin()) {
		$html_classes[] = "body_admin";
	}
	$html_classes[] = get_site_config('site_id');

?>
<!DOCTYPE HTML>
<html<?php echo " class=\"" . implode(" ", $html_classes) . "\""; ?>>
<head>
    <title><?php echo htmlspecialchars($page_title); ?><?php if (has_required_admin()) echo " [admin]"; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(url_for(get_site_config('default_css') . '?' . get_site_config('openclerk_version'))); ?>" />
    <?php if (get_site_config('custom_css')) { ?>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(url_for(get_site_config('custom_css') . '?' . get_site_config('openclerk_version'))); ?>" />
    <?php } ?>
    <?php if (has_required_admin()) { ?>
    <link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(url_for('admin.css' . '?' . get_site_config('openclerk_version'))); ?>" />
    <?php } ?>
    <?php if (isset($options["refresh"])) { ?>
    <meta http-equiv="refresh" content="<?php echo htmlspecialchars($options['refresh']); ?>">
    <?php } ?>
    <script type="text/javascript" src="<?php echo htmlspecialchars(url_for('js/jquery-1.9.1.min.js')); ?>"></script>
    <script type="text/javascript" src="<?php echo htmlspecialchars(url_for('js/common.js' . '?' . get_site_config('openclerk_version'))); ?>"></script>
    <?php if (isset($options['jsapi']) && $options['jsapi']) { ?>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <?php } ?>
    <?php if (isset($options["js"]) && $options["js"]) {
    	if (!is_array($options['js'])) $options['js'] = array($options['js']);
    	foreach ($options['js'] as $js) {
    		$js_hash = "";
    		if (strpos($js, "?") !== false) {
    			$js_hash = "&" . substr($js, strpos($js, "?") + 1);
    			$js = substr($js, 0, strpos($js, "?"));
    		}
    		?>
    <script type="text/javascript" src="<?php echo htmlspecialchars(url_for('js/' . $js . '.js' . '?' . get_site_config('openclerk_version') . $js_hash)); ?>"></script>
    <?php }
    } ?>
	<?php require_template("templates_head"); ?>
</head>
<body<?php if ($page_id) echo ' id="' . $page_id . '"'; ?><?php if (isset($options['class'])) echo " class=\"" . htmlspecialchars($options['class']) . "\""; ?>>
<div class="page">

<?php require_template("templates_header"); ?>
<?php require_template("navigation"); ?>

<?php if (did_autologin()) { ?>
<div id="autologin">
	<?php echo t("Automatically logged in. Hi, :user!", array(':user' => "<a href=\"" . url_for('user') . "\" class=\"disabled\">"
		. ($_SESSION["user_name"] ? htmlspecialchars($_SESSION["user_name"]) : "<i>" . t("anonymous") . "</i>") . "</a>")); ?>
	(<a href="<?php echo url_for('login', array('logout' => 1)); ?>"><?php echo ht("This isn't me."); ?></a>)
</div>
<?php } ?>

	<article>
<?php

	// always display messages on every page as necessary
	display_messages();

}

function page_footer() {

?>
	</article>
</div>

<?php require_template("templates_footer"); ?>

<div id="footer_nav">
	<ul class="footer_nav_list">
		<li><span class="title"><?php echo htmlspecialchars(get_site_config('site_name')); ?></span>
			<ul>
				<li><a href="<?php echo htmlspecialchars(url_for('index')); ?>"><?php echo ht("About"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('premium')); ?>"><?php echo ht("Get Premium"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(get_site_config('version_history_link')); ?>"><?php echo ht("Release History"); ?></a></li>
				<li><a href="http://openclerk.org" target="_blank">Openclerk.org</a></li>
			</ul>
		</li>
		<li><span class="title"><?php echo ht("Your Account"); ?></span>
			<ul>
				<?php if (user_logged_in()) { ?>
				<li><a href="<?php echo htmlspecialchars(url_for('user')); ?>"><?php echo ht("User Profile"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('wizard_currencies')); ?>"><?php echo ht("Currency Preferences"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('wizard_accounts')); ?>"><?php echo ht("Configure Accounts"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('profile')); ?>"><?php echo ht("Your Reports"); ?></a></li>
				<?php } else { ?>
				<li><a href="<?php echo htmlspecialchars(url_for('signup')); ?>"><?php echo ht("Signup"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('login')); ?>"><?php echo ht("Login"); ?></a></li>
				<?php } ?>
			</ul>
		</li>
		<li><span class="title"><?php echo ht("Tools"); ?></span>
			<ul>
				<li><a href="<?php echo htmlspecialchars(url_for('historical')); ?>"><?php echo ht("Historical Data"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('average')); ?>"><?php echo ht("Market Averages"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('your_transactions')); ?>"><?php echo ht(":site_name Finance"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('calculator')); ?>"><?php echo ht("Calculator"); ?></a></li>
			</ul>
		</li>
		<li><span class="title"><?php echo ht("Support"); ?></span>
			<ul>
				<li><a href="<?php echo htmlspecialchars(url_for('help')); ?>"><?php echo ht("Help Centre"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(get_site_config('blog_link')); ?>" target="_blank"><?php echo ht("Blog"); ?></a> <span class="new"><?php echo ht("new"); ?></span></li>
				<li><a href="<?php echo htmlspecialchars(url_for('contact')); ?>"><?php echo ht("Contact Us"); ?></a></li>
				<li><a href="<?php echo htmlspecialchars(url_for('external')); ?>"><?php echo ht("External API Status"); ?></a></li>
			</ul>
		</li>
	</ul>

	<div id="copyright">
		<?php require_template("templates_copyright"); ?>
	</div>

</div>
<?php if (!(has_required_admin() || defined('BATCH_SCRIPT'))) { ?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo get_site_config('google_analytics_account'); ?>']);
  _gaq.push(['_setDomainName', '<?php echo get_site_config('google_analytics_domain'); ?>']);
  _gaq.push(['_setAllowLinker', true]);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<?php } ?>
</body>
</html>
<?php

	if (defined('PAGE_RENDER_START')) {
		$end_time = microtime(true);
		$time_diff = ($end_time - PAGE_RENDER_START) * 1000;
		echo "<!-- rendered in " . number_format($time_diff, 2) . " ms -->";

		if (get_site_config('timed_sql') && is_admin()) {
			global $global_timed_sql;
			echo "\n<!-- SQL debug: \n " . print_r($global_timed_sql, true) . "\n-->";
		}

		if (get_site_config('timed_curl') && is_admin()) {
			global $global_timed_curl;
			echo "\n<!-- CURL debug: \n " . print_r($global_timed_curl, true) . "\n-->";
		}
	}
	performance_metrics_page_end();

}

/**
 * Display any errors or messages, including those passed through temporary_messages/errors.
 */
function display_messages() {
	global $messages;
	global $errors;

	if (!isset($messages)) $messages = array();
	if (!isset($errors)) $errors = array();

	if (get_temporary_messages()) {
		$messages = array_join($messages, get_temporary_messages());
	}
	if (get_temporary_errors()) {
		$errors = array_join($errors, get_temporary_errors());
	}
	// if admin, load any admin messages
	if (is_admin()) {
		$q = db()->prepare("SELECT * FROM admin_messages WHERE is_read=0 ORDER BY created_at ASC");
		$q->execute();
		while ($message = $q->fetch()) {
			$messages[] = "Admin message: " . $message['message'] /* assumes encoded */ . " (<a href=\"" . htmlspecialchars(url_for('admin_message', array('id' => $message['id']))) . "\">hide</a>)";
		}
	}

	if ($messages) { ?>
<div class="message">
<ul>
	<?php foreach ($messages as $m) { echo "<li>" . $m . "</li>"; } /* do NOT accept user input for messages! */ ?>
</ul>
</div>
<?php }
	if ($errors) { ?>
<div class="error">
<ul>
	<?php foreach ($errors as $m) { echo "<li>" . $m . "</li>"; } /* do NOT accept user input for messages! */ ?>
</ul>
</div>
<?php }

}

function crypto_address($currency, $address) {
	foreach (get_blockchain_currencies() as $explorer => $currencies) {
		foreach ($currencies as $cur) {
			if ($cur == $currency) {
				return "<span class=\"address " . $currency . "_address\"><code>" . htmlspecialchars($address) . "</code>
					<a class=\"inspect\" href=\"" . htmlspecialchars(get_site_config($currency . "_address_url") . $address) . "\" title=\"Inspect with " . htmlspecialchars($explorer) . "\">?</a>
				</span>";
			}
		}
	}

	return htmlspecialchars($address);
}

function currency_format($currency_code, $n, $precision = 8 /* must be 8 for issue #1 */) {
	$currency = get_currency_abbr($currency_code);

	if (!is_numeric($n)) {
		return "<span class=\"error\">" . $n . " $currency</span>";
	}

	return "<span class=\"" . strtolower($currency) . "_format currency_format\" title=\"" . number_format_autoprecision($n, 8) . " $currency\">" . number_format_precision($n, $precision) . " <span class=\"code\">$currency</span></span>";
}

function rate_format($currency1, $currency2, $n, $precision = 8 /* must be 8 for issue #1 */) {
	$currency1 = get_currency_abbr($currency1);
	$currency2 = get_currency_abbr($currency2);

	if (!is_numeric($n)) {
		return "<span class=\"error\">" . $n . " $currency</span>";
	}

	return "<span class=\"rate_format currency_format\" title=\"" . number_format_autoprecision($n, 8) . " $currency1/$currency2\">" . number_format_precision($n, $precision) . " <span class=\"code\">$currency1/$currency2</span></span>";
}

/**
 * Note that due to autoprecision this just makes a mess, there is no precision.
 * Consider using {@link #number_format_precision_html()} instead.
 */
function number_format_html($n, $precision, $suffix = false) {
	return "<span title=\"" . number_format_autoprecision($n, 8) . ($suffix ? $suffix : "") . "\">" . number_format_precision($n, $precision) . ($suffix ? $suffix : "") ."</span>";
}

function number_format_precision_html($n, $precision = 0, $suffix = false) {
	return "<span title=\"" . number_format_autoprecision($n, 8) . ($suffix ? $suffix : "") . "\">" . number_format($n, $precision) . ($suffix ? $suffix : "") ."</span>";
}

function number_format_autoprecision_html($n, $suffix = false) {
	return "<span title=\"" . number_format_autoprecision($n, 8) . ($suffix ? $suffix : "") . "\">" . number_format_autoprecision($n) . ($suffix ? $suffix : "") . "</span>";
}

/**
 * The default colours used in Google charts. Obtained by taking screenshots.
 */
function default_chart_color($index) {
	switch ($index) {
		case 0: return "#3366cc";
		case 1: return "#dc3912";
		case 2: return "#ff9900";
		case 3: return "#109618";
		case 4: return "#990099";
		case 5: return "#3b3eac";
		case 6: return "#0099c6";
		case 7: return "#dd4477";
		case 8: return "#66aa00";
		case 9: return "#b82e2e";
		case 10: return "#316395";
		case 11: return "#994499";
		case 12: return "#22aa99";
		case 13: return "#aaaa11";
		case 14: return "#6633cc";
		case 15: return "#e67300";
		case 16: return "#8b0707";
		case 17: return "#329262";
		case 18: return "#5574a6";
		case 19: return "#3b3eac";
	}
	// unknown
	return "white";
}

function require_template($id) {
	// sanity checking for security
	$id = str_replace(".", "", $id);
	$id = str_replace("/", "", $id);
	$id = str_replace("\\", "", $id);

	if (file_exists(__DIR__ . "/../config/templates/" . $id . ".php")) {
		require(__DIR__ . "/../config/templates/" . $id . ".php");
	} else {
		require(__DIR__ . "/../templates/" . $id . ".php");
	}
}
