/**
 * Notes Adda - User Management Module
 * Administrator directory for member searches, pagination, and role assignment.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const UserManagement = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// User Search Input Enter
			$('#na-user-search-input').on('keypress', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					app.userSearchTerm = $(this).val().trim();
					app.userPage = 1;
					self.loadUsers();
				}
			});

			// User Search Button
			$('#na-search-users-btn').on('click', function(e) {
				e.preventDefault();
				app.userSearchTerm = $('#na-user-search-input').val().trim();
				app.userPage = 1;
				self.loadUsers();
			});

			// Role Selection Change
			$(document).on('change', '.na-user-role-select', function() {
				const userId = $(this).data('user-id');
				const newRole = $(this).val();
				self.updateRole(userId, newRole, $(this));
			});
		},

		loadUsers: function() {
			if (!NotesAdda.can_manage_users) return;
			const self = this;
			const app = window.NotesAddaApp;
			const $container = $('#na-users-results');
			const $loading = $('#na-users-loading');
			const $empty = $('#na-users-empty');
			const $pagination = $('#na-users-pagination');
			const $summary = $('#na-users-count');

			$loading.show();
			$empty.hide();
			$container.empty();
			$pagination.empty();

			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_users',
				page: app.userPage || 1,
				per_page: 10,
				search: app.userSearchTerm || '',
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data.items || res.data.users || [];
					const total = parseInt(res.data.total, 10) || 0;
					const totalPages = parseInt(res.data.total_pages, 10) || 1;

					$summary.text(`Community Members (${total} total)`);

					if (items.length === 0) {
						$empty.show();
					} else {
						self.renderUsers(items, $container);
						app.renderPagination(app.userPage || 1, totalPages, $pagination, function(newPage) {
							app.userPage = newPage;
							self.loadUsers();
							$('html, body').animate({ scrollTop: $('#na-view-users').offset().top - 80 }, 200);
						});
					}
				} else {
					$empty.show();
				}
			}).fail(function() {
				$loading.hide();
				$empty.show();
				app.showToast('Failed to load user list', 'error');
			});
		},

		renderUsers: function(users, $container) {
			const app = window.NotesAddaApp;
			$container.empty();

			// Desktop Table
			let tableHtml = `
				<div class="na-users-table-wrapper">
					<table class="na-users-table">
						<thead>
							<tr>
								<th>User</th>
								<th>Email</th>
								<th>Notes Uploaded</th>
								<th>Registered</th>
								<th>Role Assignment</th>
							</tr>
						</thead>
						<tbody>
			`;

			// Mobile Cards
			let mobileCardsHtml = '<div class="na-users-mobile-cards">';

			users.forEach(function(u) {
				const isSelf = (parseInt(u.id, 10) === parseInt(NotesAdda.user_id, 10));
				const isOwner = !!u.is_owner;
				const disabledAttr = (isSelf || isOwner) ? 'disabled' : '';

				let userRole = (u.role || 'student').toLowerCase();
				if (userRole.indexOf('admin') !== -1) {
					userRole = 'admin';
				} else if (userRole.indexOf('expert') !== -1) {
					userRole = 'expert';
				} else {
					userRole = 'student';
				}

				const roleSelect = `
					<select class="na-select na-user-role-select" data-user-id="${u.id}" ${disabledAttr} aria-label="Role for ${app.escapeHtml(u.display_name)}">
						<option value="student" ${userRole === 'student' ? 'selected' : ''}>Student</option>
						<option value="expert" ${userRole === 'expert' ? 'selected' : ''}>Expert</option>
						<option value="admin" ${userRole === 'admin' ? 'selected' : ''}>Admin</option>
					</select>
				`;

				const loginName = u.user_login || u.username || '';

				// Table Row
				tableHtml += `
					<tr data-user-id="${u.id}">
						<td>
							<div class="na-user-row-user">
								<img src="${app.escapeHtml(u.avatar_url || '')}" alt="${app.escapeHtml(u.display_name)}">
								<div class="na-user-row-info">
									<span class="na-user-row-name">${app.escapeHtml(u.display_name)}</span>
									<span class="na-user-row-login">@${app.escapeHtml(loginName)}</span>
								</div>
							</div>
						</td>
						<td>${app.escapeHtml(u.email || '-')}</td>
						<td>${u.notes_count || 0}</td>
						<td>${app.formatDate(u.registered)}</td>
						<td>${roleSelect}</td>
					</tr>
				`;

				// Mobile Card
				mobileCardsHtml += `
					<div class="na-user-mobile-card" data-user-id="${u.id}">
						<div class="na-user-row-user">
							<img src="${app.escapeHtml(u.avatar_url || '')}" alt="${app.escapeHtml(u.display_name)}">
							<div class="na-user-row-info">
								<span class="na-user-row-name">${app.escapeHtml(u.display_name)}</span>
								<span class="na-user-row-login">@${app.escapeHtml(loginName)}</span>
							</div>
						</div>
						<div style="font-size:12px; color:var(--na-muted);">
							<div><strong>Email:</strong> ${app.escapeHtml(u.email || '-')}</div>
							<div><strong>Notes:</strong> ${u.notes_count || 0}</div>
							<div><strong>Joined:</strong> ${app.formatDate(u.registered)}</div>
						</div>
						<div style="margin-top:4px;">
							<label style="font-size:11.5px; color:var(--na-muted); display:block; margin-bottom:4px;">Assign Role:</label>
							${roleSelect}
						</div>
					</div>
				`;
			});

			tableHtml += '</tbody></table></div>';
			mobileCardsHtml += '</div>';

			$container.append(tableHtml);
			$container.append(mobileCardsHtml);
		},

		updateRole: function(userId, newRole, $select) {
			const app = window.NotesAddaApp;
			$select.prop('disabled', true);

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_update_user_role',
				target_user_id: userId,
				user_id: userId,
				new_role: newRole,
				role: newRole,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$select.prop('disabled', false);
				if (res.success) {
					app.showToast('User role updated successfully.', 'success');
				} else {
					app.showToast(res.data ? res.data.message : 'Error updating role.', 'error');
				}
			}).fail(function() {
				$select.prop('disabled', false);
				app.showToast('Network error while updating user role.', 'error');
			});
		}
	};

	window.NotesAddaApp.UserManagement = UserManagement;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('userManagement', UserManagement);
	}

})(jQuery);
