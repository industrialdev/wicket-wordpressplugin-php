<?php

use Wicket\Client;

/*
Plugin Name: Wicket Create Account
Description: wicket.io plugin responsible for providing a widget to sign up to wicket as a person
Author: Industrial
*/

require_once('classes/class_wicket_create_account_settings.php');

function process_wicket_create_account_form() {
	$errors = [];
	if (isset($_POST['wicket_create_account'])){
		if(!session_id()) session_start();

		$client = wicket_api_client();
		/**------------------------------------------------------------------
		* Create Account
		------------------------------------------------------------------*/
		$first_name = isset($_POST['given_name']) ? $_POST['given_name'] : '';
		$last_name = isset($_POST['family_name']) ? $_POST['family_name'] : '';
		$email = isset($_POST['address']) ? $_POST['address'] : '';
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		$password_confirmation = isset($_POST['password_confirmation']) ? $_POST['password_confirmation'] : '';

		if ($first_name == '') {
			$first_name_blank = new stdClass;
			$first_name_blank->meta = (object)['field' => 'user.given_name'];
			$first_name_blank->title = __("can't be blank");
			$errors[] = $first_name_blank;
		}
		if ($last_name == '') {
			$last_name_blank = new stdClass;
			$last_name_blank->meta = (object)['field' => 'user.family_name'];
			$last_name_blank->title = __("can't be blank");
			$errors[] = $last_name_blank;
		}
		if ($email == '') {
			$email_blank = new stdClass;
			$email_blank->meta = (object)['field' => 'emails.address'];
			$email_blank->title = __("can't be blank");
			$errors[] = $email_blank;
		}
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email_invalid = new stdClass;
			$email_invalid->meta = (object)['field' => 'emails.address'];
			$email_invalid->title = __("must be valid email");
			$errors[] = $email_invalid;
		}
		if ($password == '') {
			$pass_blank = new stdClass;
			$pass_blank->meta = (object)['field' => 'user.password'];
			$pass_blank->title = __("can't be blank");
			$errors[] = $pass_blank;
		}
		if ($password_confirmation == '') {
			$confirm_pass_blank = new stdClass;
			$confirm_pass_blank->meta = (object)['field' => 'user.password_confirmation'];
			$confirm_pass_blank->title = __("can't be blank");
			$errors[] = $confirm_pass_blank;
		}
		if ($password_confirmation != $password) {
			$pass_blank = new stdClass;
			$pass_blank->meta = (object)['field' => 'user.password'];
			$pass_blank->title = __(" - Passwords do not match");
			$errors[] = $pass_blank;
		}
		$passes_google_check = wicket_check_google_captcha();
		if (!$passes_google_check) {
			$errors[] = (object)[
					'title' => __(' - Please validate using the captcha below'),
					'meta' => (object)[
						'field' => 'google'
					]
				];
		}
		$_SESSION['wicket_create_account_form_errors'] = $errors;

		// don't send anything if errors
		if (empty($errors)) {
			// get parent org from admin settings to associate this person to
			$wicket_settings = get_wicket_settings();
			$parent_org = $wicket_settings['parent_org'];
			$args = [
				'query' => [
					'filter' => [
						'alternate_name_en_eq' => $parent_org
					],
					'page' => [
						'number' => 1,
						'size' => 1,
					]
				]
			];
			$org = $client->get('organizations', $args);

		  $user = [
				'password'              => $_POST['password'],
				'password_confirmation' => $_POST['password_confirmation'],
			];
		  $_POST['user'] = $user;

		  $person = new \Wicket\Entities\People($_POST);
		  $email = new \Wicket\Entities\Emails([
		    'address' => $_POST['address'],
		    'primary' => true,
		  ]);
		  $person->attach($email);

		  try {
		    $new_person = $client->people->create($person, (object)$org['data'][0]);
		  } catch (Exception $e) {
				$_SESSION['wicket_create_account_form_errors'] = json_decode($e->getResponse()->getBody())->errors;
		  }
			/**------------------------------------------------------------------
			* Redirect to a verify page if person was created
			------------------------------------------------------------------*/
			if (empty($_SESSION['wicket_create_account_form_errors'])) {
				unset($_SESSION['wicket_create_account_form_errors']);
				$creation_redirect_path = get_option('wicket_create_account_settings_person_creation_redirect');
				header('Location: '.$creation_redirect_path);
				die;
			}
		}
	} else if(isset($_SESSION['wicket_create_account_form_errors'])) {
		unset($_SESSION['wicket_create_account_form_errors']);
	}
}
add_action('init', 'process_wicket_create_account_form');



