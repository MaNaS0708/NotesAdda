/**
 * Notes Adda - Monolithic Application Script (Deprecated)
 * 
 * Notice: This monolithic script has been refactored into modular, page-wise files:
 * - ui/js/notes-adda-common.js (Core architecture, state, routing, dialogs, helpers)
 * - ui/js/notes-adda-auth.js (Authentication & register forms)
 * - ui/js/notes-adda-notifications.js (Notification dropdown & polling)
 * - ui/js/notes-adda-expert-ratings.js (Quarter-star rating widget)
 * - ui/js/notes-adda-library.js (Browse, search, filters, pagination, like/bookmark)
 * - ui/js/notes-adda-my-notes.js (User submitted notes management)
 * - ui/js/notes-adda-bookmarks.js (User saved bookmarks)
 * - ui/js/notes-adda-note-detail.js (Dedicated note view & preview modal)
 * - ui/js/notes-adda-upload-modal.js (Upload & edit modal, dropzone)
 * - ui/js/notes-adda-review-queue.js (Expert review queue & decisions)
 * - ui/js/notes-adda-subject-requests.js (Subject request modal & review)
 * - ui/js/notes-adda-subject-management.js (Admin subject management)
 * - ui/js/notes-adda-user-management.js (Admin user directory & role management)
 */
(function() {
    'use strict';
    if (typeof window.NotesAddaApp !== 'undefined') {
        // Modular scripts are already active
        return;
    }
    console.warn('NotesAdda: notes-adda-app.js is deprecated. Frontend functionality has been modularized.');
})();
