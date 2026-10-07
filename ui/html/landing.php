<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_logged_in = is_user_logged_in();
$app_url      = class_exists( 'Notes_Adda_Frontend' ) ? Notes_Adda_Frontend::get_app_url() : home_url( '/notes-adda/' );
$login_url    = class_exists( 'Notes_Adda_Frontend' ) ? Notes_Adda_Frontend::get_landing_url( array( 'auth' => 'login' ) ) : home_url( '/?auth=login' );
$register_url = class_exists( 'Notes_Adda_Frontend' ) ? Notes_Adda_Frontend::get_landing_url( array( 'auth' => 'register' ) ) : home_url( '/?auth=register' );
$developed_by = get_option( 'notes_adda_developed_by', 'Notes Adda Team' );
?>

<div class="na-landing-wrap">

	<!-- Navigation Header -->
	<header class="na-landing-header">
		<div class="na-landing-container">
			<nav class="na-landing-nav">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="na-landing-brand-link" title="Notes Adda">
					<img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-landing-brand-img" width="200" height="40" style="max-height: 40px; width: auto; max-width: 220px; object-fit: contain;">
				</a>

				<ul class="na-landing-menu">
					<li><a href="#features" class="na-landing-menu-link">Features</a></li>
					<li><a href="#verification" class="na-landing-menu-link">Verification & Trust</a></li>
					<li><a href="#how-it-works" class="na-landing-menu-link">How It Works</a></li>
					<li><a href="#about" class="na-landing-menu-link">About</a></li>
				</ul>

				<div class="na-landing-nav-actions">
					<?php if ( $is_logged_in ) : ?>
						<a href="<?php echo esc_url( $app_url ); ?>" class="na-landing-btn na-landing-btn-primary">
							<span class="dashicons dashicons-welcome-learn-more"></span>
							<span>Open Library</span>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $login_url ); ?>" class="na-landing-btn na-landing-btn-ghost na-auth-trigger" data-auth-mode="login">
							<span>Sign In</span>
						</a>
						<a href="<?php echo esc_url( $register_url ); ?>" class="na-landing-btn na-landing-btn-primary na-auth-trigger" data-auth-mode="register">
							<span class="dashicons dashicons-id-alt"></span>
							<span>Create Account</span>
						</a>
					<?php endif; ?>
				</div>
			</nav>
		</div>
	</header>

	<!-- Hero Section -->
	<section class="na-landing-hero">
		<div class="na-landing-container">
			<div class="na-landing-hero-grid">
				<div class="na-landing-hero-content">
					<div class="na-landing-pill">
						<span class="dashicons dashicons-awards"></span>
						<span>Collaborative Student Notes Platform</span>
					</div>

					<h1 class="na-landing-hero-title">
						Share Knowledge. <br>Ace Exams. <span>Learn Together.</span>
					</h1>

					<p class="na-landing-hero-subtitle">
						Notes Adda connects students and educators to discover chapter-wise study materials, whole-subject notes, and expert-reviewed guides. Upload PDFs, organize personal bookmarks, and prepare with total confidence.
					</p>

					<div class="na-landing-hero-actions">
						<?php if ( $is_logged_in ) : ?>
							<a href="<?php echo esc_url( $app_url ); ?>" class="na-landing-btn na-landing-btn-primary na-landing-btn-lg">
								<span class="dashicons dashicons-portfolio"></span>
								<span>Open Your Notes Library</span>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url( $register_url ); ?>" class="na-landing-btn na-landing-btn-primary na-landing-btn-lg na-auth-trigger" data-auth-mode="register">
								<span class="dashicons dashicons-id-alt"></span>
								<span>Get Started Free</span>
							</a>
							<a href="<?php echo esc_url( $login_url ); ?>" class="na-landing-btn na-landing-btn-secondary na-landing-btn-lg na-auth-trigger" data-auth-mode="login">
								<span class="dashicons dashicons-admin-users"></span>
								<span>Sign In</span>
							</a>
						<?php endif; ?>
					</div>

					<div class="na-landing-hero-trust-bar">
						<div class="na-landing-trust-item">
							<span class="dashicons dashicons-yes-alt"></span>
							<span>100% Free & Open</span>
						</div>
						<div class="na-landing-trust-item">
							<span class="dashicons dashicons-shield-alt"></span>
							<span>Expert Verified Badges</span>
						</div>
						<div class="na-landing-trust-item">
							<span class="dashicons dashicons-pdf"></span>
							<span>Instant PDF Previews</span>
						</div>
						<div class="na-landing-trust-item">
							<span class="dashicons dashicons-cloud"></span>
							<span>Cloud-Hosted on AWS</span>
						</div>
					</div>
				</div>

				<!-- Hero Visual Card Mockup -->
				<div class="na-landing-hero-preview">
					<div class="na-landing-mockup-card">
						<div class="na-landing-mockup-top">
							<span class="na-landing-badge-subject">
								<span class="dashicons dashicons-category"></span>
								Data Structures & Algorithms
							</span>
							<span class="na-landing-badge-verified">
								<span class="dashicons dashicons-yes-alt"></span>
								Verified
							</span>
						</div>

						<h3 class="na-landing-mockup-title">Comprehensive Dynamic Programming & Graph Traversal</h3>
						<p class="na-landing-mockup-desc">Complete lecture breakdown with step-by-step illustrations, time complexities, recurrence relations, and solved previous year exam problems.</p>

						<div class="na-landing-mockup-tags">
							<span class="na-landing-tag">Whole Notes</span>
							<span class="na-landing-tag">DP & Graphs</span>
							<span class="na-landing-tag">Semester 4</span>
							<span class="na-landing-tag">Exam Prep</span>
						</div>

						<div class="na-landing-mockup-footer">
							<div class="na-landing-mockup-author">
								<div class="na-landing-mockup-avatar">CS</div>
								<div class="na-landing-mockup-author-info">
									<span class="na-landing-mockup-author-name">Alex M.</span>
									<span class="na-landing-mockup-author-role">Verified Contributor</span>
								</div>
							</div>
							<div class="na-landing-mockup-stats">
								<span class="na-landing-stat-item liked">
									<span class="dashicons dashicons-heart"></span>
									<span>142</span>
								</span>
								<span class="na-landing-stat-item">
									<svg viewBox="0 0 24 24" class="na-svg-stat" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
									<span>58</span>
								</span>
							</div>
						</div>
					</div>

					<div class="na-landing-floating-card">
						<div class="na-landing-floating-icon">
							<span class="dashicons dashicons-yes"></span>
						</div>
						<div>Reviewed by Subject Experts</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Features Section -->
	<section id="features" class="na-landing-section">
		<div class="na-landing-container">
			<div class="na-landing-section-header">
				<div class="na-landing-pill">Platform Highlights</div>
				<h2 class="na-landing-section-title">Built for Students, Curated for Quality</h2>
				<p class="na-landing-section-subtitle">Everything you need to search, review, share, and organize academic notes in one cohesive workspace.</p>
			</div>

			<div class="na-landing-features-grid">
				<!-- Feature 1 -->
				<div class="na-landing-feature-card">
					<div class="na-landing-feature-icon-wrap">
						<span class="dashicons dashicons-search"></span>
					</div>
					<h3 class="na-landing-feature-title">Discover & Search Notes</h3>
					<p class="na-landing-feature-desc">Filter study materials effortlessly by subject, chapter, or complete syllabus. Find relevant notes with high-speed keyword search and tags.</p>
				</div>

				<!-- Feature 2 -->
				<div class="na-landing-feature-card">
					<div class="na-landing-feature-icon-wrap">
						<span class="dashicons dashicons-upload"></span>
					</div>
					<h3 class="na-landing-feature-title">Upload & Share PDFs</h3>
					<p class="na-landing-feature-desc">Contribute handwritten summaries, lecture slides, or complete textbook notes. Upload PDF files with automatic indexing and metadata tags.</p>
				</div>

				<!-- Feature 3 -->
				<div class="na-landing-feature-card">
					<div class="na-landing-feature-icon-wrap">
						<svg viewBox="0 0 24 24" class="na-svg-feature" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
					</div>
					<h3 class="na-landing-feature-title">Personal Bookmarks</h3>
					<p class="na-landing-feature-desc">Save essential notes directly to your personal study library with one click. Access your bookmarked study guides quickly before exams.</p>
				</div>

				<!-- Feature 4 -->
				<div class="na-landing-feature-card">
					<div class="na-landing-feature-icon-wrap">
						<span class="dashicons dashicons-awards"></span>
					</div>
					<h3 class="na-landing-feature-title">Expert Verification</h3>
					<p class="na-landing-feature-desc">Notes undergo structured peer and expert evaluation. Verified badges distinguish high-accuracy materials so you can revise with certainty.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- Trust & Verification Section -->
	<section id="verification" class="na-landing-section na-landing-section-alt">
		<div class="na-landing-container">
			<div class="na-landing-section-header">
				<div class="na-landing-pill">Academic Integrity & Trust</div>
				<h2 class="na-landing-section-title">Verified vs. Community Notes</h2>
				<p class="na-landing-section-subtitle">We maintain a two-tier review system ensuring both open peer collaboration and faculty-grade verification.</p>
			</div>

			<div class="na-landing-trust-grid">
				<!-- Verified Notes -->
				<div class="na-landing-trust-card verified-focus">
					<div class="na-landing-trust-card-header">
						<span class="na-landing-trust-badge verified">
							<span class="dashicons dashicons-yes-alt"></span>
							Verified Notes
						</span>
					</div>
					<h3 class="na-landing-trust-title">Expert Checked & Verified</h3>
					<p class="na-landing-trust-desc">These notes have undergone meticulous review by designated faculty or verified expert contributors for syllabus coverage, clarity, and factual correctness.</p>
					<ul class="na-landing-trust-points">
						<li class="na-landing-trust-point verified-point">
							<span class="dashicons dashicons-yes"></span>
							<span>Accurate mathematical derivations, algorithms, and diagrams</span>
						</li>
						<li class="na-landing-trust-point verified-point">
							<span class="dashicons dashicons-yes"></span>
							<span>Complete syllabus mapping with verified chapter milestones</span>
						</li>
						<li class="na-landing-trust-point verified-point">
							<span class="dashicons dashicons-yes"></span>
							<span>Displays prominent green verified badge for instant trust</span>
						</li>
					</ul>
				</div>

				<!-- Community Notes -->
				<div class="na-landing-trust-card">
					<div class="na-landing-trust-card-header">
						<span class="na-landing-trust-badge community">
							<span class="dashicons dashicons-groups"></span>
							Community Notes
						</span>
					</div>
					<h3 class="na-landing-trust-title">Peer-Shared & Crowdsourced</h3>
					<p class="na-landing-trust-desc">Newly contributed study guides shared directly by fellow students across universities. Available immediately while undergoing community review and feedback.</p>
					<ul class="na-landing-trust-points">
						<li class="na-landing-trust-point community-point">
							<span class="dashicons dashicons-yes"></span>
							<span>Rapidly available for emerging topics, electives, and new courses</span>
						</li>
						<li class="na-landing-trust-point community-point">
							<span class="dashicons dashicons-yes"></span>
							<span>Ranked by student likes, bookmarks, and engagement signals</span>
						</li>
						<li class="na-landing-trust-point community-point">
							<span class="dashicons dashicons-yes"></span>
							<span>Eligible for automatic promotion to Verified upon review</span>
						</li>
					</ul>
				</div>
			</div>

			<div class="na-landing-trust-banner">
				<div class="na-landing-trust-banner-icon">
					<span class="dashicons dashicons-shield"></span>
				</div>
				<div class="na-landing-trust-banner-text">
					<h4>Continuous Quality Moderation</h4>
					<p>Every note on Notes Adda includes reporting workflows and subject administration tools to keep content clean, relevant, and free of plagiarism.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- How It Works Section -->
	<section id="how-it-works" class="na-landing-section">
		<div class="na-landing-container">
			<div class="na-landing-section-header">
				<div class="na-landing-pill">Simple Workflow</div>
				<h2 class="na-landing-section-title">How Notes Adda Works</h2>
				<p class="na-landing-section-subtitle">Get started in seconds and streamline your academic journey.</p>
			</div>

			<div class="na-landing-steps-grid">
				<!-- Step 1 -->
				<div class="na-landing-step-card">
					<div class="na-landing-step-num">1</div>
					<h3 class="na-landing-step-title">Join or Browse</h3>
					<p class="na-landing-step-desc">Create your student account in seconds or browse through publicly available subject repositories.</p>
				</div>

				<!-- Step 2 -->
				<div class="na-landing-step-card">
					<div class="na-landing-step-num">2</div>
					<h3 class="na-landing-step-title">Search & Preview</h3>
					<p class="na-landing-step-desc">Filter by course, subject, or chapter. Open in-depth note details and preview attached PDFs seamlessly.</p>
				</div>

				<!-- Step 3 -->
				<div class="na-landing-step-card">
					<div class="na-landing-step-num">3</div>
					<h3 class="na-landing-step-title">Share & Request</h3>
					<p class="na-landing-step-desc">Upload your own study guides to earn community reputation, or request new academic subjects with one click.</p>
				</div>

				<!-- Step 4 -->
				<div class="na-landing-step-card">
					<div class="na-landing-step-num">4</div>
					<h3 class="na-landing-step-title">Bookmark & Excel</h3>
					<p class="na-landing-step-desc">Organize bookmarks in your private study dashboard for quick exam revision anytime, anywhere.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- About Section -->
	<section id="about" class="na-landing-section na-landing-section-alt">
		<div class="na-landing-container">
			<div class="na-landing-about-grid">
				<div class="na-landing-about-content">
					<div class="na-landing-pill">About Notes Adda</div>
					<h3>Democratizing Higher Education Study Resources</h3>
					<p>
						Notes Adda is a student-first academic knowledge sharing platform designed to eliminate the friction of finding high-quality study materials. Whether you need last-minute revision summaries or comprehensive whole-course notes, Notes Adda brings together students, top performers, and faculty mentors.
					</p>
					<p>
						Built with a calm, distraction-free reading experience, our mission is to empower learners everywhere with accessible, peer-reviewed educational materials.
					</p>

					<div class="na-landing-about-highlights">
						<div class="na-landing-about-item">
							<div class="na-landing-about-icon">
								<span class="dashicons dashicons-heart"></span>
							</div>
							<div class="na-landing-about-item-text">
								<strong>Student-Driven Community</strong>
								<span>Shared by students for students across multiple disciplines and semesters.</span>
							</div>
						</div>
						<div class="na-landing-about-item">
							<div class="na-landing-about-icon">
								<span class="dashicons dashicons-cloud"></span>
							</div>
							<div class="na-landing-about-item-text">
								<strong>Enterprise Cloud Infrastructure</strong>
								<span>Hosted on Amazon Web Services (AWS) ensuring high availability, speed, and resilient file storage.</span>
							</div>
						</div>
					</div>
				</div>

				<div class="na-landing-about-sidebar-card">
					<div class="na-landing-about-meta-list">
						<div class="na-landing-about-meta-item">
							<span class="na-landing-about-meta-label">Platform</span>
							<span class="na-landing-about-meta-val">Notes Adda</span>
						</div>
						<div class="na-landing-about-meta-item">
							<span class="na-landing-about-meta-label">Infrastructure</span>
							<span class="na-landing-about-meta-val">
								<span class="na-landing-aws-badge">
									<span class="dashicons dashicons-cloud"></span>
									Amazon Web Services (AWS)
								</span>
							</span>
						</div>
						<div class="na-landing-about-meta-item">
							<span class="na-landing-about-meta-label">Access Model</span>
							<span class="na-landing-about-meta-val">Free & Open Student Sharing</span>
						</div>
						<div class="na-landing-about-meta-item">
							<span class="na-landing-about-meta-label">Developed By</span>
							<span class="na-landing-about-meta-val"><?php echo esc_html( $developed_by ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Final CTA Section -->
	<section class="na-landing-cta-section">
		<div class="na-landing-container">
			<div class="na-landing-cta-box">
				<h2 class="na-landing-cta-title">Ready to Elevate Your Exam Preparation?</h2>
				<p class="na-landing-cta-subtitle">Join fellow students sharing, reviewing, and discovering top-tier study notes. Start exploring the library today.</p>
				<div class="na-landing-cta-actions">
					<?php if ( $is_logged_in ) : ?>
						<a href="<?php echo esc_url( $app_url ); ?>" class="na-landing-btn na-landing-btn-light na-landing-btn-lg">
							<span class="dashicons dashicons-portfolio"></span>
							<span>Open Study Library</span>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $register_url ); ?>" class="na-landing-btn na-landing-btn-light na-landing-btn-lg na-auth-trigger" data-auth-mode="register">
							<span class="dashicons dashicons-id-alt"></span>
							<span>Create Free Account</span>
						</a>
						<a href="<?php echo esc_url( $login_url ); ?>" class="na-landing-btn na-landing-btn-outline-light na-landing-btn-lg na-auth-trigger" data-auth-mode="login">
							<span class="dashicons dashicons-admin-users"></span>
							<span>Sign In</span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="na-landing-footer">
		<div class="na-landing-container">
			<div class="na-landing-footer-top">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="na-landing-brand-link" title="Notes Adda">
					<img src="<?php echo esc_url( NOTES_ADDA_URL . 'ui/assets/images/logoname.png' ); ?>" alt="Notes Adda" class="na-landing-brand-img" width="200" height="40" style="max-height: 40px; width: auto; max-width: 220px; object-fit: contain;">
				</a>

				<ul class="na-landing-footer-links">
					<li><a href="#features" class="na-landing-footer-link">Features</a></li>
					<li><a href="#verification" class="na-landing-footer-link">Verification</a></li>
					<li><a href="#how-it-works" class="na-landing-footer-link">How It Works</a></li>
					<li><a href="#about" class="na-landing-footer-link">About</a></li>
					<li><a href="<?php echo esc_url( $app_url ); ?>" class="na-landing-footer-link">Study Library</a></li>
				</ul>
			</div>

			<div class="na-landing-footer-bottom">
				<p class="na-landing-footer-copy">
					&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Notes Adda. All rights reserved.
				</p>
				<div class="na-landing-footer-aws">
					<span class="dashicons dashicons-cloud"></span>
					<span>Hosted on AWS Cloud</span>
				</div>
			</div>
		</div>
	</footer>

	<?php if ( ! $is_logged_in ) : ?>
		<!-- Auth Modal Overlay -->
		<div id="na-landing-auth-modal" class="na-modal-overlay" style="display:none;" aria-hidden="true">
			<div class="na-modal-backdrop" id="na-landing-auth-backdrop"></div>
			<div class="na-auth-container na-landing-auth-container">
				<div class="na-auth-card">
					<button type="button" class="na-auth-close-btn" id="na-landing-auth-close" aria-label="Close modal">&times;</button>
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
		</div>
	<?php endif; ?>

</div>