// The widget class
// http://www.wpexplorer.com/create-widget-plugin-wordpress
class wicket_create_account extends WP_Widget {

	public $errors;

	// Main constructor
	public function __construct()
	{
		parent::__construct(
			'wicket_create_account',
			__('Wicket Create Account', 'wicket'),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	public function form($instance) {
		return $instance;
	}

	public function update($new_instance, $old_instance) {
		return $old_instance;
	}

	// Display the widget
	public function widget($args, $instance)
	{
		$client = wicket_api_client();
		$result = '';
		if (!$client) {
			// if the API isn't up, just stop here
			return;
		}
		$this->build_form();
	}

	private function build_form()
	{
		?>
		<script src='https://www.google.com/recaptcha/api.js?hl=en'></script>
		<?php if (isset($_SESSION['wicket_create_account_form_errors']) && !empty($_SESSION['wicket_create_account_form_errors'])):?>
		<div class='alert alert-danger' role="alert">
			<p><?php printf( _n( 'The form could not be submitted because 1 error was found', 'The form could not be submitted because %s errors were found', count($_SESSION['wicket_create_account_form_errors']), 'sassquatch' ), number_format_i18n(count($_SESSION['wicket_create_account_form_errors']))); ?></p>
			<?php
			$counter = 1;
			echo "<ul>";
			foreach ($_SESSION['wicket_create_account_form_errors'] as $key => $error) {
				if ($error->meta->field == 'user.given_name') {
					$prefix = __("First Name").' ';
					printf(__("<li><a href='#given_name'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
				}
				if ($error->meta->field == 'user.family_name') {
					$prefix = __("Last Name").' ';
					printf(__("<li><a href='#family_name'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
				}
				if ($error->meta->field == 'emails.address') {
					$prefix = __("Email").' - ';
					printf(__("<li><a href='#address'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
				}
				if ($error->meta->field == 'user.password') {
					$prefix = __("Password").' ';
					printf(__("<li><a href='#password'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
				}
				if ($error->meta->field == 'user.password_confirmation') {
					$prefix = __("Confirm Password").' ';
					printf(__("<li><a href='#password_confirmation'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
				}
				if ($error->meta->field == 'google') {
					$prefix = __("Captcha").' ';
					printf(__("<li><a href='#google'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
				}
				$counter++;
			}
			echo "</ul>";
			?>
		</div>
		<?php elseif(isset($_GET['success'])): ?>
			<div class='alert alert--success'>
				<p><?php _e("Successfully Created"); ?></p>
			</div>
		<?php endif; ?>

		<form class='manage_password_form' method="post">
			<div class="form__group">
				<label class="form__label" for="given_name"><?php _e('First Name') ?>
					<span class="required" aria-label="<?php _e('Required','wicket') ?>">*</span>
					<?php
					if (isset($_SESSION['wicket_create_account_form_errors']) && !empty($_SESSION['wicket_create_account_form_errors'])) {
						foreach ($_SESSION['wicket_create_account_form_errors'] as $key => $error) {
							if (isset($error->meta->field) && $error->meta->field == 'user.given_name') {
								echo "<span class='error'>".__('First Name')." {$error->title}</span>";
								$given_name_err = true;
							}
						}
					}
					?>
				</label>
				<input class="form__input" <?php if (isset($given_name_err) && $given_name_err): echo "class='error_input'"; endif; ?> required type="text" id="given_name" name="given_name" value="<?php echo isset($_POST['given_name']) ? $_POST['given_name'] : '' ?>">
			</div>



			<div class="form__group">
				<label class="form__label" for="family_name"><?php _e('Last Name') ?>
					<span class="required" aria-label="<?php _e('Required','wicket') ?>">*</span>
					<?php
					if (isset($_SESSION['wicket_create_account_form_errors']) && !empty($_SESSION['wicket_create_account_form_errors'])) {
						foreach ($_SESSION['wicket_create_account_form_errors'] as $key => $error) {
							if (isset($error->meta->field) && $error->meta->field == 'user.family_name') {
								echo "<span class='error'>".__('Last Name')." {$error->title}</span>";
								$last_name_err = true;
							}
						}
					}
					?>
				</label>
				<input class="form__input" <?php if (isset($last_name_err) && $last_name_err): echo "class='error_input'"; endif; ?> required type="text" id="family_name" name="family_name" value="<?php echo isset($_POST['family_name']) ? $_POST['family_name'] : '' ?>">
			</div>



			<div class="form__group">
				<label class="form__label" for="address"><?php _e('Email') ?>
					<span class="required" aria-label="<?php _e('Required','wicket') ?>">*</span>
					<?php
					if (isset($_SESSION['wicket_create_account_form_errors']) && !empty($_SESSION['wicket_create_account_form_errors'])) {
						foreach ($_SESSION['wicket_create_account_form_errors'] as $key => $error) {
							if (isset($error->meta->field) && $error->meta->field == 'emails.address') {
								echo "<span class='error'>".__('Email')." - {$error->title}</span>";
								$address_err = true;
							}
						}
					}
					?>
				</label>
				<input class="form__input" <?php if (isset($address_err) && $address_err): echo "class='error_input'"; endif; ?> required type="text" id="address" name="address" value="<?php echo isset($_POST['address']) ? $_POST['address'] : '' ?>">
			</div>



			<div class="form__group">
				<label class="form__label" for="password"><?php _e('Password') ?>
					<span class="required" aria-label="<?php _e('Required','wicket') ?>">*</span>
					<?php
					if (isset($_SESSION['wicket_create_account_form_errors']) && !empty($_SESSION['wicket_create_account_form_errors'])) {
						foreach ($_SESSION['wicket_create_account_form_errors'] as $key => $error) {
							if (isset($error->meta->field) && $error->meta->field == 'user.password') {
								echo "<span class='error'>".__('Password')." {$error->title}</span>";
								$password_err = true;
							}
						}
					}
					?>
				</label>
				<input class="form__input" <?php if (isset($password_err) && $password_err): echo "class='error_input'"; endif; ?> required type="password" name="password" id="password" value="">
			</div>



			<div class="form__group">
				<label class="form__label" for="password_confirmation"><?php _e('Confirm password') ?>
					<span class="required" aria-label="<?php _e('Required','wicket') ?>">*</span>
					<?php
					if (isset($_SESSION['wicket_create_account_form_errors']) && !empty($_SESSION['wicket_create_account_form_errors'])) {
						foreach ($_SESSION['wicket_create_account_form_errors'] as $key => $error) {
							if (isset($error->meta->field) && $error->meta->field == 'user.password_confirmation') {
								echo "<span class='error'>".__('Confirm password')." {$error->title}</span>";
								$password_confirm_err = true;
							}
						}
					}
					?>
				</label>
				<input class="form__input" <?php if (isset($password_confirm_err) && $password_confirm_err): echo "class='error_input'"; endif; ?> type="password" id="password_confirmation" name="password_confirmation" value="">
			</div>

			<a name="google"></a>
			<?php
			$recaptcha_key = get_option('wicket_create_account_settings_google_captcha_key');
			if ($recaptcha_key):
			?>
			<div class="g-recaptcha" data-sitekey="<?php echo $recaptcha_key ?>"></div>
			<?php endif; ?>
			<input type="hidden" name="wicket_create_account" value="<?php echo $this->id_base . '-' . $this->number; ?>" />
			<br>
			<input class="button button--primary" type="submit" value="<?php _e('Submit') ?>">
		</form>
		<?php
	}

}



function wicket_check_google_captcha(){
  if (!isset($_POST['g-recaptcha-response'])) {
    return false;
  }
  $ch = curl_init();
	$secret = get_option('wicket_create_account_settings_google_captcha_secret_key');
  $response = $_POST['g-recaptcha-response'];
  $remoteip = $_SERVER['REMOTE_ADDR'];

  curl_setopt($ch, CURLOPT_URL,"https://www.google.com/recaptcha/api/siteverify");
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS,
    "secret=$secret&response=$response&remoteip=$remoteip"
  );

  // receive server response ...
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $google_response = curl_exec($ch);
  curl_close ($ch);
  $google_response = json_decode($google_response)->success;
  return $google_response;
}



// Register the widget
function register_custom_widget_wicket_create_account() {
	register_widget('wicket_create_account');
}
add_action('widgets_init', 'register_custom_widget_wicket_create_account');
