/**
 * Notes Adda - Notifications Module
 * In-app notification center, real-time unread badges, mark-as-read actions, and target link routing.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const Notifications = {
		init: function() {
			this.bindEvents();
			this.loadNotifications();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Bell Button Toggle
			$(document).on('click', '.na-notifications-toggle', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const $wrap = $(this).closest('.na-notifications-menu-wrap');
				const $dropdown = $wrap.find('.na-notifications-dropdown');
				const isVisible = $dropdown.is(':visible');

				$('.na-notifications-dropdown').hide();
				$('.na-notifications-toggle').attr('aria-expanded', 'false');

				if (!isVisible) {
					$dropdown.show();
					$(this).attr('aria-expanded', 'true');
					self.loadNotifications();
				}
			});

			// Close Dropdown when clicking outside
			$(document).on('click', function(e) {
				if ($(e.target).closest('.na-notifications-menu-wrap').length === 0) {
					$('.na-notifications-dropdown').hide();
					$('.na-notifications-toggle').attr('aria-expanded', 'false');
				}
			});

			// Mark All As Read
			$(document).on('click', '.na-mark-all-read-btn', function(e) {
				e.preventDefault();
				self.markAllRead();
			});

			// Click on Notification Item
			$(document).on('click', '.na-notif-item', function(e) {
				const notifId = $(this).data('id');
				const noteId = $(this).data('note-id');

				if ($(this).hasClass('na-notif-unread') && notifId) {
					self.markSingleRead(notifId);
				}

				if (noteId && app.modules.noteDetail && typeof app.modules.noteDetail.openNoteDetailsPage === 'function') {
					$('.na-notifications-dropdown').hide();
					app.modules.noteDetail.openNoteDetailsPage(noteId, true);
				}
			});
		},

		loadNotifications: function() {
			const self = this;
			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_notifications',
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success && res.data) {
					self.renderNotifications(res.data.items || [], res.data.unread_count || 0);
				}
			});
		},

		renderNotifications: function(items, unreadCount) {
			const app = window.NotesAddaApp;
			const $badges = $('.na-notifications-count-badge');
			const $empties = $('.na-notifications-empty');
			const $itemLists = $('.na-notifications-items');

			// Update Unread Badges
			if (unreadCount > 0) {
				$badges.text(unreadCount).show();
			} else {
				$badges.hide();
			}

			$itemLists.empty();

			if (items.length === 0) {
				$empties.show();
			} else {
				$empties.hide();
				items.forEach(function(item) {
					const isUnread = (parseInt(item.is_read, 10) === 0);
					const itemHtml = `
						<div class="na-notif-item ${isUnread ? 'na-notif-unread' : ''}" data-id="${item.id}" data-note-id="${item.note_id || ''}">
							<div class="na-notif-title">${app.escapeHtml(item.title || 'Notification')}</div>
							<div class="na-notif-body">${app.escapeHtml(item.message || '')}</div>
							<div class="na-notif-time">${app.formatDate(item.created_at)}</div>
						</div>
					`;
					$itemLists.append(itemHtml);
				});
			}
		},

		markSingleRead: function(id) {
			const self = this;
			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_mark_notification_read',
				notification_id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					self.loadNotifications();
				}
			});
		},

		markAllRead: function() {
			const self = this;
			const app = window.NotesAddaApp;
			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_mark_all_notifications_read',
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast('All notifications marked as read', 'success');
					self.loadNotifications();
				}
			});
		}
	};

	window.NotesAddaApp.Notifications = Notifications;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('notifications', Notifications);
	}

})(jQuery);
