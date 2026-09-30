# Notes Adda Backend Test UI Instructions & Checklist

This folder (`/notes-adda-test-ui/`) contains a plain HTML/PHP manual testing UI for the Notes Adda backend built so far. It uses standard HTML `<form>` POST/GET submissions with full page reloads to invoke backend PHP class methods directly and display raw execution results.

---

## 1. How to Open Locally

1. Ensure Apache and MySQL are running on your local machine.
2. Open your web browser and navigate to:
   ```
   http://localhost/wordpress/wp-content/plugins/notes-adda/notes-adda-test-ui/index.php
   ```
   *(or `http://localhost/wordpress/wp-content/plugins/notesadda/notes-adda-test-ui/index.php`)*

---

## 2. Test Pages Overview

| Test Page | File | Backend Feature / Class Tested |
| :--- | :--- | :--- |
| **Index Page** | `index.php` | Test suite navigation menu |
| **Tag Manager** | `tags.php` | `Notes_Adda_Tags::get_or_create($name, $type)` |
| **User Profile Manager** | `user-profile.php` | `Notes_Adda_User_Profile::get_by_user_id($user_id)`<br>`Notes_Adda_User_Profile::get_or_create($user_id, $data)` |
| **Note Manager** | `notes.php` | `Notes_Adda_Notes::create($owner_id, $data)`<br>`Notes_Adda_Notes::get_by_id($note_id)` |
| **Note Tag Manager** | `note-tags.php` | `Notes_Adda_Note_Tags::set_tags($note_id, $tags)`<br>`Notes_Adda_Note_Tags::get_tags($note_id)` |
| **Note Search & Query** | `note-query.php` | `Notes_Adda_Note_Query::get_notes($args)` |

---

## 3. Testing Checklist per Feature

### 1. Tag Manager (`tags.php`)
- [ ] **Happy Path (Create Tag):**
  - Enter Tag Name: `Sem 7`, Select Type: `sem`. Submit form.
  - **Expected:** Status `SUCCESS`, returned Tag ID integer (e.g. `1`).
- [ ] **Happy Path (Deduplication / Slug Normalization):**
  - Enter Tag Name: `SEM7`, Select Type: `sem`. Submit form.
  - **Expected:** Status `SUCCESS`, returned existing Tag ID integer (same ID `1`).
- [ ] **Failure Case (Empty Name):**
  - Clear Tag Name field (leave empty or whitespace) and submit.
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_invalid_tag`.

---

### 2. User Profile Manager (`user-profile.php`)
- [ ] **Happy Path (Get Profile):**
  - Submit `get_by_user_id` with WP User ID `1`.
  - **Expected:** Status `SUCCESS`, profile object returned.
- [ ] **Happy Path (Get or Create Profile):**
  - Submit `get_or_create_profile` with WP User ID `1`, College `IIT Delhi`, Bio `Student`.
  - **Expected:** Status `SUCCESS`, profile object returned with matching fields.
- [ ] **Failure Case (Invalid User ID):**
  - Submit User ID `0` or `-5`.
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_invalid_user_id`.
- [ ] **Failure Case (Non-existent WP User):**
  - Submit User ID `999999`.
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_user_not_found`.

---

### 3. Note Manager (`notes.php`)
- [ ] **Happy Path (Create Note):**
  - Fill Owner User ID `1`, Title `Data Structures`, Subject `CS`, File URL `http://localhost/dsa.pdf`. Submit form.
  - **Expected:** Status `SUCCESS`, note object returned with auto-increment ID >= `1000001`.
- [ ] **Happy Path (Get Note by ID):**
  - Submit Note ID `1000001`.
  - **Expected:** Status `SUCCESS`, note object returned.
- [ ] **Failure Case (Missing Required Title):**
  - Leave Title field empty and submit note creation.
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_missing_title`.
- [ ] **Failure Case (Invalid Owner User ID):**
  - Set Owner User ID to `999999` and submit.
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_owner_not_found`.

---

### 4. Note Tag Link Manager (`note-tags.php`)
- [ ] **Happy Path (Set Note Tags):**
  - Submit Note ID `1000001` with Tag 1: `Sem 7` (`sem`) and Tag 2: `Computer Science` (`subject`).
  - **Expected:** Status `SUCCESS`, array of 2 attached tag objects returned.
- [ ] **Happy Path (Reassign Tags & Remove Old Links):**
  - Submit Note ID `1000001` with Tag 1: `SEM7` (`sem`) and Tag 2: `Algorithms` (`subject`).
  - **Expected:** Status `SUCCESS`, array of 2 tag objects (`Sem 7` ID reused, `Algorithms` linked).
- [ ] **Happy Path (Get Note Tags):**
  - Submit `get_note_tags` for Note ID `1000001`.
  - **Expected:** Status `SUCCESS`, array of current tags returned.
- [ ] **Failure Case (Invalid Note ID):**
  - Submit Note ID `999999`.
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_note_not_found`.

---

### 5. Note Search & Query (`note-query.php`)
- [ ] **Happy Path (Unfiltered Search):**
  - Submit query without filters.
  - **Expected:** Status `SUCCESS`, `items` array with paginated notes, `total` count, `total_pages`.
- [ ] **Happy Path (Tag Slug Filter):**
  - Set Tag Slug: `sem7`. Click "Run Query".
  - **Expected:** Status `SUCCESS`, returns only notes attached to `sem7`, each matching note appears exactly once.
- [ ] **Happy Path (Keyword Search):**
  - Enter Search keyword `Data`. Click "Run Query".
  - **Expected:** Status `SUCCESS`, returns notes matching title/subject/chapter/description.
- [ ] **Failure Case (Invalid Orderby Column):**
  - Select `[TEST INVALID ORDERBY]` in Order By dropdown. Click "Run Query".
  - **Expected:** Status `ERROR (WP_Error)`, code: `notes_adda_invalid_orderby`.

---

## 4. Deletion Note
This entire directory (`notes-adda-test-ui`) is completely self-contained and isolated. To delete it when backend testing is finished, simply run:
```bash
rm -rf /home/minmin/Documents/ChatGPT/notesadda/notes-adda-test-ui
```
