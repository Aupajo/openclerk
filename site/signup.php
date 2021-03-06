<?php

require(__DIR__ . "/../inc/global.php");
require(__DIR__ . "/../inc/countries.php");

// only permit POST for some variables
$autologin = require_post("autologin", require_get("autologin", true));
$use_password = require_post("use_password", require_get("use_password", false));
$email = trim(require_post("email", require_get("email", false)));

$password = $use_password ? require_post("password", require_get("password", false)) : false;
if ($password && !is_string($password)) {
	throw new Exception(t("Invalid password parameter"));
}
$password2 = $use_password ? require_post("password2", require_get("password2", false)) : false;
if ($password2 && !is_string($password2)) {
	throw new Exception(t("Invalid repeated password parameter"));
}
$name = require_post("name", require_get("name", false));
$agree = require_post("agree", require_get("agree", false));
$openid = $use_password ? false : require_post("openid", require_get("openid", require_post("openid_manual", require_get("openid_manual", false))));
if ($openid && !is_string($openid)) {
	throw new Exception(t("Invalid OpenID parameter"));
}
$subscribe = require_post("subscribe", require_get("subscribe", $openid ? false : true));
$country = require_post("country", require_get("country", false));

$messages = array();
$errors = array();

if ($openid || $password) {
	if (!$country || strlen($country) != 2) {
		$errors[] = t("You need to select your country.");
	}
	if ($email && !is_valid_email($email)) {
		$errors[] = t("That is not a valid e-mail address.");
	}
	if (!$agree) {
		$errors[] = t("You need to agree to the terms of service.");
	}
	if ($subscribe && !$email) {
		$errors[] = t("To subscribe to site announcements, you must provide an e-mail address.");
	}
	if ($password && !$email) {
		$errors[] = t("You need to select your e-mail address in order to use password login.");
	}
	if ($password && (strlen($password) < 6 || strlen($password) > 255)) {
		$errors[] = t("Please select a password between :min-:max characters long.", array(':min' => 6, ':max' => 255));
	}
	if ($password && $password != $password2) {
		$errors[] = t("Those passwords do not match.");
	}
	if ($openid && $password) {
		// but you can add OpenID identities later
		$errors[] = t("You cannot use both OpenID and password at signup.");
	}

	if (!$errors) {
		try {
			if ($password) {
				// check there is no existing user using this e-mail address
				// (a user can have multiple OpenID accounts for an e-mail address, but not multiple passwords,
				// since this would confuse 'forgotten password')
				// remember that e-mail addresses (without the domain) are case-sensitive
				$q = db()->prepare("SELECT * FROM users WHERE email=? AND ISNULL(password_hash)=0 LIMIT 1");
				$q->execute(array($email));

				if ($q->fetch()) {
					throw new EscapedException(t("That e-mail address is already in use by another account using password login. Did you mean to :login?",
							array(':login' => link_to(url_for('login', array('use_password' => true, 'email' => $email)), t("login instead")),
						)));
				}

			} else {
				if (!is_valid_url($openid)) {
					throw new EscapedException(t("That is not a valid OpenID identity."));
				}

				// to sign up with OpenID, we must first authenticate to see if the identity already exists
				require(__DIR__ . "/../vendor/lightopenid/lightopenid/openid.php");
				$light = new LightOpenID(get_openid_host());

				if (!$light->mode) {
					// we still need to authenticate

					$light->identity = $openid;
					// The following two lines request email, full name, and a nickname
					// from the provider. Remove them if you dont need that data.
					// $light->required = array('contact/email');
					// $light->optional = array('namePerson', 'namePerson/friendly');

					// we want to add the openid identity URL to the return address
					// (the return URL is also verified in validate())
					$args = array('openid' => $openid, 'submit' => 1, 'name' => $name, 'email' => $email, 'country' => $country, 'agree' => $agree, 'subscribe' => $subscribe);
					// persist session ID (to keep referer) if the user hasn't saved cookie
					if (session_name()) {
						$args[session_name()] = session_id();
					}
					$light->returnUrl = absolute_url(url_for('signup', $args));

					redirect($light->authUrl());

				} else if ($light->mode == 'cancel') {
					// user has cancelled
					throw new EscapedException(t("User has cancelled authentication."));

				} else {
					// throws a BlockedException if this IP has requested this too many times recently
					check_heavy_request();

					// authentication is complete
					if ($light->validate()) {
						// we authenticate everything against a particular identity, not what is provided by the user
						// e.g. OpenID authenticating against http://foo.livejournal.com/?param=two#hash will return
						// an identity of http://foo.livejournal.com/.
						// print_r($light->getAttributes());

						$q = db()->prepare("SELECT * FROM openid_identities WHERE url=? LIMIT 1");
						$q->execute(array($light->identity));
						if ($identity = $q->fetch()) {
							throw new EscapedException(t("An account for the OpenID identity ':identity' already exists. Did you mean to :login?",
								array(
									':identity' => htmlspecialchars($light->identity),
									':login' => link_to(url_for('login', array('openid' => $openid)), t("login instead")),
								)));
						}

					} else {
						throw new EscapedException(t("OpenID validation was not successful: :cause", array(':cause' => $light->validate_error ? htmlspecialchars($light->validate_error) : t("Please try again."))));
					}

				}
			}

			// we can now proceed with creating a new user account
			$query = db()->prepare("INSERT INTO users SET
				name=:name, email=:email, country=:country, user_ip=:ip, referer=:referer, subscribe_announcements=:subscribe, created_at=NOW(), updated_at=NOW()");
			$user = array(
				"name" => $name,
				"email" => $email,
				"country" => $country,
				"ip" => user_ip(),
				"referer" => isset($_SESSION['referer']) ? substr($_SESSION['referer'], 0, 250) : NULL,
				"subscribe" => $subscribe ? 1 : 0,
			);
			$query->execute($user);
			$user['id'] = db()->lastInsertId();

			if ($openid) {
				$q = db()->prepare("INSERT INTO openid_identities SET user_id=?, url=?");
				$q->execute(array($user['id'], $light->identity));
			} else {
				$q = db()->prepare("UPDATE users SET password_hash=?, password_last_changed=NOW() WHERE id=?");
				$password_hash = md5(get_site_config('password_salt') . $password);
				$q->execute(array($password_hash, $user['id']));
			}

			if ($subscribe) {
				$q = db()->prepare("INSERT INTO pending_subscriptions SET user_id=?,created_at=NOW(),is_subscribe=1");
				$q->execute(array($user['id']));
				$messages[] = t("You will be added manually to the :mailing_list soon.",
					array(
						':mailing_list' => "<a href=\"http://groups.google.com/group/" . htmlspecialchars(get_site_config('google_groups_announce')) . "\" target=\"_blank\">" . t("Announcements Mailing List") . "</a>",
					));
			}

			// try sending email
			if ($email) {
				send_user_email($user, "signup", array(
					"email" => $email,
					"name" => $name ? $name : $email,
					"announcements" => "http://groups.google.com/group/" . htmlspecialchars(get_site_config('google_groups_announce')),
					"url" => absolute_url(url_for("unsubscribe", array('email' => $email, 'hash' => md5(get_site_config('unsubscribe_salt') . $email)))),
				));
			}

			// create default summary pages and cryptocurrencies and graphs contents
			reset_user_settings($user['id']);

			// success!
			// issue #62: rather than requiring another step to login, just log the user in now.
			complete_login($user, $autologin);

			$messages[] = t("New account creation successful.");

			// redirect
			set_temporary_messages($messages);
			redirect(url_for(get_site_config('premium_welcome') ? "welcome" : get_site_config('signup_login'), array("pause" => true)));

		} catch (Exception $e) {
			if (!($e instanceof EscapedException)) {
				$e = new EscapedException(htmlspecialchars($e->getMessage()), (int) $e->getCode() /* PDO getCode doesn't return an int */, $e);
			}
			$errors[] = $e->getMessage();
		}
	}
}

require(__DIR__ . "/../layout/templates.php");
page_header(t("Signup"), "page_signup", array('js' => 'auth'));

?>

<?php require_template("signup"); ?>

<div class="authentication-form">
<h2><?php echo ht("Signup"); ?></h2>

<form action="<?php echo htmlspecialchars(absolute_url(url_for('signup'))); ?>" method="post">
<table class="login_form">
	<tr>
		<th><label for="name"><?php echo ht("Name:"); ?></label></th>
		<td><input type="text" id="name" name="name" size="32" value="<?php echo htmlspecialchars($name); ?>" maxlength="255"></td>
	</tr>
	<tr>
		<th><label for="country"><?php echo ht("Country:"); ?></label></th>
		<td><select name="country" class="country" id="country">
			<option></option>
			<?php
				foreach (get_country_iso() as $key => $value) {
					echo "<option value=\"" . htmlspecialchars($key) . "\"" . ($country == $key ? " selected" : "") . ">" . htmlspecialchars($value) . "</option>\n";
				}
			?>
		</select> <span class="required">*</span>
	</tr>
	<tr>
		<th><label for="email"><?php echo ht("E-mail:"); ?></label></th>
		<td><input type="text" id="email" name="email" size="48" value="<?php echo htmlspecialchars($email); ?>" maxlength="255"> <span class="required email-required"<?php echo !$use_password ? " style=\"display:none;\"" : ""; ?>>*</span></td>
	</tr>
	<tr>
		<th></th>
		<td><label><input type="checkbox" name="subscribe" value="1"<?php echo $subscribe ? " checked" : ""; ?>> <?php echo ht("Subscribe to site announcements"); ?></label></td>
	</tr>
	<tr>
		<th></th>
		<td><label><input type="checkbox" name="autologin" value="1"<?php echo $autologin ? " checked" : ""; ?>> <?php echo ht("Log in automatically"); ?></label></td>
	</tr>
	<tr>
		<th></th>
		<td><label><input type="checkbox" name="agree" value="1"<?php echo $agree ? " checked" : ""; ?>> <?php echo t("I agree to the :terms", array(':terms' => '<a href="' . htmlspecialchars(url_for('terms')) . '" target="_blank">' . ht("Terms of Service") . "</a>")); ?></label> <span class="required">*</span></td>
	</tr>
	<tr>
		<td colspan="2" class="hr"><hr></td>
	</tr>
	<tr class="signup-with login-with-openid"<?php echo $use_password ? " style=\"display:none;\"" : ""; ?>>
		<th><?php echo ht("Signup with:"); ?></th>
		<td>
			<input type="hidden" name="submit" value="1">

			<?php
			$openids = get_default_openid_providers();
			foreach ($openids as $key => $data) { ?>
				<button type="submit" name="openid" class="openid openid-submit" value="<?php echo htmlspecialchars($data[1]); ?>"><span class="openid <?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($data[0]); ?></span></button>
			<?php }
			?>

			<hr>
			<button id="openid" class="openid"><span class="openid openid_manual"><?php echo ht("OpenID..."); ?></span></button>

			<div id="openid_expand" style="<?php echo require_post("submit", "") == "Signup" ? "" : "display:none;"; ?>">
				<table>
				<tr>
					<th><?php echo ht("OpenID URL:"); ?></th>
					<td>
						<input type="text" name="openid_manual" class="openid" id="openid_manual" size="40" value="<?php echo htmlspecialchars($openid); ?>" maxlength="255">
						<input type="submit" name="submit" value="<?php echo ht("Signup"); ?>" id="openid_manual_submit">
					</td>
				</tr>
				</table>
			</div>

			<hr>
			<a class="password-openid-switch" href="<?php echo htmlspecialchars(url_for('signup', array('use_password' => true))); ?>"><?php echo ht("Use a password instead"); ?></a>

		</td>
	</tr>
	<tr class="login-with-password"<?php echo !$use_password ? " style=\"display:none;\"" : ""; ?>>
		<th><label for="password"><?php echo ht("Password:"); ?></label></th>
		<td>
			<input type="password" id="password" name="password" size="32" value="" maxlength="255"> <span class="required">*</span>
		</td>
	</tr>
	<tr class="login-with-password"<?php echo !$use_password ? " style=\"display:none;\"" : ""; ?>>
		<th><label for="password2"><?php echo ht("Repeat:"); ?></label></th>
		<td>
			<input type="password" id="password2" name="password2" size="32" value="" maxlength="255"> <span class="required">*</span>
		</td>
	</tr>
	<tr class="login-with-password"<?php echo !$use_password ? " style=\"display:none;\"" : ""; ?>>
		<th></th>
		<td>
			<input type="submit" name="submit" value="<?php echo ht("Signup"); ?>" id="password_manual_submit">

			<hr>
			<a class="password-openid-switch" href="<?php echo htmlspecialchars(url_for('signup', array('use_password' => false))); ?>"><?php echo ht("Use OpenID instead"); ?></a>

			<div class="tip">
				<?php echo t(":openid is often much more secure than using an e-mail and password. If you do use a password, please
				make sure you do not use the same password on other cryptocurrency sites.",
					array(
						':openid' => '<a class="password-openid-switch" href="' . htmlspecialchars(url_for('signup', array('use_password' => false))) . '">' . ht("OpenID login") . '</a>',
					));
				?>
			</div>

		</td>
	</tr>
</table>
<input type="hidden" name="use_password" id="use_password" value="<?php echo $use_password ? 1 : 0; ?>">
</form>
</div>

<?php
page_footer();
