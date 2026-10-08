/**
 * Notes Adda - Common & Shared Utilities Module
 * Application state registry, view router, toast notifications, date formatting, and modal management.
 *
 * @package Notes_Adda
 */

(function($) {
	'use strict';

	window.NotesAddaApp = {
		activeView: 'library',
		currentPage: 1,
		subjects: [],
		reviewFilter: 'pending',
		subjectRequestFilter: 'pending',
		adminSubjectRequests: [],
		mySubjectRequests: [],
		userSearchTerm: '',
		userPage: 1,
		modules: {},

		/**
		 * Register page-wise or feature-wise sub-module.
		 */
		registerModule: function(name, moduleObj) {
			this.modules[name] = moduleObj;
		},

		/**
		 * Initialize core application services and registered sub-modules.
		 */
		init: function() {
			if (typeof NotesAdda === 'undefined') {
				return;
			}

			const self = this;
			self.bindCommonEvents();
			self.initMobileNav();

			// Load taxonomy subjects once for global use
			self.loadSubjects(function() {
				// Initialize sub-modules
				$.each(self.modules, function(name, mod) {
					if (typeof mod.init === 'function') {
						mod.init();
					}
				});

				// Hash routing or default library view
				const initialHash = window.location.hash ? window.location.hash.substring(1) : '';
				if (initialHash) {
					if (initialHash.indexOf('note-') === 0) {
						const noteId = parseInt(initialHash.replace('note-', ''), 10);
						if (noteId && self.modules.noteDetail && typeof self.modules.noteDetail.openNoteDetailsPage === 'function') {
							self.modules.noteDetail.openNoteDetailsPage(noteId, false);
						} else {
							self.switchView('library');
						}
					} else {
						self.switchView(initialHash);
					}
				} else {
					self.switchView('library');
				}
			});
		},

		/**
		 * Bind shared UI interactions (modals, escape key, hashchange, navigation items).
		 */
		bindCommonEvents: function() {
			const self = this;

			// Navigation items click
			$(document).on('click', '.na-nav-item', function(e) {
				const view = $(this).data('view');
				if (view) {
					e.preventDefault();
					self.switchView(view);
					self.closeMobileDrawer();
				}
			});

			// Hashchange support for back/forward browser navigation
			$(window).on('hashchange', function() {
				const hash = window.location.hash ? window.location.hash.substring(1) : '';
				if (!hash) {
					self.switchView('library');
					return;
				}
				if (hash.indexOf('note-') === 0) {
					const noteId = parseInt(hash.replace('note-', ''), 10);
					if (noteId && self.modules.noteDetail && typeof self.modules.noteDetail.openNoteDetailsPage === 'function') {
						self.modules.noteDetail.openNoteDetailsPage(noteId, false);
					}
				} else if (hash !== self.activeView) {
					self.switchView(hash);
				}
			});

			// Universal Modal Close Handler (X icons, Cancel buttons)
			$(document).on('click', '.na-modal-close-btn', function(e) {
				e.preventDefault();
				const modalKey = $(this).data('modal');
				if (modalKey === 'form') {
					$('#na-note-form-modal').removeClass('open');
					if (self.modules.uploadModal && typeof self.modules.uploadModal.reset === 'function') {
						self.modules.uploadModal.reset();
					}
				} else if (modalKey === 'details') {
					$('#na-note-details-modal').removeClass('open');
				} else if (modalKey === 'review') {
					$('#na-review-modal').removeClass('open');
				} else if (modalKey === 'subject-request') {
					$('#na-subject-request-modal').removeClass('open');
				} else {
					$('.na-modal-overlay').removeClass('open');
				}
			});

			// Close modal on background overlay click
			$(document).on('click', '.na-modal-overlay', function(e) {
				if ($(e.target).hasClass('na-modal-overlay')) {
					$(this).removeClass('open');
				}
			});

			// Close on ESC key
			$(document).on('keydown', function(e) {
				if (e.key === 'Escape' || e.keyCode === 27) {
					$('.na-modal-overlay').removeClass('open');
					$('.na-notifications-dropdown').hide();
					$('.na-notifications-toggle').attr('aria-expanded', 'false');
					self.closeMobileDrawer();
				}
			});
		},

		/**
		 * Mobile drawer toggle and backdrop events.
		 */
		initMobileNav: function() {
			const self = this;
			$(document).on('click', '#na-mobile-drawer-toggle', function(e) {
				e.preventDefault();
				self.toggleMobileDrawer();
			});

			$(document).on('click', '#na-sidebar-close-btn, #na-sidebar-backdrop', function(e) {
				e.preventDefault();
				self.closeMobileDrawer();
			});
		},

		toggleMobileDrawer: function() {
			const $sidebar = $('#na-app-sidebar');
			const $backdrop = $('#na-sidebar-backdrop');
			const isOpen = $sidebar.hasClass('na-drawer-open');
			if (isOpen) {
				this.closeMobileDrawer();
			} else {
				$sidebar.addClass('na-drawer-open');
				$backdrop.addClass('na-backdrop-active');
			}
		},

		closeMobileDrawer: function() {
			$('#na-app-sidebar').removeClass('na-drawer-open');
			$('#na-sidebar-backdrop').removeClass('na-backdrop-active');
		},

		/**
		 * Switch current application view without reloading.
		 */
		switchView: function(view) {
			const self = this;
			if (!view) view = 'library';

			// Verify view element exists
			const $targetView = $(`#na-view-${view}`);
			if ($targetView.length === 0) {
				view = 'library';
			}

			self.activeView = view;
			self.currentPage = 1;

			// Update hash smoothly without scroll jump
			if (window.location.hash !== `#${view}`) {
				if (history.pushState) {
					history.pushState(null, null, `#${view}`);
				} else {
					window.location.hash = `#${view}`;
				}
			}

			// Navigation items state
			$('.na-nav-item').removeClass('active');
			$(`.na-nav-item[data-view="${view}"]`).addClass('active');

			// Views toggle
			$('.na-view').removeClass('active').hide();
			$(`#na-view-${view}`).addClass('active').show();

			// Trigger module view loaders
			if (view === 'library' && self.modules.library && typeof self.modules.library.loadNotes === 'function') {
				self.modules.library.loadNotes();
			} else if (view === 'my-notes' && self.modules.myNotes && typeof self.modules.myNotes.loadNotes === 'function') {
				self.modules.myNotes.loadNotes();
			} else if (view === 'bookmarks' && self.modules.bookmarks && typeof self.modules.bookmarks.loadNotes === 'function') {
				self.modules.bookmarks.loadNotes();
			} else if (view === 'review-queue' && self.modules.reviewQueue && typeof self.modules.reviewQueue.loadQueue === 'function') {
				self.modules.reviewQueue.loadQueue();
			} else if (view === 'subjects' && self.modules.subjectManagement && typeof self.modules.subjectManagement.render === 'function') {
				self.modules.subjectManagement.render();
			} else if (view === 'subject-requests' && self.modules.subjectRequests && typeof self.modules.subjectRequests.loadAdminRequests === 'function') {
				self.modules.subjectRequests.loadAdminRequests();
			} else if (view === 'users' && self.modules.userManagement && typeof self.modules.userManagement.loadUsers === 'function') {
				self.modules.userManagement.loadUsers();
			}
		},

		/**
		 * Fetch subjects from WordPress REST/AJAX endpoint.
		 */
		loadSubjects: function(callback) {
			const self = this;
			$.get(NotesAdda.ajax_url, { action: 'notes_adda_get_subjects' }, function(res) {
				if (res.success && res.data) {
					self.subjects = res.data;
					self.populateSubjectDropdowns();
				}
				if (typeof callback === 'function') {
					callback(self.subjects);
				}
			}).fail(function() {
				if (typeof callback === 'function') {
					callback([]);
				}
			});
		},

		/**
		 * Populate subject dropdowns across filter toolbar and note upload form.
		 */
		populateSubjectDropdowns: function() {
			const self = this;
			const $filter = $('#na-subject-filter');
			const $formSelect = $('#na-note-subject');

			$filter.find('option:not(:first)').remove();
			$formSelect.find('option:not(:first)').remove();

			if (!self.subjects || self.subjects.length === 0) {
				$('#na-no-subjects-warning').show();
				return;
			}
			$('#na-no-subjects-warning').hide();

			self.subjects.forEach(function(sub) {
				$filter.append(new Option(sub.name, sub.name));
				$formSelect.append(new Option(sub.name, sub.name));
			});
		},

		/**
		 * Clean date formatter for human readability.
		 * Formats "2026-10-08 14:32:00" into "8 Oct 2026" or "Uploaded 8 Oct 2026".
		 */
		formatDate: function(dateStr, includePrefix) {
			if (!dateStr) return '';
			try {
				const cleanStr = String(dateStr).trim().replace(' ', 'T');
				const d = new Date(cleanStr);
				let day, month, year;
				const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

				if (isNaN(d.getTime())) {
					const parts = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
					if (parts) {
						year = parts[1];
						month = months[parseInt(parts[2], 10) - 1];
						day = parseInt(parts[3], 10);
					} else {
						return dateStr;
					}
				} else {
					day = d.getDate();
					month = months[d.getMonth()];
					year = d.getFullYear();
				}

				const formatted = `${day} ${month} ${year}`;
				return includePrefix ? `Uploaded ${formatted}` : formatted;
			} catch (e) {
				return dateStr;
			}
		},

		/**
		 * Safe HTML entity escaper.
		 */
		escapeHtml: function(text) {
			if (!text) return '';
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
		},

		/**
		 * Render quarter-star rating SVGs using SVG gradient defs.
		 */
		renderQuarterStarsHtml: function(rating, size) {
			rating = parseFloat(rating) || 0;
			size = size || 14;
			let starsHtml = '<span class="na-quarter-stars" aria-label="' + rating.toFixed(2) + ' out of 5 stars">';
			for (let i = 1; i <= 5; i++) {
				let fillAttr = 'url(#na-star-grad-0)';
				if (rating >= i) {
					fillAttr = 'url(#na-star-grad-100)';
				} else if (rating > i - 1) {
					const rem = rating - (i - 1);
					if (rem >= 0.75) {
						fillAttr = 'url(#na-star-grad-75)';
					} else if (rem >= 0.5) {
						fillAttr = 'url(#na-star-grad-50)';
					} else if (rem >= 0.25) {
						fillAttr = 'url(#na-star-grad-25)';
					}
				}
				starsHtml += `
					<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="${fillAttr}" stroke="#ffb703" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" class="na-quarter-star-svg" aria-hidden="true">
						<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
					</svg>
				`;
			}
			starsHtml += '</span>';
			return starsHtml;
		},

		/**
		 * Unified pagination component renderer.
		 */
		renderPagination: function(current, total, $container, onPageClick) {
			$container.empty();
			if (total <= 1) return;

			// Previous
			const $prev = $(`<button type="button" class="na-page-btn na-page-prev" ${current === 1 ? 'disabled' : ''} aria-label="Previous page">&larr;</button>`);
			$prev.on('click', function() {
				if (current > 1 && typeof onPageClick === 'function') {
					onPageClick(current - 1);
				}
			});
			$container.append($prev);

			// Page numbers
			let start = Math.max(1, current - 2);
			let end = Math.min(total, current + 2);
			for (let i = start; i <= end; i++) {
				const $page = $(`<button type="button" class="na-page-btn ${i === current ? 'active' : ''}" aria-label="Page ${i}">${i}</button>`);
				(function(pageIndex) {
					$page.on('click', function() {
						if (pageIndex !== current && typeof onPageClick === 'function') {
							onPageClick(pageIndex);
						}
					});
				})(i);
				$container.append($page);
			}

			// Next
			const $next = $(`<button type="button" class="na-page-btn na-page-next" ${current === total ? 'disabled' : ''} aria-label="Next page">&rarr;</button>`);
			$next.on('click', function() {
				if (current < total && typeof onPageClick === 'function') {
					onPageClick(current + 1);
				}
			});
			$container.append($next);
		},

		/**
		 * Cyber HUD Toast Message.
		 */
		showToast: function(msg, type) {
			let $toast = $('#na-toast');
			if ($toast.length === 0) {
				$toast = $('<div id="na-toast" class="na-toast" role="status" aria-live="polite"></div>');
				$('body').append($toast);
			}

			$toast.removeClass('success error show').text(msg);
			if (type === 'success') {
				$toast.addClass('success');
			} else if (type === 'error') {
				$toast.addClass('error');
			}

			$toast.addClass('show');
			clearTimeout(this._toastTimeout);
			this._toastTimeout = setTimeout(function() {
				$toast.removeClass('show');
			}, 3600);
		}
	};

	// Start App on DOM Ready
	$(document).ready(function() {
		window.NotesAddaApp.init();
	});

})(jQuery);
