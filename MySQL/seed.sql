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