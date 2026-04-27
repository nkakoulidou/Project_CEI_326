INSERT INTO
    users (
        username,
        email,
        password_hash,
        role
    )
VALUES (
        'administrator',
        'admin@example.com',
        '$2y$10$5qnXkU6bG.ZVWktFVJYi6..uUJioPOhRXvKOvLQT9TGIDY87c.AAu',
        'admin'
    ), -- password: 12345678
    (
        'user1',
        'user1@example.com',
        '$2y$10$IrAOh85U1HU7Ed5RRfsYI..W8jJexnwa04UGblc75WK3pKtEp90ru',
        'user'
    ), -- password: 11112222
    (
        'user2',
        'user2@example.com',
        '$2y$10$CdsnMUdA7uaNxh6KKleN8O07n.XKXrlbBzn3a7poejt5lgKNqTm8S',
        'user'
    ), -- password: 33334444
    (
        'user3',
        'user3@example.com',
        '$2y$10$JExplcQ9QBoMzQFqVve20.GRDiolMH/jnS8RAS29yMM.0B/sDaXQa',
        'user'
    );
-- password: 55556666

INSERT INTO
    specialties (
        name,
        description,
        is_selected
    )
VALUES (
        'Mathematics',
        'Secondary education mathematics specialty.',
        1
    ),
    (
        'Physics',
        'Secondary education physics specialty.',
        1
    ),
    (
        'Philology',
        'Language and literature specialty.',
        1
    );

INSERT INTO
    committee_lists (
        specialty_id,
        title,
        source_url,
        published_year,
        candidate_count,
        notes
    )
VALUES (
        1,
        'Mathematics Appointment List 2026',
        'https://www.eey.gov.cy/',
        2026,
        2,
        'Imported from the official committee page.'
    ),
    (
        2,
        'Physics Appointment List 2026',
        'https://www.eey.gov.cy/',
        2026,
        1,
        'Imported from the official committee page.'
    ),
    (
        3,
        'Philology Appointment List 2026',
        'https://www.eey.gov.cy/',
        2026,
        1,
        'Imported from the official committee page.'
    );

INSERT INTO
    candidates (
        user_id,
        committee_list_id,
        first_name,
        last_name,
        specialty,
        ranking,
        birth_date,
        application_year
    )
VALUES (
        2,
        1,
        'Maria',
        'Ioannou',
        'Mathematics',
        12,
        '1997-05-10',
        2026
    ),
    (
        3,
        1,
        'Andreas',
        'Georgiou',
        'Mathematics',
        21,
        '1995-09-22',
        2025
    ),
    (
        4,
        2,
        'Eleni',
        'Charalambous',
        'Physics',
        8,
        '1993-02-18',
        2026
    ),
    (
        NULL,
        3,
        'Sofia',
        'Demetriou',
        'Philology',
        14,
        '1998-11-03',
        2024
    );