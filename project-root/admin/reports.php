<?php

require_once __DIR__ . '/../includes/admin.php';

requireAdmin();

$totals = [
    'users' => countRows($pdo, 'users'),
    'admins' => tableExists($pdo, 'users')
        ? (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn()
        : 0,
    'lists' => countRows($pdo, 'lists'),
    'candidates' => countRows($pdo, 'candidates'),
];

$averageAge = null;

if (tableExists($pdo, 'candidates') && columnExists($pdo, 'candidates', 'birth_date')) {
    $averageAge = $pdo->query(
        'SELECT ROUND(AVG(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())), 1) FROM candidates WHERE birth_date IS NOT NULL'
    )->fetchColumn();
}

$specialtyStats = [];

if (tableExists($pdo, 'candidates') && columnExists($pdo, 'candidates', 'specialty')) {
    if (
        tableExists($pdo, 'committee_lists') &&
        tableExists($pdo, 'specialties') &&
        columnExists($pdo, 'candidates', 'committee_list_id') &&
        columnExists($pdo, 'committee_lists', 'specialty_id')
    ) {
        $specialtyStats = $pdo->query(
            "SELECT
                COALESCE(NULLIF(specialties.name, ''), NULLIF(candidates.specialty, ''), 'Unassigned') AS specialty_name,
                COUNT(candidates.id) AS total_candidates
             FROM candidates
             LEFT JOIN committee_lists ON committee_lists.id = candidates.committee_list_id
             LEFT JOIN specialties ON specialties.id = committee_lists.specialty_id
             GROUP BY specialty_name
             ORDER BY total_candidates DESC, specialty_name ASC"
        )->fetchAll();
    } else {
        $specialtyStats = $pdo->query(
            "SELECT
                COALESCE(NULLIF(specialty, ''), 'Unassigned') AS specialty_name,
                COUNT(id) AS total_candidates
             FROM candidates
             GROUP BY specialty_name
             ORDER BY total_candidates DESC, specialty_name ASC"
        )->fetchAll();
    }
}

$yearlyStats = tableExists($pdo, 'applications')
    ? $pdo->query(
        'SELECT YEAR(submitted_at) AS application_year, COUNT(*) AS total_candidates
         FROM applications
         WHERE submitted_at IS NOT NULL
         GROUP BY YEAR(submitted_at)
         ORDER BY YEAR(submitted_at) ASC'
    )->fetchAll()
    : [];

$specialtyChart = [];
foreach ($specialtyStats as $row) {
    $specialtyChart[] = [
        'label' => $row['specialty_name'],
        'value' => (int) $row['total_candidates'],
    ];
}

$yearChart = [];
foreach ($yearlyStats as $row) {
    $yearChart[] = [
        'label' => (string) $row['application_year'],
        'value' => (int) $row['total_candidates'],
    ];
}

adminPageStart('Reports');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <p class="admin-eyebrow">Reports</p>
            <h1>Statistics Dashboard</h1>
            <p>Summary cards and simple charts for specialties, candidate ages and yearly activity.</p>
        </div>
    </section>

    <section class="admin-stats-grid">
        <article class="admin-stat-card">
            <span>Total Users</span>
            <strong><?php echo $totals['users']; ?></strong>
        </article>
        <article class="admin-stat-card">
            <span>Admin Accounts</span>
            <strong><?php echo $totals['admins']; ?></strong>
        </article>
        <article class="admin-stat-card">
            <span>Published Lists</span>
            <strong><?php echo $totals['lists']; ?></strong>
        </article>
        <article class="admin-stat-card">
            <span>Average Candidate Age</span>
            <strong><?php echo h($averageAge !== null ? (string) $averageAge : 'N/A'); ?></strong>
        </article>
    </section>

    <section class="admin-two-column">
        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2>Candidates Per Specialty</h2>
            </div>
            <div class="admin-chart" id="specialty-chart" data-chart='<?php echo h(json_encode($specialtyChart, JSON_UNESCAPED_UNICODE)); ?>'></div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2>New Candidates Per Year</h2>
            </div>
            <div class="admin-chart" id="year-chart" data-chart='<?php echo h(json_encode($yearChart, JSON_UNESCAPED_UNICODE)); ?>'></div>
        </article>
    </section>

    <section class="admin-panel">
        <div class="admin-panel__header">
            <h2>Detailed Figures</h2>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total candidates</td>
                        <td><?php echo $totals['candidates']; ?></td>
                    </tr>
                    <tr>
                        <td>Average candidate age</td>
                        <td><?php echo h($averageAge !== null ? (string) $averageAge . ' years' : 'Not enough data'); ?></td>
                    </tr>
                    <?php foreach ($specialtyStats as $row): ?>
                        <tr>
                            <td><?php echo h($row['specialty_name']); ?></td>
                            <td><?php echo (int) $row['total_candidates']; ?> candidates</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<script>
    function renderSimpleChart(containerId) {
        var chart = document.getElementById(containerId);
        if (!chart) {
            return;
        }

        var raw = chart.getAttribute('data-chart');
        if (!raw) {
            chart.innerHTML = '<p class="admin-empty">No data available.</p>';
            return;
        }

        var items = JSON.parse(raw);
        if (!items.length) {
            chart.innerHTML = '<p class="admin-empty">No data available.</p>';
            return;
        }

        var max = Math.max.apply(null, items.map(function (item) { return item.value; })) || 1;
        chart.innerHTML = items.map(function (item) {
            var width = Math.max((item.value / max) * 100, item.value > 0 ? 12 : 0);
            return '<div class="admin-chart__row">' +
                '<span class="admin-chart__label">' + item.label + '</span>' +
                '<div class="admin-chart__track"><span class="admin-chart__bar" style="width:' + width + '%"></span></div>' +
                '<strong class="admin-chart__value">' + item.value + '</strong>' +
                '</div>';
        }).join('');
    }

    renderSimpleChart('specialty-chart');
    renderSimpleChart('year-chart');
</script>

<?php adminPageEnd(); ?>
