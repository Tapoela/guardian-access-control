
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<?php
$granted = $todayStats->granted ?? 0;
$blacklisted = $todayStats->blacklisted ?? 0;
$unknown = $todayStats->unknown ?? 0;
$noPlate = $todayStats->no_plate ?? 0;
$total = $todayStats->total ?? 0;
$trend = $yesterdayTotal > 0
    ? round((($total - $yesterdayTotal) / $yesterdayTotal) * 100, 1)
    : 0;
$onlineCams = $cameraStatus->online ?? 0;
$offlineCams = $cameraStatus->offline ?? 0;
$errorCams = $cameraStatus->error ?? 0;
$totalCams = $cameraStatus->total ?? 0;
?>

<!-- ── Page heading ────────────────────────────────────────────── -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0"><i class="fas fa-tachometer-alt text-dark mr-2"></i>Dashboard</h4>
            <small class="text-muted">Traffic overview — today vs previous days</small>
        </div>
        <button id="refresh-btn" class="btn btn-sm btn-outline-dark" title="Refresh dashboard">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
    </div>
</div>

<!-- ── Stat cards ──────────────────────────────────────────────── -->
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-car text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold" id="today-total"><?= $total ?></div>
                    <div class="small text-muted">Total Today</div>
                    <div class="small <?= $trend >= 0 ? 'text-success' : 'text-danger' ?>" id="trend-badge">
                        <i class="fas fa-arrow-<?= $trend >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($trend) ?>% vs yesterday
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-check text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold text-success"><?= $granted ?></div>
                    <div class="small text-muted">Granted</div>
                    <div class="small text-muted">
                        <?= $total > 0 ? round(($granted / $total) * 100) : 0 ?>% of total
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-ban text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold text-danger"><?= $blacklisted ?></div>
                    <div class="small text-muted">Blacklisted</div>
                    <div class="small text-muted">
                        <?= $total > 0 ? round(($blacklisted / $total) * 100) : 0 ?>% of total
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-exclamation-triangle text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold text-warning"><?= $unknown + $noPlate ?></div>
                    <div class="small text-muted">Unknown / No Plate</div>
                    <div class="small text-muted">
                        <?= $unknown ?> unknown · <?= $noPlate ?> no plate
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Camera Status ───────────────────────────────────────────── -->
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-wifi text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold text-success" id="online-cameras"><?= $onlineCams ?></div>
                    <div class="small text-muted">Cameras Online</div>
                    <div class="small text-muted"><?= $totalCams ?> total</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-wifi-slash text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold text-danger" id="offline-cameras"><?= $offlineCams ?></div>
                    <div class="small text-muted">Cameras Offline</div>
                    <span id="currently-offline-badge" class="badge badge-danger" style="<?= $offlineCams > 0 ? '' : 'display:none;' ?>">
                        <?= $offlineCams ?> down
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-exclamation-circle text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold text-warning"><?= $errorCams ?></div>
                    <div class="small text-muted">Error</div>
                    <div class="small text-muted">Status unknown</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;min-width:48px;">
                    <i class="fas fa-clock text-white"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="h4 mb-0 font-weight-bold" id="hourly-count">0</div>
                    <div class="small text-muted">This Hour</div>
                    <div class="small text-muted" id="last-updated">Just now</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Charts row ──────────────────────────────────────────────── -->
<div class="row mb-3">
    <div class="col-md-8 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-chart-line mr-2 text-dark"></i>Traffic — Last 7 Days</h6>
            </div>
            <div class="card-body">
                <canvas id="chartWeekly" height="100"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-chart-bar mr-2 text-dark"></i>Hourly Today</h6>
            </div>
            <div class="card-body">
                <canvas id="chartHourly" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Donut + Top cameras + Quick links ──────────────────────── -->
<div class="row mb-3">
    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-chart-pie mr-2 text-dark"></i>Result Breakdown Today</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="chartDonut" height="220"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-video mr-2 text-dark"></i>Top Cameras Today</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($topCameras)): ?>
                    <p class="text-muted p-3 mb-0">No data yet.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php
                        $maxCam = max(array_column($topCameras, 'total')) ?: 1;
                        foreach ($topCameras as $cam):
                        ?>
                        <li class="list-group-item py-2">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="font-weight-bold"><?= esc($cam['name'] ?? 'Unknown') ?></small>
                                <small class="text-muted"><?= $cam['total'] ?></small>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-dark"
                                     style="width:<?= round(($cam['total'] / $maxCam) * 100) ?>%"></div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white border-0 pb-0">
                <h6 class="mb-0"><i class="fas fa-bolt mr-2 text-dark"></i>Quick Access</h6>
            </div>
            <div class="card-body">
                <a href="/access/cameras/events" class="btn btn-outline-dark btn-block mb-2">
                    <i class="fas fa-list mr-2"></i> All Events
                </a>
                <a href="/access/cameras/events?result=blacklisted&date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>&searched=1"
                   class="btn btn-outline-danger btn-block mb-2">
                    <i class="fas fa-ban mr-2"></i> Today's Blacklisted
                </a>
                <a href="/access/cameras" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-video mr-2"></i> Manage Cameras
                </a>
                <a href="/access/cameras/events?result=unknown&date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>&searched=1"
                   class="btn btn-outline-warning btn-block mb-2">
                    <i class="fas fa-question-circle mr-2"></i> Today's Unknown
                </a>
                <a href="/access/cameras/status-log" class="btn btn-outline-info btn-block">
                    <i class="fas fa-heartbeat mr-2"></i> Camera Status Log
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ── Camera Downtime ─────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-video mr-2"></i>Camera Downtime</h6>
        <span id="downtime-loading" class="spinner-border spinner-border-sm" role="status" style="display:none;">
            <span class="sr-only">Loading...</span>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Camera</th>
                        <th>Location</th>
                        <th>Went Offline</th>
                        <th>Came Online</th>
                        <th>Duration</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="downtime-tbody">
                    <tr><td colspan="6" class="text-center p-3">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Chart.js ────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// HTML escape helper
