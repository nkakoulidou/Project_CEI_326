USE dioristeon;

-- Demo users
INSERT INTO users (username, email, password_hash, role, status)
VALUES
    ('administrator', 'admin@example.com', '$2y$10$5qnXkU6bG.ZVWktFVJYi6..uUJioPOhRXvKOvLQT9TGIDY87c.AAu', 'admin', 'active'),
    ('nikoleta', 'nkakoulidou05@gmail.com', '$2y$10$IrAOh85U1HU7Ed5RRfsYI..W8jJexnwa04UGblc75WK3pKtEp90ru', 'user', 'active'),
    ('user1', 'user1@example.com', '$2y$10$CdsnMUdA7uaNxh6KKleN8O07n.XKXrlbBzn3a7poejt5lgKNqTm8S', 'user', 'active'),
    ('user2', 'user2@example.com', '$2y$10$JExplcQ9QBoMzQFqVve20.GRDiolMH/jnS8RAS29yMM.0B/sDaXQa', 'user', 'active');

-- Password hints for demo logins:
-- admin@example.com -> 12345678
-- nkakoulidou05@gmail.com -> 11112222
-- user1@example.com -> 33334444
-- user2@example.com -> 55556666

-- Services shown inside lists and applications
INSERT INTO services (service_code, title, category, district, description)
VALUES
    ('PRI-001', 'Primary Education Appointments', 'Primary', 'Nicosia', 'Appointments list for primary education candidates.'),
    ('SEC-002', 'Secondary Education Appointments', 'Secondary', 'Limassol', 'Appointments list for secondary education candidates.'),
    ('SPC-003', 'Special Education Appointments', 'Special Education', 'Larnaca', 'Appointments list for special education candidates.');

-- Published lists
INSERT INTO lists (service_id, academic_year, publication_date, status, notes)
VALUES
    (1, '2025-2026', '2025-09-01', 'published', 'Initial publication for the new academic year.'),
    (2, '2025-2026', '2025-09-05', 'published', 'Updated after review cycle.'),
    (3, '2025-2026', '2025-09-10', 'draft', 'Awaiting final verification.');

-- Candidate profiles linked to user accounts
INSERT INTO candidates (user_id, first_name, last_name, specialty, ranking, phone, district)
VALUES
    (2, 'Nikoleta', 'Kakoulidou', 'IT', 12, '99112233', 'Limassol'),
    (3, 'Maria', 'Ioannou', 'Primary Education', 7, '99112244', 'Nicosia'),
    (4, 'Andreas', 'Georgiou', 'Special Education', 15, '99112255', 'Larnaca');

-- Candidate notification preferences
INSERT INTO candidate_preferences (user_id, notify_new_lists, notify_status_changes, notify_rank_updates)
VALUES
    (2, 1, 1, 1),
    (3, 1, 1, 0),
    (4, 1, 0, 0);

-- Candidate position inside each list
INSERT INTO list_entries (list_id, candidate_id, rank_position, points, status, remarks)
VALUES
    (2, 1, 3, 84.75, 'pending', 'Pending document verification.'),
    (1, 2, 1, 92.50, 'active', 'Top ranked candidate.'),
    (3, 3, 2, 88.20, 'active', 'Eligible for next publication cycle.');

-- Main application rows used by "Track My Applications"
INSERT INTO applications (candidate_id, list_id, application_code, current_status, timeline_note)
VALUES
    (1, 2, 'APP-2025-0259', 'submitted', 'Application received and waiting for review.'),
    (2, 1, 'APP-2025-0001', 'approved', 'Verified and approved by admin.'),
    (3, 3, 'APP-2025-0003', 'under_review', 'Submitted successfully and currently under review.');

-- Demo tracked candidates
INSERT INTO tracked_candidates (user_id, candidate_id)
VALUES
    (2, 2),
    (2, 3);

INSERT INTO user_candidate_links (user_id, candidate_id)
VALUES
    (2, 1),
    (3, 2);

-- Basic audit trail
INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, description)
VALUES
    (1, 'create', 'list', 1, 'Administrator created the primary education list.'),
    (1, 'update', 'application', 1, 'Administrator updated the status of application APP-2025-0259.'),
    (2, 'submit', 'application', 1, 'Candidate submitted application APP-2025-0259.');
