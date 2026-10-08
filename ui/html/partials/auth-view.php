<?php
/**
 * Notes Adda Partial: Authentication View
 * Sign In and Account Creation forms for guests.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="na-auth-container">
	<div class="na-auth-card">
		<div class="na-brand-header">
			<img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-auth-brand-logo" width="240" height="80" style="max-width: 240px; height: auto; max-height: 80px; object-fit: contain;">
			<p class="na-brand-tagline">Cyber Knowledge &amp; Collaborative Notes Network</p>
		</div>

		<div class="na-auth-nav">
			<button type="button" class="na-auth-tab active" data-switch="login">Sign In</button>
			<button type="button" class="na-auth-tab" data-switch="register">Create Account</button>
		</div>

		<div id="na-login-view" class="na-auth-view active">
			<form id="na-login-form" class="na-form">
				<div class="na-form-group">
					<label for="na-login-username">Username or Email</label>
					<div class="na-input-wrapper">
						<span class="na-input-icon dashicons dashicons-admin-users"></span>
						<input type="text" id="na-login-username" name="username" class="na-input" placeholder="Enter username or email" required autocomplete="username">
					</div>
				</div>
				<div class="na-form-group">
					<label for="na-login-password">Password</label>
					<div class="na-input-wrapper">
						<span class="na-input-icon dashicons dashicons-lock"></span>
						<input type="password" id="na-login-password" name="password" class="na-input" placeholder="Enter password" required autocomplete="current-password">
					</div>
				</div>
				<button type="submit" class="na-btn na-btn-primary na-btn-block">
					<span class="na-btn-text">Sign In</span>
					<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
				</button>
				<div class="na-auth-message"></div>
			</form>
		</div>
		
		<div id="na-register-view" class="na-auth-view" style="display:none;">
			<form id="na-register-form" class="na-form">
				<div class="na-form-row">
					<div class="na-form-group na-col">
						<label for="na-reg-username">Username</label>
						<input type="text" id="na-reg-username" name="username" class="na-input" placeholder="Unique username" required autocomplete="username">
					</div>
					<div class="na-form-group na-col">
						<label for="na-reg-email">Email</label>
						<input type="email" id="na-reg-email" name="email" class="na-input" placeholder="student@college.edu" required autocomplete="email">
					</div>
				</div>
				<div class="na-form-group">
					<label for="na-reg-bio">Bio (Optional)</label>
					<textarea id="na-reg-bio" name="bio" class="na-input na-textarea" rows="2" placeholder="Major, year, interests..."></textarea>
				</div>
				<div class="na-form-row">
					<div class="na-form-group na-col">
						<label for="na-reg-password">Password</label>
						<input type="password" id="na-reg-password" name="password" class="na-input" placeholder="Min. 6 chars" required autocomplete="new-password">
					</div>
					<div class="na-form-group na-col">
						<label for="na-reg-confirm">Confirm Password</label>
						<input type="password" id="na-reg-confirm" name="confirm_password" class="na-input" placeholder="Re-enter password" required autocomplete="new-password">
					</div>
				</div>
				<button type="submit" class="na-btn na-btn-primary na-btn-block">
					<span class="na-btn-text">Create Account</span>
					<span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
				</button>
				<div class="na-auth-message"></div>
			</form>
		</div>
	</div>
</div>