function htmlEscape(str) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(str).replace(/[&<>"']/g, m => map[m]);
}

(function () {
    const weekData = <?= json_encode(array_values($trafficDays)) ?>;
    const allDays  = [];
    for (let i = 6; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        allDays.push(d.toISOString().slice(0, 10));
    }
    const dayMap = {};
    weekData.forEach(r => dayMap[r.day] = r);

    new Chart(document.getElementById('chartWeekly'), {
        type: 'line',
        data: {
            labels: allDays.map(d => d.slice(5)),
            datasets: [
                { label: 'Granted',     data: allDays.map(d => dayMap[d]?.granted     ?? 0), borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,.1)',   tension: 0.3, fill: true },
                { label: 'Blacklisted', data: allDays.map(d => dayMap[d]?.blacklisted ?? 0), borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,.1)',   tension: 0.3, fill: true },
                { label: 'Unknown',     data: allDays.map(d => dayMap[d]?.unknown     ?? 0), borderColor: '#6c757d', backgroundColor: 'rgba(108,117,125,.1)', tension: 0.3, fill: true },
                { label: 'No Plate',    data: allDays.map(d => dayMap[d]?.no_plate    ?? 0), borderColor: '#ffc107', backgroundColor: 'rgba(255,193,7,.1)',   tension: 0.3, fill: true },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    const hourlyData = <?= json_encode(array_values($hourlyToday)) ?>;
    const hourMap    = {};
    hourlyData.forEach(r => hourMap[parseInt(r.hour)] = parseInt(r.total));

    new Chart(document.getElementById('chartHourly'), {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => `${String(i).padStart(2,'0')}:00`),
            datasets: [{
                label: 'Events',
                data: Array.from({length: 24}, (_, i) => hourMap[i] ?? 0),
                backgroundColor: 'rgba(52,58,64,0.75)',
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });

    new Chart(document.getElementById('chartDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Granted', 'Blacklisted', 'Unknown', 'No Plate'],
            datasets: [{
                data: [<?= $granted ?>, <?= $blacklisted ?>, <?= $unknown ?>, <?= $noPlate ?>],
                backgroundColor: ['#28a745','#dc3545','#6c757d','#ffc107'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
})();

function formatDuration(seconds) {
    if (seconds < 60)   return seconds + 's';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return h + 'h ' + m + 'm';
}

function loadDowntime() {
    const loading = document.getElementById('downtime-loading');
    loading.style.display = 'block';

    fetch('/dashboard/downtime')
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(rows => {
            loading.style.display = 'none';
            const tbody = document.getElementById('downtime-tbody');

            if (!rows || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted p-3">No downtime recorded yet.</td></tr>';
                return;
            }

            tbody.innerHTML = rows.map(r => `
                <tr class="${r.status === 'offline' ? 'table-danger' : ''}">
                    <td><strong>${htmlEscape(r.name)}</strong><br><code class="small">${htmlEscape(r.ip_address || 'N/A')}</code></td>
                    <td class="small">${htmlEscape(r.location || '—')}</td>
                    <td class="small">${htmlEscape(r.updated_at || '—')}</td>
                    <td class="small">—</td>
                    <td><span class="badge badge-${r.status === 'offline' ? 'danger' : 'secondary'}">—</span></td>
                    <td>${r.status === 'offline'
                        ? '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Offline</span>'
                        : '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Online</span>'
                    }</td>
                </tr>
            `).join('');
        })
        .catch(err => {
            loading.style.display = 'none';
            console.error('Downtime load error:', err);
            document.getElementById('downtime-tbody').innerHTML =
                '<tr><td colspan="6" class="text-center text-danger p-3"><i class="fas fa-exclamation-triangle mr-2"></i>Failed to load downtime data.</td></tr>';
        });
}

function updateRealtimeStats() {
    fetch('/dashboard/realtime-stats')
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(data => {
            document.getElementById('hourly-count').textContent = data.hourly ?? 0;
            document.getElementById('online-cameras').textContent = data.online ?? 0;
            document.getElementById('offline-cameras').textContent = data.offline ?? 0;
            
            const badge = document.getElementById('currently-offline-badge');
            if (data.offline > 0) {
                badge.textContent = data.offline + ' down';
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }

            const now = new Date();
            document.getElementById('last-updated').textContent = 
                now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        })
        .catch(err => {
            console.error('Realtime stats error:', err);
        });
}

// Initial load
loadDowntime();
updateRealtimeStats();

// Poll every 30 seconds
setInterval(updateRealtimeStats, 30000);
setInterval(loadDowntime, 60000);

// Manual refresh button
document.getElementById('refresh-btn')?.addEventListener('click', () => {
    fetch('/dashboard/refresh', { 
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        location.reload();
    })
    .catch(err => {
        alert('Failed to refresh dashboard');
        console.error(err);
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    // Cleanup if needed
});
</script>

<?= $this->endSection() ?>