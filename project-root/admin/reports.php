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

$cityStats = [];

if (tableExists($pdo, 'candidates') && columnExists($pdo, 'candidates', 'district')) {
    $cityStats = $pdo->query(
        "SELECT
            COALESCE(NULLIF(TRIM(district), ''), 'Unknown') AS city_name,
            COUNT(id) AS total_candidates
         FROM candidates
         GROUP BY city_name
         ORDER BY total_candidates DESC, city_name ASC"
    )->fetchAll();
}

$specialtyStats = [];
if (tableExists($pdo, 'candidates') && columnExists($pdo, 'candidates', 'specialty')) {
    $specialtyStats = $pdo->query(
        "SELECT
            COALESCE(NULLIF(TRIM(specialty), ''), 'Unknown') AS specialty_name,
            COUNT(id) AS total_candidates
         FROM candidates
         GROUP BY specialty_name
         ORDER BY total_candidates DESC, specialty_name ASC"
    )->fetchAll();
}

$cityChart = [];
foreach ($cityStats as $row) {
    $cityChart[] = [
        'label' => $row['city_name'],
        'value' => (int) $row['total_candidates'],
    ];
}

$specialtyChart = [];
foreach ($specialtyStats as $row) {
    $specialtyChart[] = [
        'label' => $row['specialty_name'],
        'value' => (int) $row['total_candidates'],
    ];
}

adminPageStart('admin.reports.title');
?>

<main class="admin-page">
    <section class="admin-hero admin-hero--tight">
        <div>
            <h1><?php echo h(t('admin.reports.heading')); ?></h1>
            <p><?php echo h(t('admin.reports.intro')); ?></p>
        </div>
    </section>

    <section class="admin-two-column">
        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo h(t('admin.reports.candidates_per_specialty')); ?></h2>
            </div>
            <div class="admin-chart admin-chart--pie" id="city-chart" data-chart='<?php echo h(json_encode($cityChart, JSON_UNESCAPED_UNICODE)); ?>'></div>
        </article>

        <article class="admin-panel">
            <div class="admin-panel__header">
                <h2><?php echo h(t('admin.reports.specialty_column_chart')); ?></h2>
            </div>
            <div class="admin-chart admin-chart--columns" id="specialty-chart" data-chart='<?php echo h(json_encode($specialtyChart, JSON_UNESCAPED_UNICODE)); ?>'></div>
        </article>
    </section>

    <section class="admin-panel">
        <div class="admin-panel__header">
            <h2><?php echo h(t('admin.reports.detailed_figures')); ?></h2>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?php echo h(t('admin.reports.metric')); ?></th>
                        <th><?php echo h(t('admin.reports.value')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo h(t('admin.reports.total_candidates')); ?></td>
                        <td><?php echo $totals['candidates']; ?></td>
                    </tr>
                    <tr>
                        <td><?php echo h(t('admin.reports.average_age_row')); ?></td>
                        <td><?php echo h($averageAge !== null ? (string) $averageAge . ' ' . t('admin.reports.years_suffix') : t('admin.reports.not_enough_data')); ?></td>
                    </tr>
                    <?php foreach ($cityStats as $row): ?>
                        <tr>
                            <td><?php echo h($row['city_name']); ?></td>
                            <td><?php echo (int) $row['total_candidates']; ?> <?php echo h(t('admin.reports.candidates_suffix')); ?></td>
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
            chart.innerHTML = '<p class="admin-empty"><?php echo h(t('admin.reports.no_data')); ?></p>';
            return;
        }

        var items = JSON.parse(raw);
        if (!items.length) {
            chart.innerHTML = '<p class="admin-empty"><?php echo h(t('admin.reports.no_data')); ?></p>';
            return;
        }

        var max = Math.max.apply(null, items.map(function (item) { return item.value; })) || 1;
        if (chart.classList.contains('admin-chart--pie')) {
            var total = items.reduce(function (sum, item) { return sum + item.value; }, 0) || 1;
            var colors = ['#a7f3d0', '#bfdbfe', '#fbcfe8', '#fde68a', '#c4b5fd', '#fdba74', '#99f6e4', '#ddd6fe', '#fecdd3', '#bae6fd'];
            var angle = 0;
            var stops = [];
            var legend = [];

            items.forEach(function (item, index) {
                var part = (item.value / total) * 360;
                var start = angle;
                var end = angle + part;
                var color = colors[index % colors.length];
                stops.push(color + ' ' + start.toFixed(2) + 'deg ' + end.toFixed(2) + 'deg');
                angle = end;
                legend.push(
                    '<li class="admin-chart-pie__legend-item">' +
                        '<span class="admin-chart-pie__dot" style="background:' + color + '"></span>' +
                        '<span class="admin-chart-pie__legend-label">' + item.label + '</span>' +
                        '<strong class="admin-chart-pie__legend-value">' + item.value + '</strong>' +
                    '</li>'
                );
            });

            chart.innerHTML =
                '<div class="admin-chart-pie">' +
                    '<div class="admin-chart-pie__disk" style="background: conic-gradient(' + stops.join(',') + ')"></div>' +
                    '<ul class="admin-chart-pie__legend">' + legend.join('') + '</ul>' +
                '</div>';
            return;
        }

        if (chart.classList.contains('admin-chart--columns')) {
            var colors = ['#93c5fd', '#86efac', '#f9a8d4', '#fcd34d', '#a5b4fc', '#fdba74', '#67e8f9', '#c4b5fd', '#fca5a5', '#bef264'];
            chart.innerHTML = '<div class="admin-chart-columns">' + items.map(function (item) {
                var height = Math.max((item.value / max) * 100, item.value > 0 ? 8 : 0);
                var color = colors[Math.abs(item.label.length) % colors.length];
                return '<div class="admin-chart-columns__item">' +
                    '<span class="admin-chart-columns__value">' + item.value + '</span>' +
                    '<div class="admin-chart-columns__track"><span class="admin-chart-columns__bar" style="height:' + height + '%;background:' + color + '"></span></div>' +
                    '<span class="admin-chart-columns__label" title="' + item.label + '">' + item.label + '</span>' +
                    '</div>';
            }).join('') + '</div>';
            return;
        }

        chart.innerHTML = items.map(function (item) {
            var width = Math.max((item.value / max) * 100, item.value > 0 ? 12 : 0);
            return '<div class="admin-chart__row">' +
                '<span class="admin-chart__label">' + item.label + '</span>' +
                '<div class="admin-chart__track"><span class="admin-chart__bar" style="width:' + width + '%"></span></div>' +
                '<strong class="admin-chart__value">' + item.value + '</strong>' +
                '</div>';
        }).join('');
    }

    renderSimpleChart('city-chart');
    renderSimpleChart('specialty-chart');
</script>

<?php adminPageEnd(); ?>
