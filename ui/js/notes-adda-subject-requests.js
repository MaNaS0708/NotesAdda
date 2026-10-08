/**
 * Notes Adda - Subject Requests Module
 * Manages subject requests submitted by students/experts, tracking user requests and admin moderation.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	if (typeof window.NotesAddaApp === 'undefined') {
		window.NotesAddaApp = {};
	}

	const SubjectRequests = {
		init: function() {
			this.bindEvents();
		},

		bindEvents: function() {
			const self = this;
			const app = window.NotesAddaApp;

			// Modal Subtabs Switcher
			$(document).on('click', '.na-modal-tab-btn', function(e) {
				e.preventDefault();
				const target = $(this).data('subtab');
				$('.na-modal-tab-btn').removeClass('active');
				$(this).addClass('active');
				$('.na-modal-subtab-pane').removeClass('active').hide();
				$(`#na-subtab-${target}`).addClass('active').show();

				if (target === 'my-requests') {
					self.loadMyRequests();
				}
			});

			// Submit Subject Request Form
			$('#na-subject-request-form').on('submit', function(e) {
				e.preventDefault();
				self.submitRequest();
			});

			// Admin Filter Tabs
			$('#na-view-subject-requests .na-tab-btn').on('click', function(e) {
				e.preventDefault();
				const status = $(this).data('subject-req-status');
				$('#na-view-subject-requests .na-tab-btn').removeClass('active');
				$(this).addClass('active');
				app.subjectRequestFilter = status;
				self.loadAdminRequests();
			});

			// Admin Approve Action
			$(document).on('click', '.na-btn-approve-subject-req', function(e) {
				e.preventDefault();
				const id = $(this).data('id');
				const name = $(this).data('name');
				self.approveRequest(id, name, $(this));
			});

			// Admin Reject Action
			$(document).on('click', '.na-btn-reject-subject-req', function(e) {
				e.preventDefault();
				const id = $(this).data('id');
				const name = $(this).data('name');
				self.rejectRequest(id, name, $(this));
			});

			// Admin Delete Action
			$(document).on('click', '.na-btn-delete-subject-req', function(e) {
				e.preventDefault();
				const id = $(this).data('id');
				if (confirm('Are you sure you want to delete this subject request?')) {
					self.deleteRequest(id, $(this));
				}
			});
		},

		openModal: function() {
			$('#na-subject-request-form')[0].reset();
			$('#na-subject-request-msg').text('').removeClass('error success');
			$('.na-modal-tab-btn[data-subtab="new-request"]').trigger('click');
			$('#na-subject-request-modal').addClass('open');
		},

		submitRequest: function() {
			const self = this;
			const app = window.NotesAddaApp;
			const $form = $('#na-subject-request-form');
			const $btn = $('#na-submit-subject-req-btn');
			const $msg = $('#na-subject-request-msg');

			const name = $('#na-req-subject-name').val().trim();
			const reason = $('#na-req-subject-reason').val().trim();

			if (!name) {
				$msg.text('Subject name is required.').addClass('error');
				return;
			}

			$btn.prop('disabled', true).find('.na-btn-spinner').show();
			$msg.text('').removeClass('error success');

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_request_subject',
				name: name,
				reason: reason,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				if (res.success) {
					$msg.text(res.data.message || 'Subject request submitted successfully.').addClass('success');
					$form[0].reset();
					app.showToast('Subject requested!', 'success');
					self.loadMyRequests(true);
				} else {
					$msg.text(res.data ? res.data.message : 'Error submitting request.').addClass('error');
				}
			}).fail(function() {
				$btn.prop('disabled', false).find('.na-btn-spinner').hide();
				$msg.text('Network error submitting request.').addClass('error');
			});
		},

		loadMyRequests: function(quiet) {
			const self = this;
			const $container = $('#na-my-requests-list');
			const $loading = $('#na-my-requests-loading');
			const $empty = $('#na-my-requests-empty');

			if (!quiet) {
				$loading.show();
				$empty.hide();
				$container.empty();
			}

			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_my_subject_requests',
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data;
					const count = items.length;
					$('#na-my-requests-badge').text(count).toggle(count > 0);

					if (count === 0) {
						$empty.show();
					} else {
						self.renderMyRequests(items, $container);
					}
				}
			});
		},

		renderMyRequests: function(requests, $container) {
			const app = window.NotesAddaApp;
			$container.empty();

			requests.forEach(function(r) {
				let statusClass = 'na-badge-pending';
				if (r.status === 'approved') statusClass = 'na-badge-verified';
				if (r.status === 'rejected') statusClass = 'na-badge-rejected';

				const html = `
					<div class="na-subject-req-card" style="margin-bottom:10px;">
						<div class="na-subject-req-info">
							<div class="na-subject-req-header">
								<span class="na-subject-req-name">${app.escapeHtml(r.name)}</span>
								<span class="na-badge ${statusClass}">${app.escapeHtml(r.status)}</span>
							</div>
							<div class="na-subject-req-meta">
								<span>Requested on ${app.formatDate(r.created_at)}</span>
							</div>
							${r.reason ? `<div class="na-subject-req-reason">"${app.escapeHtml(r.reason)}"</div>` : ''}
						</div>
					</div>
				`;
				$container.append(html);
			});
		},

		loadAdminRequests: function() {
			if (!NotesAdda.can_manage_subjects) return;
			const self = this;
			const app = window.NotesAddaApp;
			const $container = $('#na-subject-requests-results');
			const $loading = $('#na-subject-requests-loading');
			const $empty = $('#na-subject-requests-empty');
			const $summary = $('#na-subject-requests-count');

			$loading.show();
			$empty.hide();
			$container.empty();

			$.get(NotesAdda.ajax_url, {
				action: 'notes_adda_get_subject_requests',
				status: app.subjectRequestFilter || 'pending',
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				$loading.hide();
				if (res.success && res.data) {
					const items = res.data;
					$summary.text(`Subject requests (${items.length} total)`);

					if (items.length === 0) {
						$empty.show();
					} else {
						self.renderAdminRequests(items, $container);
					}
				}
			});
		},

		renderAdminRequests: function(requests, $container) {
			const app = window.NotesAddaApp;
			$container.empty();

			requests.forEach(function(r) {
				let statusBadge = '<span class="na-badge na-badge-pending">Pending</span>';
				if (r.status === 'approved') statusBadge = '<span class="na-badge na-badge-verified">Approved</span>';
				if (r.status === 'rejected') statusBadge = '<span class="na-badge na-badge-rejected">Rejected</span>';

				const html = `
					<div class="na-subject-req-card" data-id="${r.id}">
						<div class="na-subject-req-info">
							<div class="na-subject-req-header">
								<span class="na-subject-req-name">${app.escapeHtml(r.name)}</span>
								${statusBadge}
							</div>
							<div class="na-subject-req-meta">
								<span>By: <strong>${app.escapeHtml(r.requester_name || 'User')}</strong></span>
								<span>&bull;</span>
								<span>Date: ${app.formatDate(r.created_at)}</span>
							</div>
							${r.reason ? `<div class="na-subject-req-reason">${app.escapeHtml(r.reason)}</div>` : ''}
						</div>
						<div class="na-subject-req-actions">
							${r.status === 'pending' ? `
								<button type="button" class="na-btn na-btn-primary na-btn-sm na-btn-approve-subject-req" data-id="${r.id}" data-name="${app.escapeHtml(r.name)}">
									<span class="dashicons dashicons-yes-alt"></span> <span>Approve</span>
								</button>
								<button type="button" class="na-btn na-btn-secondary na-btn-sm na-btn-reject-subject-req" data-id="${r.id}" data-name="${app.escapeHtml(r.name)}">
									<span class="dashicons dashicons-dismiss"></span> <span>Reject</span>
								</button>
							` : `
								<button type="button" class="na-btn na-btn-ghost na-btn-sm na-btn-delete-subject-req" data-id="${r.id}">
									<span class="dashicons dashicons-trash"></span>
								</button>
							`}
						</div>
					</div>
				`;
				$container.append(html);
			});
		},

		approveRequest: function(id, name, $btn) {
			const self = this;
			const app = window.NotesAddaApp;
			$btn.prop('disabled', true);

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_approve_subject_request',
				id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast(`Subject "${name}" approved and added to catalog!`, 'success');
					self.loadAdminRequests();
					app.loadSubjects();
				} else {
					$btn.prop('disabled', false);
					app.showToast(res.data ? res.data.message : 'Error approving request', 'error');
				}
			});
		},

		rejectRequest: function(id, name, $btn) {
			const self = this;
			const app = window.NotesAddaApp;
			$btn.prop('disabled', true);

			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_reject_subject_request',
				id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast(`Subject request "${name}" rejected.`, 'success');
					self.loadAdminRequests();
				} else {
					$btn.prop('disabled', false);
					app.showToast(res.data ? res.data.message : 'Error rejecting request', 'error');
				}
			});
		},

		deleteRequest: function(id, $btn) {
			const self = this;
			const app = window.NotesAddaApp;
			$.post(NotesAdda.ajax_url, {
				action: 'notes_adda_delete_subject_request',
				id: id,
				_ajax_nonce: NotesAdda.nonce
			}, function(res) {
				if (res.success) {
					app.showToast('Request deleted.', 'success');
					self.loadAdminRequests();
				} else {
					app.showToast(res.data ? res.data.message : 'Error deleting request', 'error');
				}
			});
		}
	};

	window.NotesAddaApp.SubjectRequests = SubjectRequests;
	if (window.NotesAddaApp.registerModule) {
		window.NotesAddaApp.registerModule('subjectRequests', SubjectRequests);
	}

})(jQuery);
