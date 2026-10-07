<?php
/**
 * Notes Adda - Full Acceptance Test Suite
 * Tests scenarios 1-7 from specifications.
 */

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once '/var/www/html/wordpress/wp-load.php';

echo "=== NOTES ADDA ACCEPTANCE TEST SUITE ===\n\n";

global $wpdb;

// Ensure users exist
// Admin: ID 1 (notes_adda_dev)
// Expert: User 49 (Manas)
// Student: User 55 or create a student
$admin_user = get_user_by('id', 1);
$expert_user = get_user_by('id', 49);
if (!$expert_user) {
    $expert_user = get_user_by('login', 'Manas');
}
// Ensure expert capability
$expert_user->add_cap('notes_adda_expert');
$expert_user->add_cap('notes_adda_review_notes');

// Get or create a student user
$student_user = get_user_by('login', 'test_student');
if (!$student_user) {
    $student_id = wp_create_user('test_student', 'StudentPass123!', 'student@example.com');
    $student_user = get_user_by('id', $student_id);
    $student_user->set_role('subscriber');
    $student_user->add_cap('notes_adda_student');
}

// Get or create another student user
$other_student = get_user_by('login', 'other_student');
if (!$other_student) {
    $other_id = wp_create_user('other_student', 'OtherPass123!', 'other@example.com');
    $other_student = get_user_by('id', $other_id);
    $other_student->set_role('subscriber');
    $other_student->add_cap('notes_adda_student');
}

echo "Active Test Users:\n";
echo " - Admin: #{$admin_user->ID} ({$admin_user->user_login})\n";
echo " - Expert: #{$expert_user->ID} ({$expert_user->user_login})\n";
echo " - Student: #{$student_user->ID} ({$student_user->user_login})\n";
echo " - Other Student: #{$other_student->ID} ({$other_student->user_login})\n\n";

$pass_count = 0;
$fail_count = 0;

function assert_test($condition, $name) {
    global $pass_count, $fail_count;
    if ($condition) {
        echo " [PASS] $name\n";
        $pass_count++;
    } else {
        echo " [FAIL] $name\n";
        $fail_count++;
    }
}

// ---------------------------------------------------------
// TEST 1: Student uploads a note
// ---------------------------------------------------------
echo "\n--- TEST 1: Student Uploads Note (3-State & Visibility) ---\n";
wp_set_current_user($student_user->ID);

$note1_obj = Notes_Adda_Notes::create($student_user->ID, array(
    'title' => 'Biology 101 Notes by Student',
    'subject' => 'Biology',
    'description' => 'Comprehensive cell structure guide',
    'chapter' => 'Cell Structure',
    'file_id' => 0,
    'file_url' => 'http://example.com/bio101.pdf',
    'is_whole_notes' => 0
));

assert_test(!is_wp_error($note1_obj) && $note1_obj->id > 0, "Note created with ID: " . ($note1_obj->id ?? 'error'));
$note1_id = $note1_obj->id;

$note1_db = Notes_Adda_Notes::get_by_id($note1_id);
assert_test($note1_db->review_status === 'pending', "Newly uploaded note status is 'pending'");

// 1.1 Appears in owner's My Notes
$my_notes = Notes_Adda_Note_Query::get_notes(array(
    'owner_id' => $student_user->ID,
    'per_page' => 10
));
$found_in_my = false;
foreach ($my_notes['items'] as $item) {
    if ((int)$item->id === (int)$note1_id) $found_in_my = true;
}
assert_test($found_in_my, "Note appears in student's My Notes query");

// 1.2 Appears in reviewer queue
wp_set_current_user($expert_user->ID);
$review_queue = Notes_Adda_Note_Query::get_notes(array(
    'is_review_queue' => true,
    'review_status' => 'pending',
    'search' => 'Biology 101 Notes'
));
$found_in_queue = false;
foreach ($review_queue['items'] as $item) {
    if ((int)$item->id === (int)$note1_id) $found_in_queue = true;
}
assert_test($found_in_queue, "Note appears in reviewer's Pending Review Queue");

// 1.3 Absent from public library for other users
wp_set_current_user($other_student->ID);
$public_lib = Notes_Adda_Note_Query::get_notes(array(
    'search' => 'Biology 101 Notes'
));
$found_in_public = false;
foreach ($public_lib['items'] as $item) {
    if ((int)$item->id === (int)$note1_id) $found_in_public = true;
}
assert_test(!$found_in_public, "Pending note is strictly absent from public library query for other users");

