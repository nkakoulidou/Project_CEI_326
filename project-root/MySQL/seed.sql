USE dioristeon;

INSERT INTO users (username, email, password_hash, role, status)
VALUES
    ('administrator', 'admin@example.com', '$2y$10$5qnXkU6bG.ZVWktFVJYi6..uUJioPOhRXvKOvLQT9TGIDY87c.AAu', 'admin', 'active'),
    ('maria_p', 'maria@example.com', '$2y$10$IrAOh85U1HU7Ed5RRfsYI..W8jJexnwa04UGblc75WK3pKtEp90ru', 'user', 'active'),
    ('andreas_k', 'andreas@example.com', '$2y$10$CdsnMUdA7uaNxh6KKleN8O07n.XKXrlbBzn3a7poejt5lgKNqTm8S', 'user', 'active'),
    ('eleni_s', 'eleni@example.com', '$2y$10$JExplcQ9QBoMzQFqVve20.GRDiolMH/jnS8RAS29yMM.0B/sDaXQa', 'user', 'active');
-- admin@example.com => 12345678
-- maria@example.com => 11112222
-- andreas@example.com => 33334444
-- eleni@example.com => 55556666

INSERT INTO services (service_code, title, category, district, description)
VALUES
    ('PRI-001', 'Primary Education Appointments', 'Primary', 'Nicosia', 'Appointments list for primary education candidates.'),
    ('SEC-002', 'Secondary Education Appointments', 'Secondary', 'Limassol', 'Appointments list for secondary education candidates.'),
    ('SPC-003', 'Special Education Appointments', 'Special Education', 'Larnaca', 'Appointments list for special education candidates.');

INSERT INTO lists (service_id, academic_year, publication_date, status, notes)
VALUES
    (1, '2025-2026', '2025-09-01', 'published', 'Initial publication for the new academic year.'),
    (2, '2025-2026', '2025-09-05', 'published', 'Updated after review cycle.'),
    (3, '2025-2026', '2025-09-10', 'draft', 'Awaiting final verification.');

INSERT INTO candidates (user_id, first_name, last_name, identity_number, phone, district, specialty)
VALUES
    (2, 'Maria', 'Papadopoulou', 'ID1001', '99112233', 'Nicosia', 'Primary Education'),
    (3, 'Andreas', 'Konstantinou', 'ID1002', '99112244', 'Limassol', 'Mathematics'),
    (4, 'Eleni', 'Stylianou', 'ID1003', '99112255', 'Larnaca', 'Special Education');

INSERT INTO list_entries (list_id, candidate_id, rank_position, points, status, remarks)
VALUES
    (1, 1, 1, 92.50, 'active', 'Top ranked candidate.'),
    (2, 2, 3, 84.75, 'pending', 'Pending document verification.'),
    (3, 3, 2, 88.20, 'active', 'Eligible for next publication cycle.');

INSERT INTO applications (candidate_id, list_id, application_code, current_status, timeline_note)
VALUES
    (1, 1, 'APP-2025-0001', 'approved', 'Verified and approved by admin.'),
    (2, 2, 'APP-2025-0002', 'under_review', 'Waiting for final ranking validation.'),
    (3, 3, 'APP-2025-0003', 'submitted', 'Submitted successfully.');

INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, description)
VALUES
    (1, 'create', 'list', 1, 'Administrator created the primary education list.'),
    (1, 'update', 'application', 2, 'Administrator updated the status of application APP-2025-0002.'),
    (2, 'submit', 'application', 1, 'Candidate submitted application APP-2025-0001.');
