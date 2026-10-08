/**
 * Notes Adda - Authentication Module
 * Handles sign in, user registration tabs, credential validation, and sign out requests.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const Auth = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const app = window.NotesAddaApp;

			// Auth View Tab Switching
			$(document).on('click', '[data-switch]', function(e) {
				e.preventDefault();
				const target = $(this).data('switch');
				$('.na-auth-tab').removeClass('active');
				$(`.na-auth-tab[data-switch="${target}"]`).addClass('active');

				$('.na-auth-view').removeClass('active').hide();
				$('#na-' + target + '-view').addClass('active').show();
				$('.na-auth-message').text('').removeClass('error success');
			});

			// Login Form Submission
			$('#na-login-form').on('submit', function(e) {
				e.preventDefault();
				const $form = $(this);
				const $btn = $form.find('button[type="submit"]');
				const $msg = $form.find('.na-auth-message');

				$btn.prop('disabled', true);
				$btn.find('.na-btn-text').text('Signing in...');
				$btn.find('.na-btn-spinner').show();
				$msg.text('').removeClass('error success');

				const postData = $form.serialize() + '&action=notes_adda_login&_ajax_nonce=' + NotesAdda.nonce;

				$.ajax({
					url: NotesAdda.ajax_url,
					type: 'POST',
					data: postData,
					success: function(res) {
						if (res.success) {
							$msg.text('Signed in successfully! Loading your dashboard...').addClass('success');
							window.location.reload();
						} else {
							$btn.prop('disabled', false);
							$btn.find('.na-btn-text').text('Sign In');
							$btn.find('.na-btn-spinner').hide();
							$msg.text(res.data && res.data.message ? res.data.message : 'Invalid credentials.').addClass('error');
						}
					},
					error: function(xhr) {
						$btn.prop('disabled', false);
						$btn.find('.na-btn-text').text('Sign In');
						$btn.find('.na-btn-spinner').hide();
						const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'An error occurred during sign in.';
						$msg.text(err).addClass('error');
					}
				});
			});

			// Register Form Submission
			$('#na-register-form').on('submit', function(e) {
				e.preventDefault();
				const $form = $(this);
				const $btn = $form.find('button[type="submit"]');
				const $msg = $form.find('.na-auth-message');

				$btn.prop('disabled', true);
				$btn.find('.na-btn-text').text('Creating Account...');
				$btn.find('.na-btn-spinner').show();
				$msg.text('').removeClass('error success');

				const postData = $form.serialize() + '&action=notes_adda_register&_ajax_nonce=' + NotesAdda.nonce;

				$.ajax({
					url: NotesAdda.ajax_url,
					type: 'POST',
					data: postData,
					success: function(res) {
						if (res.success) {
							$msg.text('Account created! Welcome to Notes Adda. Loading...').addClass('success');
							window.location.reload();
						} else {
							$btn.prop('disabled', false);
							$btn.find('.na-btn-text').text('Create Account');
							$btn.find('.na-btn-spinner').hide();
							$msg.text(res.data && res.data.message ? res.data.message : 'Registration failed.').addClass('error');
						}
					},
					error: function(xhr) {
						$btn.prop('disabled', false);
						$btn.find('.na-btn-text').text('Create Account');
						$btn.find('.na-btn-spinner').hide();
						const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'An error occurred during registration.';
						$msg.text(err).addClass('error');
					}
				});
			});

			// Sign Out Action
			$(document).on('click', '#na-logout-btn', function(e) {
				e.preventDefault();
				if (!confirm('Are you sure you want to sign out of Notes Adda?')) {
					return;
				}

				$.post(NotesAdda.ajax_url, {
					action: 'notes_adda_logout',
					_ajax_nonce: NotesAdda.nonce
				}, function(res) {
					if (res.success && res.data && res.data.redirect_url) {
						window.location.href = res.data.redirect_url;
					} else {
						window.location.href = NotesAdda.landing_url || '/';
					}
				}).fail(function() {
					window.location.href = NotesAdda.landing_url || '/';
				});
			});
		}
	};

	window.NotesAddaApp.Auth = Auth;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('auth', Auth);
	}

})(jQuery);