// 1.4 Direct note access forbidden for other users
$other_details = Notes_Adda_Notes::get_details($note1_id, $other_student->ID);
assert_test(is_wp_error($other_details), "Direct note access to pending note by non-owner non-reviewer returns WP_Error (HTTP 403 equivalent)");

// 1.5 Pending note cannot be liked
$like_result = Notes_Adda_Likes::add($note1_id, $other_student->ID);
assert_test(is_wp_error($like_result), "Liking a pending note is rejected server-side");

// 1.6 Pending note cannot be bookmarked
$bm_result = Notes_Adda_Bookmarks::add($note1_id, $other_student->ID);
assert_test(is_wp_error($bm_result), "Bookmarking a pending note is rejected server-side");

// ---------------------------------------------------------
// TEST 2: Expert tries to review their own note
// ---------------------------------------------------------
echo "\n--- TEST 2: Self-Review Strictly Forbidden ---\n";
wp_set_current_user($expert_user->ID);

$expert_note_obj = Notes_Adda_Notes::create($expert_user->ID, array(
    'title' => 'Advanced Physics by Expert',
    'subject' => 'Physics',
    'description' => 'Quantum mechanics basics',
    'file_url' => 'http://example.com/phys.pdf'
));
$expert_note_id = $expert_note_obj->id;

$self_review = Notes_Adda_Notes::review($expert_note_id, $expert_user->ID, 'verified', 'Self-approving my great work');
assert_test(is_wp_error($self_review), "Expert attempting to review own note fails with self_review_forbidden");

$expert_note_details = Notes_Adda_Notes::get_details($expert_note_id, $expert_user->ID);
assert_test($expert_note_details['can_review'] === false, "can_review is false for owner even if user has reviewer capability");

// ---------------------------------------------------------
// TEST 3: Reviewer verifies with a reason
// ---------------------------------------------------------
echo "\n--- TEST 3: Reviewer Verifies with Reason ---\n";
wp_set_current_user($expert_user->ID);

// Reason cannot be empty
$empty_reason = Notes_Adda_Notes::review($note1_id, $expert_user->ID, 'verified', '   ');
assert_test(is_wp_error($empty_reason), "Approval without reason is rejected");

// Valid verification
$pending_count_before = Notes_Adda_Notes::get_pending_review_count();
$verify_res = Notes_Adda_Notes::review($note1_id, $expert_user->ID, 'verified', 'Clear diagrams and accurate definitions.');
assert_test(!is_wp_error($verify_res), "Reviewer verifies note with valid reason");

$pending_count_after = Notes_Adda_Notes::get_pending_review_count();
assert_test($pending_count_after < $pending_count_before, "Pending review queue count decreased");

$verified_note = Notes_Adda_Notes::get_by_id($note1_id);
assert_test($verified_note->review_status === 'verified', "Note status changed to 'verified'");
assert_test((int)$verified_note->reviewed_by === $expert_user->ID, "Reviewer ID preserved in database");
assert_test(!empty($verified_note->reviewed_at), "Reviewed timestamp recorded");
assert_test($verified_note->review_note === 'Clear diagrams and accurate definitions.', "Review reason preserved");

// Note is now visible in public library
wp_set_current_user($other_student->ID);
$pub_check = Notes_Adda_Note_Query::get_notes(array('search' => 'Biology 101 Notes'));
$found_now = false;
foreach ($pub_check['items'] as $item) {
    if ((int)$item->id === (int)$note1_id) $found_now = true;
}
assert_test($found_now, "Verified note is now visible in main library to all users");

// Owner received persistent notification
$owner_notifs = Notes_Adda_Notifications::get_for_user($student_user->ID);
$found_notif = false;
foreach ($owner_notifs['items'] as $n) {
    if ((int)$n['note_id'] === (int)$note1_id && $n['type'] === 'note_verified') {
        $found_notif = true;
        assert_test(strpos($n['message'], 'Clear diagrams') !== false, "Persistent notification includes reviewer reason");
    }
}
assert_test($found_notif, "Owner received persistent in-app notification on verification");

// ---------------------------------------------------------
// TEST 4: Reviewer rejects with a reason & Edit-and-Resubmit
// ---------------------------------------------------------
echo "\n--- TEST 4: Rejection & Edit-and-Resubmit Workflow ---\n";
wp_set_current_user($student_user->ID);
$note2_obj = Notes_Adda_Notes::create($student_user->ID, array(
    'title' => 'Chemistry Lab Report Rough Draft',
    'subject' => 'Chemistry',
    'description' => 'Incomplete lab observations',
    'file_url' => 'http://example.com/chem.pdf'
));
$note2_id = $note2_obj->id;

