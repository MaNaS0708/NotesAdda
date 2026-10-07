<?php
if ( ! is_user_logged_in() ) {
    $auth_mode = isset( $_GET['auth'] ) ? sanitize_text_field( wp_unslash( $_GET['auth'] ) ) : '';
    $args      = array();
    if ( in_array( $auth_mode, array( 'login', 'register' ), true ) ) {
        $args['auth'] = $auth_mode;
    }
    $target_url = class_exists( 'Notes_Adda_Frontend' ) ? Notes_Adda_Frontend::get_landing_url( $args ) : home_url( '/' );
    wp_safe_redirect( $target_url );
    exit;
}
?>
<div id="notes-adda-app" class="notes-adda-app-container">
    <?php if ( ! is_user_logged_in() ) : ?>
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
    <?php else : 
        $current_user = wp_get_current_user();
        $owner_id     = (int) get_option( 'notes_adda_owner_id' );
        $is_owner     = ( $owner_id > 0 && (int) $current_user->ID === $owner_id );

        $can_upload   = $is_owner || current_user_can( 'notes_adda_upload_notes' ) || current_user_can( 'manage_options' );
        $can_review   = $is_owner || current_user_can( 'notes_adda_review_notes' ) || current_user_can( 'manage_options' );
        $can_subjects = $is_owner || current_user_can( 'notes_adda_manage_subjects' ) || current_user_can( 'manage_options' );
        $can_users    = $is_owner || current_user_can( 'notes_adda_manage_users' ) || current_user_can( 'manage_options' );

        $role_badge_class = 'na-role-student';
        $role_badge_text  = 'Student';
        if ( $is_owner ) {
            $role_badge_class = 'na-role-owner';
            $role_badge_text  = 'Owner';
        } elseif ( $can_users ) {
            $role_badge_class = 'na-role-admin';
            $role_badge_text  = 'Notes Adda Admin';
        } elseif ( $can_review ) {
            $role_badge_class = 'na-role-expert';
            $role_badge_text  = 'Expert';
        }
    ?>
        <div class="na-app-layout">
            <!-- Sidebar -->
            <aside class="na-sidebar">
                <div class="na-sidebar-header">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="na-sidebar-brand-link" title="Notes Adda">
                        <img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-sidebar-brand-logo" width="220" height="46" style="max-width: 235px; height: 46px; width: auto; object-fit: contain;">
                    </a>
                </div>
                
                <div class="na-user-card">
                    <div class="na-user-avatar">
                        <?php echo get_avatar( $current_user->ID, 44 ); ?>
                    </div>
                    <div class="na-user-details">
                        <span class="na-user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
                        <div class="na-user-meta-row">
                            <span class="na-user-handle">@<?php echo esc_html( $current_user->user_login ); ?></span>
                            <span class="na-role-badge <?php echo esc_attr( $role_badge_class ); ?>"><?php echo esc_html( $role_badge_text ); ?></span>
                        </div>
                    </div>
                </div>

                <div class="na-sidebar-action">
                    <button type="button" class="na-btn na-btn-primary na-btn-block" id="na-new-note-btn">
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <span>Upload Note</span>
                    </button>
                </div>

                <nav class="na-sidebar-nav">
                    <div class="na-nav-label">Navigation</div>
                    <ul class="na-nav-list">
                        <li>
                            <a href="#library" class="na-nav-item active" data-view="library">
                                <span class="dashicons dashicons-books"></span>
                                <span class="na-nav-text">Browse library</span>
                            </a>
                        </li>
                        <li>
                            <a href="#my-notes" class="na-nav-item" data-view="my-notes">
                                <span class="dashicons dashicons-category"></span>
                                <span class="na-nav-text">My Notes</span>
                            </a>
                        </li>
                        <li>
                            <a href="#bookmarks" class="na-nav-item" data-view="bookmarks">
                                <span class="na-nav-icon"><svg viewBox="0 0 24 24" class="na-svg-nav" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></span>
                                <span class="na-nav-text">Bookmarks</span>
                            </a>
                        </li>
                    </ul>

                    <?php if ( $can_review || $can_subjects || $can_users ) : ?>
                        <div class="na-nav-label" style="margin-top:20px;">Administration</div>
                        <ul class="na-nav-list">
                            <?php if ( $can_review ) : ?>
                                <li>
                                    <a href="#review-queue" class="na-nav-item" data-view="review-queue">
                                        <span class="dashicons dashicons-shield"></span>
                                        <span class="na-nav-text">Review Queue</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ( $can_subjects ) : ?>
                                <li>
                                    <a href="#subject-requests" class="na-nav-item" data-view="subject-requests">
                                        <span class="dashicons dashicons-clipboard"></span>
                                        <span class="na-nav-text">Subject Requests</span>
                                        <span id="na-pending-requests-badge" class="na-nav-counter-badge" style="display:none;">0</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#subjects" class="na-nav-item" data-view="subjects">
                                        <span class="dashicons dashicons-tag"></span>
                                        <span class="na-nav-text">Subject Management</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ( $can_users ) : ?>
                                <li>
                                    <a href="#users" class="na-nav-item" data-view="users">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <span class="na-nav-text">User Management</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                </nav>

                <div class="na-sidebar-footer">
                    <button type="button" id="na-logout-btn" class="na-logout-btn">
                        <span class="dashicons dashicons-migrate"></span>
                        <span>Sign Out</span>
                    </button>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="na-main-container">
                <!-- Mobile Top Bar -->
                <header class="na-mobile-header">
                    <div class="na-mobile-brand">
                        <img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-mobile-brand-logo" width="140" height="30" style="max-width: 160px; height: 30px; width: auto; object-fit: contain;">
                    </div>
                    <button type="button" class="na-btn na-btn-primary na-btn-sm" id="na-mobile-new-note-btn">
                        <span class="dashicons dashicons-plus"></span> <span>Upload</span>
                    </button>
                </header>

                <!-- Central Library View -->
                <section id="na-view-library" class="na-view active">
                    <div class="na-view-header">
                        <div>
                            <span class="na-eyebrow">Community knowledge base</span>
                            <h2 class="na-view-title">Your study library, in one place.</h2>
                            <p class="na-view-subtitle">Search reliable notes, study guides, and complete course material shared by your community.</p>
                        </div>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-upload"></span> Share notes
                        </button>
                    </div>

                    <!-- Toolbar / Filters -->
                    <div class="na-toolbar">
                        <div class="na-search-box">
                            <span class="dashicons dashicons-search na-search-icon"></span>
                            <input type="search" id="na-search-input" placeholder="Search by subject, topic, or title" class="na-input" aria-label="Search notes">
                        </div>
                        <div class="na-filters-row">
                            <select id="na-subject-filter" class="na-select" aria-label="Filter by subject">
                                <option value="">All Subjects</option>
                            </select>
                            <select id="na-sort-filter" class="na-select" aria-label="Sort notes">
                                <option value="recent">Sort: Most Recent</option>
                                <option value="popular">Sort: Most Liked</option>
                            </select>
                            <button type="button" id="na-apply-filters" class="na-btn na-btn-secondary">
                                <span class="dashicons dashicons-search"></span>
                                <span>Search</span>
                            </button>
                            <button type="button" id="na-reset-filters" class="na-btn na-btn-ghost" style="display:none;">
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="na-results-meta">
                        <span id="na-library-result-count">Explore recently shared notes</span>
                        <span class="na-results-meta-hint">Upload yours to help another student.</span>
                    </div>

                    <!-- Loading State -->
                    <div id="na-library-loading" class="na-state-box na-loading-box" style="display:none;">
                        <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                        <p class="na-state-title">Loading notes...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="na-library-empty" class="na-state-box na-empty-box" style="display:none;">
                        <div class="na-state-icon-wrap"><span class="dashicons dashicons-search na-state-icon"></span></div>
                        <h3 class="na-state-title">No notes found</h3>
                        <p class="na-state-desc">No notes match your filter criteria or the library is empty.</p>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-plus"></span> Upload First Note
                        </button>
                    </div>

                    <!-- Note Grid -->
                    <div id="na-library-results" class="na-cards-grid"></div>

                    <!-- Pagination -->
                    <div id="na-library-pagination" class="na-pagination-container"></div>
                </section>

                <!-- My Notes View -->
                <section id="na-view-my-notes" class="na-view" style="display:none;">
                    <div class="na-view-header">
                        <div>
                            <span class="na-eyebrow">Your contribution</span>
                            <h2 class="na-view-title">My Notes</h2>
                            <p class="na-view-subtitle">Manage the material you have shared with the student community.</p>
                        </div>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-plus"></span> Upload Note
                        </button>
                    </div>

                    <div class="na-results-meta na-results-meta-my">
                        <span id="na-my-notes-result-count">Your published material</span>
                        <span class="na-results-meta-hint">Keep titles and descriptions clear so students can find them.</span>
                    </div>

                    <!-- Loading State -->
                    <div id="na-my-notes-loading" class="na-state-box na-loading-box" style="display:none;">
                        <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                        <p class="na-state-title">Loading your notes...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="na-my-notes-empty" class="na-state-box na-empty-box" style="display:none;">
                        <div class="na-state-icon-wrap"><span class="dashicons dashicons-portfolio na-state-icon"></span></div>
                        <h3 class="na-state-title">You haven't uploaded any notes yet</h3>
                        <p class="na-state-desc">Share your lecture notes, summaries, or study guides with other students.</p>
                        <button type="button" class="na-btn na-btn-primary na-open-create-btn">
                            <span class="dashicons dashicons-plus"></span> Upload Your First Note
                        </button>
                    </div>

                    <!-- Note Grid -->
                    <div id="na-my-notes-results" class="na-cards-grid"></div>

                    <!-- Pagination -->
                    <div id="na-my-notes-pagination" class="na-pagination-container"></div>
                </section>

                <!-- Bookmarks View -->
                <section id="na-view-bookmarks" class="na-view" style="display:none;">
                    <div class="na-view-header">
                        <div>
                            <span class="na-eyebrow">Saved for quick study</span>
                            <h2 class="na-view-title">My Bookmarks</h2>
                            <p class="na-view-subtitle">Access your collection of saved study guides, notes, and full-course materials.</p>
                        </div>
                        <button type="button" class="na-btn na-btn-secondary" id="na-bookmarks-browse-btn">
                            <span class="dashicons dashicons-books"></span> Browse Library
                        </button>
                    </div>

                    <div class="na-results-meta">
                        <span id="na-bookmarks-result-count">Your saved notes</span>
                        <span class="na-results-meta-hint">Click the bookmark icon on any note card to save or remove.</span>
                    </div>

                    <!-- Loading State -->
                    <div id="na-bookmarks-loading" class="na-state-box na-loading-box" style="display:none;">
                        <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                        <p class="na-state-title">Loading bookmarks...</p>
                    </div>

                    <!-- Empty State -->
                    <div id="na-bookmarks-empty" class="na-state-box na-empty-box" style="display:none;">
                        <div class="na-state-icon-wrap">
                            <svg viewBox="0 0 24 24" class="na-svg-bookmark na-state-icon" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <h3 class="na-state-title">No saved notes yet</h3>
                        <p class="na-state-desc">Save useful notes to find them quickly later.</p>
                        <button type="button" class="na-btn na-btn-primary" id="na-empty-browse-btn">
                            <span class="dashicons dashicons-books"></span> Browse Study Library
                        </button>
                    </div>

                    <!-- Bookmarks Grid -->
                    <div id="na-bookmarks-results" class="na-cards-grid"></div>

                    <!-- Pagination -->
                    <div id="na-bookmarks-pagination" class="na-pagination-container"></div>
                </section>

                <!-- Dedicated Note Details View -->
                <section id="na-view-note-details" class="na-view" style="display:none;">
                    <div class="na-view-header na-note-page-header" style="margin-bottom:16px;">
                        <div class="na-note-back-nav">
                            <button type="button" class="na-btn na-btn-ghost na-back-btn" id="na-note-back-btn">
                                <span class="dashicons dashicons-arrow-left-alt2"></span>
                                <span>Back</span>
                            </button>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div id="na-note-details-loading" class="na-state-box na-loading-box" style="display:none;">
                        <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                        <p class="na-state-title">Loading note details...</p>
                    </div>

                    <!-- Not Found / Error State -->
                    <div id="na-note-details-error" class="na-state-box na-empty-box" style="display:none;">
                        <div class="na-state-icon-wrap"><span class="dashicons dashicons-warning na-state-icon"></span></div>
                        <h3 class="na-state-title">Note not found</h3>
                        <p class="na-state-desc">This study note does not exist or has been removed from the library.</p>
                        <button type="button" class="na-btn na-btn-primary" id="na-notfound-browse-btn" style="margin-top:12px;">
                            <span class="dashicons dashicons-books"></span> Back to Study Library
                        </button>
                    </div>

                    <!-- Note Details Page Container -->
                    <div id="na-note-details-container" class="na-note-page"></div>
                </section>

                <?php if ( $can_review ) : ?>
                    <!-- Review Queue View -->
                    <section id="na-view-review-queue" class="na-view" style="display:none;">
                        <div class="na-view-header">
                            <div>
                                <span class="na-eyebrow">Quality & Verification</span>
                                <h2 class="na-view-title">Review Queue</h2>
                                <p class="na-view-subtitle">Review student submissions, verify reliable notes, and moderate community content.</p>
                            </div>
                        </div>

                        <!-- Review Tabs -->
                        <div class="na-review-filter-tabs">
                            <button type="button" class="na-tab-btn active" data-review-status="unverified">
                                <span class="dashicons dashicons-warning"></span>
                                <span>Unverified Notes</span>
                            </button>
                            <button type="button" class="na-tab-btn" data-review-status="all">
                                <span class="dashicons dashicons-list-view"></span>
                                <span>All Notes</span>
                            </button>
                            <button type="button" class="na-tab-btn" data-review-status="verified">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span>Verified Notes</span>
                            </button>
                        </div>

                        <div class="na-results-meta">
                            <span id="na-review-result-count">Notes awaiting review</span>
                            <span class="na-results-meta-hint">Review content before marking verified or removing inappropriate materials.</span>
                        </div>

                        <!-- Loading State -->
                        <div id="na-review-loading" class="na-state-box na-loading-box" style="display:none;">
                            <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                            <p class="na-state-title">Loading review queue...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="na-review-empty" class="na-state-box na-empty-box" style="display:none;">
                            <div class="na-state-icon-wrap"><span class="dashicons dashicons-yes-alt na-state-icon"></span></div>
                            <h3 class="na-state-title">Queue is clear!</h3>
                            <p class="na-state-desc">There are no unverified notes awaiting review right now.</p>
                        </div>

                        <!-- Review Queue Items -->
                        <div id="na-review-results" class="na-review-list"></div>

                        <!-- Pagination -->
                        <div id="na-review-pagination" class="na-pagination-container"></div>
                    </section>
                <?php endif; ?>

                <?php if ( $can_subjects ) : ?>
                    <!-- Subject Requests View (Admin Only) -->
                    <section id="na-view-subject-requests" class="na-view" style="display:none;">
                        <div class="na-view-header">
                            <div>
                                <span class="na-eyebrow">Taxonomy Governance</span>
                                <h2 class="na-view-title">Subject Requests</h2>
                                <p class="na-view-subtitle">Review student and expert requests for new study subjects. Approved subjects are instantly added to the active catalog.</p>
                            </div>
                        </div>

                        <!-- Subject Request Filter Tabs -->
                        <div class="na-review-filter-tabs">
                            <button type="button" class="na-tab-btn active" data-subject-req-status="pending">
                                <span class="dashicons dashicons-clock"></span>
                                <span>Pending Requests</span>
                            </button>
                            <button type="button" class="na-tab-btn" data-subject-req-status="all">
                                <span class="dashicons dashicons-list-view"></span>
                                <span>All Requests</span>
                            </button>
                            <button type="button" class="na-tab-btn" data-subject-req-status="approved">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <span>Approved</span>
                            </button>
                            <button type="button" class="na-tab-btn" data-subject-req-status="rejected">
                                <span class="dashicons dashicons-dismiss"></span>
                                <span>Rejected</span>
                            </button>
                        </div>

                        <div class="na-results-meta">
                            <span id="na-subject-requests-count">Subject requests awaiting review</span>
                            <span class="na-results-meta-hint">Approving a request creates the subject in the active catalog immediately.</span>
                        </div>

                        <!-- Loading State -->
                        <div id="na-subject-requests-loading" class="na-state-box na-loading-box" style="display:none;">
                            <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                            <p class="na-state-title">Loading subject requests...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="na-subject-requests-empty" class="na-state-box na-empty-box" style="display:none;">
                            <div class="na-state-icon-wrap"><span class="dashicons dashicons-yes-alt na-state-icon"></span></div>
                            <h3 class="na-state-title">No requests found</h3>
                            <p class="na-state-desc">There are no subject requests matching the selected filter.</p>
                        </div>

                        <!-- Subject Requests Table / Cards -->
                        <div id="na-subject-requests-results" class="na-subject-requests-list"></div>
                    </section>

                    <!-- Subject Management View -->
                    <section id="na-view-subjects" class="na-view" style="display:none;">
                        <div class="na-view-header">
                            <div>
                                <span class="na-eyebrow">Taxonomy Management</span>
                                <h2 class="na-view-title">Subject Management</h2>
                                <p class="na-view-subtitle">Create and organize standard subjects used by students when uploading notes.</p>
                            </div>
                        </div>

                        <!-- Add Subject Form Card -->
                        <div class="na-admin-card na-subject-create-card">
                            <h3 class="na-admin-card-title">Add New Subject</h3>
                            <p class="na-admin-card-subtitle">Subject names must be unique and will be available to all students in upload and filter dropdowns.</p>
                            
                            <form id="na-add-subject-form" class="na-inline-form">
                                <div class="na-input-wrapper na-col">
                                    <span class="na-input-icon dashicons dashicons-tag"></span>
                                    <input type="text" id="na-new-subject-name" name="name" class="na-input" placeholder="e.g. Computer Science, Neuroscience, Thermodynamics" required>
                                </div>
                                <button type="submit" class="na-btn na-btn-primary" id="na-add-subject-btn">
                                    <span class="na-btn-text">Add Subject</span>
                                    <span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
                                </button>
                            </form>
                            <div id="na-subject-form-msg" class="na-form-message" style="margin-top:12px;"></div>
                        </div>

                        <div class="na-results-meta">
                            <span id="na-subjects-count">Current Subjects</span>
                            <span class="na-results-meta-hint">Alphabetical database list. Subjects with existing notes cannot be deleted.</span>
                        </div>

                        <!-- Loading State -->
                        <div id="na-subjects-loading" class="na-state-box na-loading-box" style="display:none;">
                            <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                            <p class="na-state-title">Loading subjects...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="na-subjects-empty" class="na-state-box na-empty-box" style="display:none;">
                            <div class="na-state-icon-wrap"><span class="dashicons dashicons-tag na-state-icon"></span></div>
                            <h3 class="na-state-title">No subjects defined yet</h3>
                            <p class="na-state-desc">Use the form above to add the first study subject.</p>
                        </div>

                        <!-- Subjects Grid / Table -->
                        <div id="na-subjects-results" class="na-subjects-grid"></div>
                    </section>
                <?php endif; ?>

                <?php if ( $can_users ) : ?>
                    <!-- User Management View (Admin Only) -->
                    <section id="na-view-users" class="na-view" style="display:none;">
                        <div class="na-view-header">
                            <div>
                                <span class="na-eyebrow">Access Control & Staff</span>
                                <h2 class="na-view-title">User Management</h2>
                                <p class="na-view-subtitle">Search registered community members and assign Notes Adda roles (Student, Expert, Notes Adda Admin).</p>
                            </div>
                        </div>

                        <!-- Search Box -->
                        <div class="na-toolbar">
                            <div class="na-search-box">
                                <span class="dashicons dashicons-search na-search-icon"></span>
                                <input type="search" id="na-user-search-input" placeholder="Search users by username, display name, or email" class="na-input" aria-label="Search users">
                            </div>
                            <button type="button" id="na-search-users-btn" class="na-btn na-btn-secondary">
                                <span class="dashicons dashicons-search"></span>
                                <span>Search</span>
                            </button>
                        </div>

                        <div class="na-results-meta">
                            <span id="na-users-count">Community Members</span>
                            <span class="na-results-meta-hint">Roles control review powers, subject creation, and user management.</span>
                        </div>

                        <!-- Loading State -->
                        <div id="na-users-loading" class="na-state-box na-loading-box" style="display:none;">
                            <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                            <p class="na-state-title">Loading users...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="na-users-empty" class="na-state-box na-empty-box" style="display:none;">
                            <div class="na-state-icon-wrap"><span class="dashicons dashicons-admin-users na-state-icon"></span></div>
                            <h3 class="na-state-title">No users found</h3>
                            <p class="na-state-desc">No accounts matched your search criteria.</p>
                        </div>

                        <!-- Users List / Stacked Cards on Mobile -->
                        <div id="na-users-results" class="na-users-list"></div>

                        <!-- Pagination -->
                        <div id="na-users-pagination" class="na-pagination-container"></div>
                    </section>
                <?php endif; ?>

            </main>
        </div>

        <!-- Note Details Modal -->
        <div id="na-note-details-modal" class="na-modal-overlay">
            <div class="na-modal-dialog na-modal-lg">
                <div class="na-modal-header">
                    <h3 class="na-modal-title">Note Preview</h3>
                    <button type="button" class="na-modal-close-btn" data-modal="details" aria-label="Close modal">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div id="na-note-details-body" class="na-modal-body">
                    <!-- Loaded dynamically via AJAX -->
                </div>
            </div>
        </div>

        <!-- Create / Edit Note Modal -->
        <div id="na-note-form-modal" class="na-modal-overlay">
            <div class="na-modal-dialog na-modal-lg">
                <div class="na-modal-header">
                    <h3 id="na-note-form-title" class="na-modal-title">Upload New Note</h3>
                    <button type="button" class="na-modal-close-btn" data-modal="form" aria-label="Close modal">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="na-modal-body">
                    <form id="na-note-form" class="na-form">
                        <input type="hidden" id="na-note-id" name="note_id" value="">

                        <div class="na-form-group">
                            <label for="na-note-title">Title <span class="na-required">*</span></label>
                            <input type="text" id="na-note-title" name="title" class="na-input" placeholder="e.g. Operating Systems: Process Scheduling" required>
                        </div>

                        <div class="na-form-row">
                            <div class="na-form-group na-col">
                                <div class="na-label-with-action">
                                    <label for="na-note-subject">Subject <span class="na-required">*</span></label>
                                    <button type="button" class="na-btn-link na-request-subject-trigger" id="na-request-subject-link">
                                        <span class="dashicons dashicons-plus"></span> Request a new subject
                                    </button>
                                </div>
                                <select id="na-note-subject" name="subject" class="na-select" required>
                                    <option value="">Select a Subject *</option>
                                </select>
                                <div id="na-no-subjects-warning" class="na-form-warning" style="display:none; margin-top:5px;">
                                    No subjects are available yet. <button type="button" class="na-btn-link na-request-subject-trigger">Request a new subject</button> to get started.
                                </div>
                            </div>
                            <div class="na-form-group na-col">
                                <label for="na-note-chapter">Chapter / Unit (Optional)</label>
                                <input type="text" id="na-note-chapter" name="chapter" class="na-input" placeholder="e.g. Chapter 4">
                            </div>
                        </div>

                        <div class="na-form-group">
                            <label for="na-note-description">Description / Overview</label>
                            <textarea id="na-note-description" name="description" class="na-input na-textarea" rows="3" placeholder="Brief summary of what this note covers..."></textarea>
                        </div>

                        <!-- PDF Upload Area -->
                        <div class="na-form-group">
                            <label>PDF Document <span class="na-required">*</span> <span class="na-label-hint">(Max 50 MB)</span></label>
                            <div class="na-file-dropzone" id="na-file-dropzone">
                                <input type="file" id="na-note-file" accept="application/pdf" class="na-file-input">
                                <input type="hidden" id="na-note-file-url" name="file_url" value="">
                                <input type="hidden" id="na-note-file-id" name="file_id" value="">
                                
                                <div class="na-dropzone-content">
                                    <div class="na-dropzone-icon"><span class="dashicons dashicons-pdf"></span></div>
                                    <div class="na-dropzone-text">
                                        <span class="na-dropzone-primary">Click to select PDF or drag & drop</span>
                                        <span class="na-dropzone-secondary" id="na-file-name-display">Only PDF files up to 50 MB supported</span>
                                    </div>
                                </div>
                            </div>
                            <div id="na-file-upload-status" class="na-file-status"></div>
                        </div>

                        <div class="na-form-group na-checkbox-wrapper">
                            <label class="na-checkbox-label">
                                <input type="checkbox" id="na-note-is-whole" name="is_whole_notes">
                                <span class="na-checkbox-custom"></span>
                                <span class="na-checkbox-text">This note covers the complete course / whole syllabus</span>
                            </label>
                        </div>

                        <div class="na-form-group">
                            <label for="na-note-tags">Tags <span class="na-label-hint">(Comma separated)</span></label>
                            <input type="text" id="na-note-tags" name="tags" class="na-input" placeholder="e.g. Midterms, Sem 4, Cheatsheet">
                        </div>

                        <div id="na-form-message" class="na-form-message"></div>

                        <div class="na-modal-footer">
                            <button type="button" class="na-btn na-btn-ghost na-modal-close-btn" data-modal="form">Cancel</button>
                            <button type="submit" class="na-btn na-btn-primary" id="na-save-note-btn">
                                <span class="na-btn-text">Publish Note</span>
                                <span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Subject Request Modal (Student / Expert / Admin) -->
        <div id="na-subject-request-modal" class="na-modal-overlay">
            <div class="na-modal-dialog">
                <div class="na-modal-header">
                    <h3 class="na-modal-title">Subject Request</h3>
                    <button type="button" class="na-modal-close-btn" data-modal="subject-request" aria-label="Close modal">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="na-modal-body">
                    <!-- Modal Subtabs -->
                    <div class="na-modal-tabs">
                        <button type="button" class="na-modal-tab-btn active" data-subtab="new-request">
                            <span class="dashicons dashicons-plus-alt"></span> Request New Subject
                        </button>
                        <button type="button" class="na-modal-tab-btn" data-subtab="my-requests" id="na-my-requests-tab-btn">
                            <span class="dashicons dashicons-list-view"></span> My Requests <span id="na-my-requests-badge" class="na-subtab-badge" style="display:none;">0</span>
                        </button>
                    </div>

                    <!-- Tab 1: New Request Form -->
                    <div id="na-subtab-new-request" class="na-modal-subtab-pane active">
                        <form id="na-subject-request-form" class="na-form" style="margin-top:16px;">
                            <div class="na-form-group">
                                <label for="na-req-subject-name">Requested Subject Name <span class="na-required">*</span></label>
                                <input type="text" id="na-req-subject-name" name="name" class="na-input" placeholder="e.g. Computer Graphics, Biochemistry" required autocomplete="off">
                            </div>

                            <div class="na-form-group">
                                <label for="na-req-subject-reason">Reason or Note <span class="na-label-hint">(Optional)</span></label>
                                <textarea id="na-req-subject-reason" name="reason" class="na-input na-textarea" rows="2" placeholder="Course title, syllabus code, or why this subject is needed..."></textarea>
                            </div>

                            <div id="na-subject-request-msg" class="na-form-message"></div>

                            <div class="na-modal-footer" style="padding:0; margin-top:20px;">
                                <button type="button" class="na-btn na-btn-ghost na-modal-close-btn" data-modal="subject-request">Cancel</button>
                                <button type="submit" class="na-btn na-btn-primary" id="na-submit-subject-req-btn">
                                    <span class="na-btn-text">Submit Request</span>
                                    <span class="na-btn-spinner dashicons dashicons-update na-spin" style="display:none;"></span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: My Requests List -->
                    <div id="na-subtab-my-requests" class="na-modal-subtab-pane" style="display:none; margin-top:16px;">
                        <div id="na-my-requests-loading" class="na-state-box na-loading-box" style="padding:20px; display:none;">
                            <span class="dashicons dashicons-update na-spin na-state-icon"></span>
                            <p class="na-state-title">Loading your requests...</p>
                        </div>
                        <div id="na-my-requests-empty" class="na-state-box na-empty-box" style="padding:24px; display:none;">
                            <div class="na-state-icon-wrap"><span class="dashicons dashicons-tag na-state-icon"></span></div>
                            <h3 class="na-state-title" style="font-size:15px;">No requests submitted yet</h3>
                            <p class="na-state-desc" style="font-size:13px;">When you request new study subjects, track their approval status here.</p>
                        </div>
                        <div id="na-my-requests-list" class="na-my-requests-list"></div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
