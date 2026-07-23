
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <form method="get" class="form-inline col">
        <input type="date" name="start" value="<?= esc(substr($filters['start'],0,10)) ?>" class="form-control mr-2">
        <input type="date" name="end" value="<?= esc(substr($filters['end'],0,10)) ?>" class="form-control mr-2">
        <select name="site_id" class="form-control mr-2">
            <option value="">All Sites</option>
            <?php foreach ($sites as $site): ?>
                <option value="<?= esc($site['id']) ?>" <?= ($filters['site_id'] ?? '') == $site['id'] ? 'selected' : '' ?>><?= esc($site['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="camera_id" class="form-control mr-2">
            <option value="">All Cameras</option>
            <?php foreach ($cameras as $cam): ?>
                <option value="<?= esc($cam['id']) ?>" <?= ($filters['camera_id'] ?? '') == $cam['id'] ? 'selected' : '' ?>><?= esc($cam['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-2">
        <div class="small-box bg-info">
            <div class="inner"><h3><?= esc($summary['total'] ?? 0) ?></h3><p>Total Events</p></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-success">
            <div class="inner"><h3><?= esc($summary['entries'] ?? 0) ?></h3><p>Entries</p></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-warning">
            <div class="inner"><h3><?= esc($summary['exits'] ?? 0) ?></h3><p>Exits</p></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-primary">
            <div class="inner"><h3><?= esc($summary['granted'] ?? 0) ?></h3><p>Granted</p></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-danger">
            <div class="inner"><h3><?= esc($summary['blacklisted'] ?? 0) ?></h3><p>Blacklisted</p></div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="small-box bg-secondary">
            <div class="inner"><h3><?= esc($summary['unknown'] ?? 0) ?></h3><p>Unknown</p></div>
        </div>
    </div>
</div>

<!-- Group and pivot breakdown data by camera -->
<?php
$pivot = [];
$directions = [];
$results = [];
foreach ($breakdown as $row) {
    $cam = $row['camera_name'] ?? 'Unknown';
    $dir = $row['direction'] ?? 'unknown';
    $res = $row['result'] ?? 'unknown';
    if (!isset($pivot[$cam])) $pivot[$cam] = [];
    if (!isset($pivot[$cam][$dir])) $pivot[$cam][$dir] = [];
    $pivot[$cam][$dir][$res] = $row['total'];
    $directions[$dir] = true;
    $results[$res] = true;
}
$directions = array_keys($directions);
$results = array_keys($results);
?>

<div class="card mt-4">
    <div class="card-header"><h5>Breakdown by Camera</h5></div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead>
                <tr>
                    <th>Camera</th>
                    <?php foreach ($directions as $dir): ?>
                        <?php foreach ($results as $res): ?>
                            <th><?= ucfirst($dir) ?> - <?= ucfirst($res) ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pivot as $cam => $dirData): ?>
                    <tr>
                        <td><strong><?= esc($cam) ?></strong></td>
                        <?php foreach ($directions as $dir): ?>
                            <?php foreach ($results as $res): ?>
                                <td><?= isset($dirData[$dir][$res]) ? esc($dirData[$dir][$res]) : 0 ?></td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5>Camera Downtime (Minutes Offline in Period)</h5></div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead>
                <tr>
                    <th>Camera</th>
                    <th>Downtime (minutes)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($downtime)): ?>
                    <tr><td colspan="2" class="text-center text-muted">No downtime data</td></tr>
                <?php else: ?>
                    <?php foreach ($downtime as $cam): ?>
                        <tr>
                            <td><?= esc($cam['name'] ?? 'Unknown') ?></td>
                            <td><?= esc($cam['downtime_minutes'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h5>Hourly Distribution</h5></div>
    <div class="card-body">
        <canvas id="hourlyChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    const hourlyData = <?= json_encode(array_values($hourly ?? [])) ?>;
    const labels = hourlyData.map(row => String(row.hour).padStart(2, '0') + ':00');
    const data = hourlyData.map(row => row.total);

    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Events per Hour',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?= $this->endSection() ?>