// Reviewer rejects
wp_set_current_user($expert_user->ID);
$reject_res = Notes_Adda_Notes::review($note2_id, $expert_user->ID, 'rejected', 'Please include titration formulas and calculations.');
assert_test(!is_wp_error($reject_res), "Reviewer rejected note with required reason");

$rejected_note = Notes_Adda_Notes::get_by_id($note2_id);
assert_test($rejected_note->review_status === 'rejected', "Note status is 'rejected'");
assert_test($rejected_note->review_note === 'Please include titration formulas and calculations.', "Rejection reason saved");

// Rejected note private to non-owners
wp_set_current_user($other_student->ID);
$rej_direct = Notes_Adda_Notes::get_details($note2_id, $other_student->ID);
assert_test(is_wp_error($rej_direct), "Rejected note cannot be accessed by other students");

// Owner can access details and see rejection reason
wp_set_current_user($student_user->ID);
$owner_rej_details = Notes_Adda_Notes::get_details($note2_id, $student_user->ID);
assert_test(!is_wp_error($owner_rej_details), "Owner can access their own rejected note");
assert_test($owner_rej_details['review_note'] === 'Please include titration formulas and calculations.', "Owner sees rejection reason in details");

// Owner edits and resubmits
$resubmit_res = Notes_Adda_Notes::update($note2_id, $student_user->ID, array(
    'title' => 'Chemistry Lab Report - Final Resubmitted',
    'description' => 'Added all titration calculations and graphs.'
));
assert_test(!is_wp_error($resubmit_res), "Owner successfully updated rejected note");

$resubmitted_note = Notes_Adda_Notes::get_by_id($note2_id);
assert_test($resubmitted_note->review_status === 'pending', "Editing a rejected note automatically returns it to 'pending'");
assert_test(empty($resubmitted_note->reviewed_by), "Prior reviewer ID cleared on resubmission");
assert_test(empty($resubmitted_note->reviewed_at), "Prior review timestamp cleared on resubmission");

// ---------------------------------------------------------
// TEST 5: Logged-in user rates an Expert (1-5 stars, upsert)
// ---------------------------------------------------------
echo "\n--- TEST 5: Expert Ratings (1-5 stars & Upsert) ---\n";
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->prefix}notes_adda_expert_ratings WHERE expert_id = " . intval( $expert_user->ID ) );

// ---------------------------------------------------------
// TEST 5: Contextual Quarter-Star Expert Ratings
// ---------------------------------------------------------
echo "\n--- TEST 5: Contextual Quarter-Star Expert Ratings ---\n";
// Ensure expert has reviewed Biology
$has_reviewed_bio = Notes_Adda_Ratings::has_reviewed_subject($expert_user->ID, 'Biology');
assert_test($has_reviewed_bio, "Expert has reviewed at least one Biology note");

// 5.1 Student rates expert 4.75 for Biology
wp_set_current_user($student_user->ID);
$rate_res1 = Notes_Adda_Ratings::rate($expert_user->ID, $student_user->ID, 'Biology', 4.75);
assert_test(!is_wp_error($rate_res1), "Student rates expert 4.75 for Biology");

$summary1 = Notes_Adda_Ratings::get_expert_subject_summary($expert_user->ID, 'Biology', $student_user->ID);
assert_test($summary1['has_ratings'] === true, "Expert has active ratings for Biology");
assert_test((float)$summary1['average'] === 4.75, "Average rating is 4.75");
assert_test($summary1['formatted_average'] === '4.75', "Formatted average is '4.75'");
assert_test((int)$summary1['total'] === 1, "Total ratings count is 1");
assert_test((float)$summary1['user_rating'] === 4.75, "Current user rating is 4.75");
assert_test($summary1['reviewed_subject_notes_count'] >= 1, "Reviewed notes count is >= 1");

// 5.2 Re-rate updates existing record rather than creating a duplicate (changes 4.75 -> 4.25)
$rate_res2 = Notes_Adda_Ratings::rate($expert_user->ID, $student_user->ID, 'Biology', 4.25);
assert_test(!is_wp_error($rate_res2), "Student updates rating to 4.25 for Biology");

