/**
 * Notes Adda - Landing Page Interactive Script & Auth Flow
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $modal = $('#na-landing-auth-modal');
        if (!$modal.length) return;

        function openAuthModal(mode) {
            $modal.show().attr('aria-hidden', 'false');
            $('body').addClass('na-modal-open');
            switchTab(mode === 'register' ? 'register' : 'login');
            // Focus first visible input
            setTimeout(function() {
                const $targetView = mode === 'register' ? $('#na-register-view') : $('#na-login-view');
                $targetView.find('input:visible:first').focus();
            }, 100);
        }

        function closeAuthModal() {
            $modal.hide().attr('aria-hidden', 'true');
            $('body').removeClass('na-modal-open');
            $('.na-auth-message').text('').removeClass('error success');
        }

        function switchTab(target) {
            $('.na-auth-tab').removeClass('active');
            $(`.na-auth-tab[data-switch="${target}"]`).addClass('active');

            $('.na-auth-view').removeClass('active').hide();
            $('#na-' + target + '-view').addClass('active').show();
            $('.na-auth-message').text('').removeClass('error success');
        }

        // Click on auth triggers (Sign In, Create Account, Get Started Free)
        $(document).on('click', '.na-auth-trigger', function(e) {
            e.preventDefault();
            const mode = $(this).data('auth-mode') || 'login';
            openAuthModal(mode);
        });

        // Close triggers
        $(document).on('click', '#na-landing-auth-close, #na-landing-auth-backdrop', function(e) {
            e.preventDefault();
            closeAuthModal();
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $modal.is(':visible')) {
                closeAuthModal();
            }
        });

        // Tab switching
        $(document).on('click', '.na-auth-tab[data-switch]', function(e) {
            e.preventDefault();
            const target = $(this).data('switch');
            switchTab(target);
        });

        // Check URL on load for ?auth=login, ?auth=register, #login, #register
        const urlParams = new URLSearchParams(window.location.search);
        const authParam = urlParams.get('auth');
        const hash = window.location.hash;

        if (authParam === 'register' || hash === '#register') {
            openAuthModal('register');
        } else if (authParam === 'login' || hash === '#login') {
            openAuthModal('login');
        }

        // Login Form AJAX
        $('#na-login-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const $msg = $form.find('.na-auth-message');

            $btn.prop('disabled', true);
            $btn.find('.na-btn-text').text('Signing in...');
            $btn.find('.na-btn-spinner').show();
            $msg.text('').removeClass('error success');

            const postData = $form.serialize() + '&action=notes_adda_login&_ajax_nonce=' + NotesAddaLanding.nonce;

            $.ajax({
                url: NotesAddaLanding.ajax_url,
                type: 'POST',
                data: postData,
                success: function(res) {
                    if (res.success) {
                        $msg.text('Signed in successfully! Loading study workspace...').addClass('success');
                        window.location.href = NotesAddaLanding.app_url;
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

        // Register Form AJAX
        $('#na-register-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const $msg = $form.find('.na-auth-message');

            $btn.prop('disabled', true);
            $btn.find('.na-btn-text').text('Creating Account...');
            $btn.find('.na-btn-spinner').show();
            $msg.text('').removeClass('error success');

            const postData = $form.serialize() + '&action=notes_adda_register&_ajax_nonce=' + NotesAddaLanding.nonce;

            $.ajax({
                url: NotesAddaLanding.ajax_url,
                type: 'POST',
                data: postData,
                success: function(res) {
                    if (res.success) {
                        $msg.text('Account created! Entering study workspace...').addClass('success');
                        window.location.href = NotesAddaLanding.app_url;
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
                    const err = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : 'Registration error occurred.';
                    $msg.text(err).addClass('error');
                }
            });
        });
    });
})(jQuery);
