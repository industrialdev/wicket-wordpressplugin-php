<?php

use Wicket\Client;

/*
Plugin Name: Wicket Update Password
Description: wicket.io plugin responsible for providing a widget with a form to update a persons wicket password
Author: Industrial
*/

function process_wicket_password_form() {
	$client = wicket_api_client();
	$person = get_person();

	$errors = [];
	if (!empty($_POST)):
		// UPDATE PASSWORD
		// ----------------------------------------
		$current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
		$password = isset($_POST['password']) ? $_POST['password'] : '';
		$password_confirmation = isset($_POST['password_confirmation']) ? $_POST['password_confirmation'] : '';

		if ($current_password == '') {
			$current_pass_blank = new stdClass;
			$current_pass_blank->meta->field = 'user.current_password';
			$current_pass_blank->title = __("can't be blank");
			$errors[] = $current_pass_blank;
		}
		if ($password == '') {
			$pass_blank = new stdClass;
			$pass_blank->meta->field = 'user.password';
			$pass_blank->title = __("can't be blank");
			$errors[] = $pass_blank;
		}
		if ($password_confirmation == '') {
			$confirm_pass_blank = new stdClass;
			$confirm_pass_blank->meta->field = 'user.password_confirmation';
			$confirm_pass_blank->title = __("can't be blank");
			$errors[] = $confirm_pass_blank;
		}
		if ($password_confirmation != $password) {
			$pass_blank = new stdClass;
			$pass_blank->meta = (object)['field' => 'user.password'];
			$pass_blank->title = __(" - Passwords do not match");
			$errors[] = $pass_blank;
		}
		$_SESSION['wicket_password_form_errors'] = $errors;

		// don't send anything if errors
		if (empty($errors)) {
			$update_user = new Wicket\Entities\People(['user' => ['current_password' => $current_password,
																														'password' => $password,
																														'password_confirmation' => $password_confirmation
																													 ]
																								]);
			$update_user->id = $person->id;
			$update_user->type = $person->type;

			try {
				$client->people->update($update_user);
			} catch (Exception $e) {
				$_SESSION['wicket_password_form_errors'] = json_decode($e->getResponse()->getBody())->errors;
			}
			// redirect here if there was updates made to reload person info and prevent form re-submission
			if (empty($_SESSION['wicket_password_form_errors'])) {
				header('Location: '.strtok($_SERVER["REQUEST_URI"],'?').'?success');
				die;
			}
		}
	endif;
}
add_action('init', 'process_wicket_password_form');



// The widget class
// http://www.wpexplorer.com/create-widget-plugin-wordpress
class wicket_update_password extends WP_Widget {

	public $errors;

	// Main constructor
	public function __construct()
	{
		parent::__construct(
			'wicket_update_password',
			__('Wicket Update Password', 'wicket'),
			array(
				'customize_selective_refresh' => true,
			)
		);
	}

	// Display the widget
	public function widget($args, $instance)
	{
		$this->build_form();
	}

	private function build_form()
	{
		?>
		<?php if (isset($_SESSION['wicket_password_form_errors']) && !empty($_SESSION['wicket_password_form_errors'])):?>
		<div class='alert alert--error alert--has-icon'>
			<div class='alert__icon alert__panel-heading icon-right'>
				<span class='fa fa-exclamation-circle' aria-hidden='true'></span>
				<?php printf( _n( 'The form could not be submitted because 1 error was found', 'The form could not be submitted because %s errors were found', count($_SESSION['wicket_password_form_errors']), 'sassquatch' ), number_format_i18n(count($_SESSION['wicket_password_form_errors']))); ?>
			</div>
			<div class="alert__message">
				<?php
				$counter = 1;
				echo "<ul>";
				foreach ($_SESSION['wicket_password_form_errors'] as $key => $error) {
					if ($error->meta->field == 'user.current_password') {
						$prefix = __("Current Password").' ';
						printf(__("<li><a href='#current_password'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
					}
					if ($error->meta->field == 'user.password') {
						$prefix = __("New Password").' ';
						printf(__("<li><a href='#password'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
					}
					if ($error->meta->field == 'user.password_confirmation') {
						$prefix = __("Confirm Password").' ';
						printf(__("<li><a href='#password_confirmation'><strong>%s</strong> %s</a></li>", 'sassquatch'), 'Error: '.$counter, $prefix.$error->title);
					}
					$counter++;
				}
				echo "</ul>";
				?>
			</div>
		</div>
		<?php elseif(isset($_GET['success'])): ?>
			<div class='alert alert--success alert--has-icon'>
			<?php _e("Successfully Updated"); ?>
			</div>
		<?php endif; ?>

		<form class='manage_password_form' method="post">
			<label class="form__label" for="current_password"><?php _e('Current password') ?>
				<span class="required">*</span>
				<?php
				if (isset($_SESSION['wicket_password_form_errors']) && !empty($_SESSION['wicket_password_form_errors'])) {
					foreach ($_SESSION['wicket_password_form_errors'] as $key => $error) {
						if (isset($error->meta->field) && $error->meta->field == 'user.current_password') {
							echo "<span class='error'>".__('Current password')." {$error->title}</span>";
							$current_password_err = true;
						}
					}
				}
				?>
			</label>
			<input class="form__input" <?php if (isset($current_password_err) && $current_password_err): echo "class='error_input'"; endif; ?> required type="password" id="current_password" name="current_password" value="">

			<label class="form__label" for="password"><?php _e('New password') ?>
				<span class="required">*</span>
				<?php
				if (isset($_SESSION['wicket_password_form_errors']) && !empty($_SESSION['wicket_password_form_errors'])) {
					foreach ($_SESSION['wicket_password_form_errors'] as $key => $error) {
						if (isset($error->meta->field) && $error->meta->field == 'user.password') {
							echo "<span class='error'>".__('New password')." {$error->title}</span>";
							$password_err = true;
						}
					}
				}
				?>
			</label>

			<input class="form__input" <?php if (isset($password_err) && $password_err): echo "class='error_input'"; endif; ?> required type="password" name="password" id="password" value="">

			<label class="form__label" for="password_confirmation"><?php _e('Confirm new password') ?>
				<span class="required">*</span>
				<?php
				if (isset($_SESSION['wicket_password_form_errors']) && !empty($_SESSION['wicket_password_form_errors'])) {
					foreach ($_SESSION['wicket_password_form_errors'] as $key => $error) {
						if (isset($error->meta->field) && $error->meta->field == 'user.password_confirmation') {
							echo "<span class='error'>".__('Confirm password')." {$error->title}</span>";
							$password_confirm_err = true;
						}
					}
				}
				?>
			</label>
			<input class="form__input" <?php if (isset($password_confirm_err) && $password_confirm_err): echo "class='error_input'"; endif; ?> type="password" id="password_confirmation" name="password_confirmation" value="">

			<input class="button button--primary" type="submit" value="<?php _e('Change password') ?>">
		</form>
		<?php
	}

}



// Register the widget
function register_custom_widget_wicket_update_password() {
	register_widget('wicket_update_password');
}
add_action('widgets_init', 'register_custom_widget_wicket_update_password');