$summary2 = Notes_Adda_Ratings::get_expert_subject_summary($expert_user->ID, 'Biology', $student_user->ID);
assert_test((float)$summary2['average'] === 4.25, "Average updated to 4.25");
assert_test($summary2['formatted_average'] === '4.25', "Formatted average is '4.25'");
assert_test((int)$summary2['total'] === 1, "Total count remains 1 (no duplicate record created)");
assert_test((float)$summary2['user_rating'] === 4.25, "User rating is now 4.25");

// 5.3 Second student rates 4.75
wp_set_current_user($other_student->ID);
$rate_res3 = Notes_Adda_Ratings::rate($expert_user->ID, $other_student->ID, 'Biology', 4.75);
assert_test(!is_wp_error($rate_res3), "Second student rates expert 4.75 for Biology");

$summary3 = Notes_Adda_Ratings::get_expert_subject_summary($expert_user->ID, 'Biology', $other_student->ID);
assert_test((int)$summary3['total'] === 2, "Total count is now 2");
// Average of 4.25 and 4.75 is 4.50
assert_test((float)$summary3['average'] === 4.50, "Average is (4.25 + 4.75)/2 = 4.50");
assert_test($summary3['formatted_average'] === '4.50', "Formatted average is '4.50'");

// 5.4 Quarter-star validation: Invalid increments and ranges rejected
$inv_inc = Notes_Adda_Ratings::rate($expert_user->ID, $other_student->ID, 'Biology', 4.33);
assert_test(is_wp_error($inv_inc), "Non-quarter increment (4.33) rejected");

$inv_range_high = Notes_Adda_Ratings::rate($expert_user->ID, $other_student->ID, 'Biology', 5.25);
assert_test(is_wp_error($inv_range_high), "Rating above 5.00 (5.25) rejected");

$inv_range_low = Notes_Adda_Ratings::rate($expert_user->ID, $other_student->ID, 'Biology', 0.10);
assert_test(is_wp_error($inv_range_low), "Rating below 0.25 (0.10) rejected");

// 5.5 Rating for an unreviewed subject is strictly blocked
$unrev_subj = Notes_Adda_Ratings::rate($expert_user->ID, $other_student->ID, 'Quantum Physics', 4.75);
assert_test(is_wp_error($unrev_subj), "Rating for subject where expert verified 0 notes rejected");

// ---------------------------------------------------------
// TEST 6: Expert Self-Rating Forbidden & Role Validation
// ---------------------------------------------------------
echo "\n--- TEST 6: Expert Self-Rating Forbidden & Target Validation ---\n";
wp_set_current_user($expert_user->ID);
$self_rate = Notes_Adda_Ratings::rate($expert_user->ID, $expert_user->ID, 'Biology', 5.00);
assert_test(is_wp_error($self_rate), "Expert rating themselves rejected with cannot_rate_self");

// Non-expert cannot be rated
$rate_student = Notes_Adda_Ratings::rate($student_user->ID, $expert_user->ID, 'Biology', 5.00);
assert_test(is_wp_error($rate_student), "Rating a non-expert user rejected with not_an_expert");

// ---------------------------------------------------------
// TEST 7: Library Like AJAX & Multi-Instance Consistency
// ---------------------------------------------------------
echo "\n--- TEST 7: Note Likes (Verified Only, Idempotent, Multi-Instance) ---\n";
wp_set_current_user($student_user->ID);

// 7.1 Liking verified note succeeds
$like_res = Notes_Adda_Likes::add($note1_id, $student_user->ID);
assert_test(!is_wp_error($like_res), "Liking verified note succeeds");
assert_test(Notes_Adda_Likes::has_liked($note1_id, $student_user->ID), "has_liked returns true");

$like_count = (int) Notes_Adda_Notes::get_by_id($note1_id)->like_count;
assert_test($like_count === 1, "Like count is 1");

// 7.2 Removing like succeeds
$rem_res = Notes_Adda_Likes::remove($note1_id, $student_user->ID);
assert_test(!is_wp_error($rem_res), "Removing like succeeds");
assert_test(!Notes_Adda_Likes::has_liked($note1_id, $student_user->ID), "has_liked returns false after remove");

// 7.3 Cannot like pending note2
$rej_like = Notes_Adda_Likes::add($note2_id, $student_user->ID);
assert_test(is_wp_error($rej_like), "Liking pending/rejected note strictly blocked");

echo "\n=========================================\n";
echo "SUMMARY: Passed: $pass_count, Failed: $fail_count\n";
echo "=========================================\n";

if ($fail_count === 0) {
    echo "ALL ACCEPTANCE TESTS PASSED SUCCESSFULLY!\n";
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
