
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-0"><i class="fas fa-car-alt text-dark mr-2"></i>ANPR Events</h4>
        <small class="text-muted">Every plate read pushed by registered cameras.</small>
    </div>
    <div class="col-md-4 text-right">
        <a href="/access/cameras" class="btn btn-outline-dark btn-sm">
            <i class="fas fa-video mr-1"></i> Cameras
        </a>
    </div>
</div>
<!-- Filters -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="/access/cameras/events" class="form-inline flex-wrap">
            <div class="form-group mr-2 mb-1">
                <label class="mr-1 small">Camera:</label>
                <select name="camera_id" class="form-control form-control-sm">
                    <option value="">All cameras</option>
                    <?php foreach ($cameras as $cam): ?>
                        <option value="<?= $cam['id'] ?>" <?= $cameraId == $cam['id'] ? 'selected' : '' ?>>
                            <?= esc($cam['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mr-2 mb-1">
                <label class="mr-1 small">From:</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= esc($dateFrom) ?>">
            </div>
            <div class="form-group mr-2 mb-1">
                <label class="mr-1 small">Time:</label>
                <input type="time" name="time_from" class="form-control form-control-sm"
                       value="<?= esc($timeFrom ?? '00:00') ?>">
            </div>
            <div class="form-group mr-2 mb-1">
                <label class="mr-1 small">To:</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= esc($dateTo) ?>">
            </div>
            <div class="form-group mr-2 mb-1">
                <label class="mr-1 small">Time:</label>
                <input type="time" name="time_to" class="form-control form-control-sm"
                       value="<?= esc($timeTo ?? '23:59') ?>">
            </div>
            <div class="form-group mr-2 mb-1">
                <label class="mr-1 small">Result:</label>
                <select name="result" class="form-control form-control-sm">
                    <option value="">All</option>
                    <option value="granted"     <?= $result === 'granted'     ? 'selected' : '' ?>>Granted</option>
                    <option value="blacklisted" <?= $result === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                    <option value="unknown"     <?= $result === 'unknown'     ? 'selected' : '' ?>>Unknown</option>
                    <option value="no_plate"    <?= $result === 'no_plate'    ? 'selected' : '' ?>>No Plate</option>
                </select>
            </div>
            <button type="submit" class="btn btn-sm btn-dark mb-1">
                <i class="fas fa-filter mr-1"></i> Filter
            </button>
            <input type="hidden" name="searched" value="1">
            <a href="/access/cameras/events" class="btn btn-sm btn-outline-secondary ml-1 mb-1">Reset</a>
        </form>
    </div>
</div>

<!-- Stats bar -->
<?php
$counts = ['granted' => 0, 'blacklisted' => 0, 'unknown' => 0, 'no_plate' => 0];
foreach ($events as $e) {
    if ($e['registration'] === 'NO PLATE') {
        $counts['no_plate']++;
    } else {
        $counts[$e['result']] = ($counts[$e['result']] ?? 0) + 1;
    }
}
?>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-success" style="border-radius:6px;">
            <div class="inner p-3">
                <h4 id="statGranted"><?= $counts['granted'] ?></h4>
                <p>Granted</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-danger" style="border-radius:6px;">
            <div class="inner p-3">
                <h4 id="statBlacklisted"><?= $counts['blacklisted'] ?></h4>
                <p>Blacklisted</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-secondary" style="border-radius:6px;">
            <div class="inner p-3">
                <h4 id="statUnknown"><?= $counts['unknown'] ?></h4>
                <p>Unknown</p>
            </div>
            <div class="icon"><i class="fas fa-question-circle"></i></div>
        </div>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($events)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">No events for the selected period.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="eventsTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Time</th>
                            <th>Snapshot</th>
                            <th>Plate</th>
                            <th class="text-center">Result</th>
                            <th>Member</th>
                            <th>Vehicle</th>
                            <th>Camera</th>
                            <th class="text-center">Dir</th>
                            <th class="text-center">Conf.</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($events as $ev):
                        $badgeMap = [
                            'granted'     => 'badge-success',
                            'blacklisted' => 'badge-danger',
                            'unknown'     => 'badge-secondary',
                        ];
                        $badge = $badgeMap[$ev['result']] ?? 'badge-light';
                        $dirIcon = [
                            'entry'   => '<i class="fas fa-arrow-right text-success" title="Entry"></i>',
                            'exit'    => '<i class="fas fa-arrow-left text-warning" title="Exit"></i>',
                            'unknown' => '<i class="fas fa-arrows-alt-h text-muted" title="Unknown"></i>',
                        ][$ev['direction'] ?? 'unknown'] ?? '';
                        $snapUrl     = !empty($ev['snapshot_path'])
                            ? site_url('anpr/snapshot?path=' . urlencode($ev['snapshot_path']))
                            : null;
                        $overviewUrl = !empty($ev['overview_snapshot_path'])
                            ? site_url('anpr/snapshot?path=' . urlencode($ev['overview_snapshot_path']))
                            : null;
                    ?>
                    <tr data-id="<?= $ev['id'] ?>">
                        <td><small><?= esc($ev['created_at']) ?></small></td>
                        <td class="text-center">
                            <button 
                                type="button"
                                class="btn btn-sm btn-primary view-btn"
                                data-toggle="modal"
                                data-target="#eventModal"
                                data-created="<?= esc($ev['created_at']) ?>"
                                data-registration="<?= esc($ev['registration']) ?>"
                                data-result="<?= esc(ucfirst($ev['result'])) ?>"
                                data-member="<?= esc($ev['member_name'] ?? '—') ?>"
                                data-unit="<?= esc($ev['unit_number'] ?? '') ?>"
                                data-make="<?= esc($ev['vehicle_make'] ?? '') ?>"
                                data-color="<?= esc($ev['vehicle_color'] ?? '') ?>"
                                data-type="<?= esc($ev['vehicle_type'] ?? '') ?>"
                                data-camera="<?= esc($ev['camera_name'] ?? '—') ?>"
                                data-direction="<?= esc($ev['direction'] ?? '—') ?>"
                                data-confidence="<?= esc($ev['confidence'] ?? '') ?>"
                                data-snap="<?= $snapUrl ?>"
                                data-overview="<?= $overviewUrl ?>"
                            >View</button>
                        </td>
                        <td>
                            <?php if ($ev['registration'] === 'NO PLATE'): ?>
                                <span class="badge badge-warning px-2 py-1" style="letter-spacing:1px;font-size:.9rem;">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> NO PLATE
                                </span>
                            <?php else: ?>
                                <span class="badge badge-dark px-2 py-1" style="letter-spacing:1px;font-size:.9rem;">
                                    <?= esc($ev['registration']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $badge ?>"><?= esc(ucfirst($ev['result'])) ?></span>
                        </td>
                        <td>
                            <?php if ($ev['member_name']): ?>
                                <small><?= esc($ev['member_name']) ?>
                                <?= $ev['unit_number'] ? '<span class="text-muted">(Unit '.esc($ev['unit_number']).')</span>' : '' ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $make  = trim($ev['vehicle_make']  ?? '');
                            $color = trim($ev['vehicle_color'] ?? '');
                            $type  = trim($ev['vehicle_type']  ?? '');
                            if ($make || $color || $type): ?>
                                <small class="d-flex flex-column" style="line-height:1.4;">
                                    <?php if ($make):  ?><span class="font-weight-bold"><?= esc($make) ?></span><?php endif; ?>
                                    <?php if ($color): ?><span class="text-muted"><?= esc(ucfirst($color)) ?></span><?php endif; ?>
                                    <?php if ($type):  ?><span class="badge badge-light border" style="font-size:.7rem;"><?= esc($type) ?></span><?php endif; ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                        <td><small><?= esc($ev['camera_name'] ?? '—') ?></small></td>
                        <td class="text-center"><?= $dirIcon ?></td>
                        <td class="text-center">
                            <?php if ($ev['confidence']): ?>
                                <span class="badge badge-<?= $ev['confidence'] >= 80 ? 'success' : ($ev['confidence'] >= 60 ? 'warning' : 'danger') ?>">
                                    <?= (int)$ev['confidence'] ?>%
                                </span>
                            <?php else: ?>
                                <small class="text-muted">—</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>    
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer text-muted text-right">
        <small><?= count($events) ?> event(s) shown</small>
    </div>
</div>

<!-- Snapshot lightbox modal -->
<div class="modal fade" id="snapshotModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-white">
                    <i class="fas fa-car-alt mr-2"></i>
                    <span id="snapshotPlate" class="badge badge-light" style="letter-spacing:1px;font-size:1rem;"></span>
                </h6>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="snapshotFull" src="" alt="Snapshot" class="img-fluid rounded" style="max-height:70vh;width:auto;">
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <a id="snapshotDownload" href="#" download class="btn btn-sm btn-outline-light">
                    <i class="fas fa-download mr-1"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>
<audio id="blacklistAlarm" src="<?= base_url('sounds/alarm 2.mp3') ?>" preload="auto"></audio>
<!-- Live-update indicator -->
<div id="liveBar" class="d-flex align-items-center justify-content-between mb-2" style="display:none!important;">
    <span id="liveDot" class="badge badge-success px-2 py-1">
        <i class="fas fa-circle fa-xs mr-1"></i> Live
    </span>
    <small id="liveMsg" class="text-muted ml-2"></small>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">ANPR Event Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <dt class="col-sm-3">Time</dt>
          <dd class="col-sm-9" id="modal-created"></dd>
          <dt class="col-sm-3">Registration</dt>
          <dd class="col-sm-9" id="modal-registration"></dd>
          <dt class="col-sm-3">Result</dt>
          <dd class="col-sm-9" id="modal-result"></dd>
          <dt class="col-sm-3">Member</dt>
          <dd class="col-sm-9" id="modal-member"></dd>
          <dt class="col-sm-3">Vehicle</dt>
          <dd class="col-sm-9" id="modal-vehicle"></dd>
          <dt class="col-sm-3">Camera</dt>
          <dd class="col-sm-9" id="modal-camera"></dd>
          <dt class="col-sm-3">Direction</dt>
          <dd class="col-sm-9" id="modal-direction"></dd>
          <dt class="col-sm-3">Confidence</dt>
          <dd class="col-sm-9" id="modal-confidence"></dd>
        </dl>
        <div class="row">
          <div class="col-md-6 text-center mb-3">
            <div id="modal-snap-container"></div>
            <small class="text-muted">Plate Close-up</small>
          </div>
          <div class="col-md-6 text-center mb-3">
            <div id="modal-overview-container"></div>
            <small class="text-muted">Vehicle Overview</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    // ── Quick-filter ─────────────────────────────────────────────
    const filterInput = document.createElement('input');
    filterInput.type = 'text';
    filterInput.className = 'form-control form-control-sm mb-2';
    filterInput.placeholder = 'Quick filter table…';
    document.querySelector('.card').before(filterInput);
    filterInput.addEventListener('keyup', function () {
        const val = this.value.toUpperCase();
        document.querySelectorAll('#eventsTable tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toUpperCase().includes(val) ? '' : 'none';
        });
    });

    // ── Thumbnail lightbox ───────────────────────────────────────
    function bindThumbs(scope) {
        (scope || document).querySelectorAll('.anpr-thumb').forEach(function (img) {
            img.addEventListener('click', function () {
                document.getElementById('snapshotFull').src          = this.dataset.full;
                document.getElementById('snapshotPlate').textContent = this.dataset.plate;
                document.getElementById('snapshotDownload').href     = this.dataset.full;
                $('#snapshotModal').modal('show');
            });
        });
    }
    bindThumbs();

    // ── Live polling ─────────────────────────────────────────────
    const dateFrom = document.querySelector('[name=date_from]')?.value || '';
    const dateTo   = document.querySelector('[name=date_to]')?.value   || '';
    const today    = new Date().toISOString().slice(0, 10);
    if (dateFrom !== today || dateTo !== today) return;

    const liveBar = document.getElementById('liveBar');
    const liveMsg = document.getElementById('liveMsg');
    liveBar.style.display = 'flex';

    let lastId = 0;
    document.querySelectorAll('#eventsTable tbody tr[data-id]').forEach(function (row) {
        const id = parseInt(row.dataset.id, 10);
        if (id > lastId) lastId = id;
    });

    // ── Live stat counters ────────────────────────────────────────
    const liveCounts = {
        granted:     parseInt(document.getElementById('statGranted').textContent)     || 0,
        blacklisted: parseInt(document.getElementById('statBlacklisted').textContent) || 0,
        unknown:     parseInt(document.getElementById('statUnknown').textContent)     || 0,
    };

    function updateStats(rows) {
        rows.forEach(function (ev) {
            if (liveCounts[ev.result] !== undefined) liveCounts[ev.result]++;
        });
        document.getElementById('statGranted').textContent     = liveCounts.granted;
        document.getElementById('statBlacklisted').textContent = liveCounts.blacklisted;
        document.getElementById('statUnknown').textContent     = liveCounts.unknown;
    }

    const badgeMap = { granted: 'badge-success', blacklisted: 'badge-danger', unknown: 'badge-secondary' };
    const dirIcon  = {
        entry:   '<i class="fas fa-arrow-right text-success" title="Entry"></i>',
        exit:    '<i class="fas fa-arrow-left text-warning"  title="Exit"></i>',
        unknown: '<i class="fas fa-arrows-alt-h text-muted"  title="Unknown"></i>'
    };

    function confBadge(c) {
        if (!c) return '<small class="text-muted">—</small>';
        const cls = c >= 80 ? 'badge-success' : (c >= 60 ? 'badge-warning' : 'badge-danger');
        return `<span class="badge ${cls}">${c}%</span>`;
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function buildRow(ev) {
        const badgeMap = { granted: 'badge-success', blacklisted: 'badge-danger', unknown: 'badge-secondary' };
        const badge    = badgeMap[ev.result] || 'badge-light';
        const dirIcon  = {
            entry:   '<i class="fas fa-arrow-right text-success" title="Entry"></i>',
            exit:    '<i class="fas fa-arrow-left text-warning"  title="Exit"></i>',
            unknown: '<i class="fas fa-arrows-alt-h text-muted"  title="Unknown"></i>'
        };
        const dir = dirIcon[ev.direction] || dirIcon.unknown;
        const snapUrl     = ev.snapshot_path          ? `<?= site_url('anpr/snapshot?path=') ?>${encodeURIComponent(ev.snapshot_path)}` : '';
        const overviewUrl = ev.overview_snapshot_path ? `<?= site_url('anpr/snapshot?path=') ?>${encodeURIComponent(ev.overview_snapshot_path)}` : '';

        const member = ev.member_name
            ? `<small>${escHtml(ev.member_name)}${ev.unit_number ? ` <span class="text-muted">(Unit ${escHtml(ev.unit_number)})</span>` : ''}</small>`
            : `<small class="text-muted">—</small>`;
        const make  = (ev.vehicle_make  || '').trim();
        const color = (ev.vehicle_color || '').trim();
        const vtype = (ev.vehicle_type  || '').trim();
        const vehicleCell = (make || color || vtype)
            ? `<small class="d-flex flex-column" style="line-height:1.4;">
                ${make  ? `<span class="font-weight-bold">${escHtml(make)}</span>`  : ''}
                ${color ? `<span class="text-muted">${escHtml(color.charAt(0).toUpperCase()+color.slice(1))}</span>` : ''}
                ${vtype ? `<span class="badge badge-light border" style="font-size:.7rem;">${escHtml(vtype)}</span>` : ''}
            </small>`
            : `<small class="text-muted">—</small>`;

        const tr = document.createElement('tr');
        tr.dataset.id = ev.id;
        tr.innerHTML = `
            <td><small>${escHtml(ev.created_at)}</small></td>
            <td class="text-center">
                <button 
                    type="button"
                    class="btn btn-sm btn-primary view-btn"
                    data-toggle="modal"
                    data-target="#eventModal"
                    data-created="${escHtml(ev.created_at)}"
                    data-registration="${escHtml(ev.registration)}"
                    data-result="${escHtml(ev.result.charAt(0).toUpperCase()+ev.result.slice(1))}"
                    data-member="${escHtml(ev.member_name || '—')}"
                    data-unit="${escHtml(ev.unit_number || '')}"
                    data-make="${escHtml(ev.vehicle_make || '')}"
                    data-color="${escHtml(ev.vehicle_color || '')}"
                    data-type="${escHtml(ev.vehicle_type || '')}"
                    data-camera="${escHtml(ev.camera_name || '—')}"
                    data-direction="${escHtml(ev.direction || '—')}"
                    data-confidence="${escHtml(ev.confidence || '')}"
                    data-snap="${snapUrl}"
                    data-overview="${overviewUrl}"
                >View</button>
            </td>
            <td><span class="badge badge-dark px-2 py-1" style="letter-spacing:1px;font-size:.9rem;">${escHtml(ev.registration)}</span></td>
            <td class="text-center"><span class="badge ${badge}">${escHtml(ev.result.charAt(0).toUpperCase()+ev.result.slice(1))}</span></td>
            <td>${member}</td>
            <td>${vehicleCell}</td>
            <td><small>${escHtml(ev.camera_name || '—')}</small></td>
            <td class="text-center">${dir}</td>
            <td class="text-center">${confBadge(ev.confidence)}</td>`;
        return tr;
    }

    const params = new URLSearchParams(window.location.search);
    function pollUrl() {
        const p = new URLSearchParams(params);
        p.set('after', lastId);
        return `<?= site_url('access/cameras/events/poll') ?>?${p.toString()}`;
    }

    let newCount = 0;

    function poll() {
        fetch(pollUrl(), { credentials: 'same-origin' })
            .then(r => r.json())
            .then(function (rows) {
                if (!Array.isArray(rows) || rows.length === 0) return;

                updateStats(rows);

                const tbody = document.querySelector('#eventsTable tbody');
                rows.forEach(function (ev) {
                    if (parseInt(ev.id, 10) > lastId) lastId = parseInt(ev.id, 10);
                    const tr = buildRow(ev);
                    tbody.insertBefore(tr, tbody.firstChild);
                    tr.style.transition = 'background 1s';
                    tr.style.background = ev.result === 'blacklisted' ? '#ffe5e5'
                                        : ev.result === 'granted'     ? '#e6f9ee' : '#fff9e6';
                    setTimeout(() => tr.style.background = '', 3000);
                    newCount++;
                });

                bindThumbs(tbody);
                liveMsg.textContent = `${newCount} new event${newCount !== 1 ? 's' : ''} since page load`;

                // ── Cap live rows at 100 ──────────────────────────
                const allRows = tbody.querySelectorAll('tr');
                if (allRows.length > 100) {
                    for (let i = 100; i < allRows.length; i++) allRows[i].remove();
                }

                const footer = document.querySelector('.card-footer small');
                if (footer) footer.textContent = `${tbody.querySelectorAll('tr').length} event(s) shown`;

                if (rows.some(r => r.result === 'blacklisted')) {
                    const alarm = document.getElementById('blacklistAlarm');
                    if (alarm) { alarm.currentTime = 0; alarm.play().catch(() => {}); }
                }
            })
            .catch(() => {});
    }

    setInterval(poll, 5000);

    $('#eventModal').on('show.bs.modal', function (e) {
        var btn = $(e.relatedTarget);
        $('#modal-created').text(btn.data('created'));
        $('#modal-registration').text(btn.data('registration'));
        $('#modal-result').text(btn.data('result'));
        $('#modal-member').text(btn.data('member') + (btn.data('unit') ? ' (Unit ' + btn.data('unit') + ')' : ''));
        $('#modal-vehicle').text([btn.data('make'), btn.data('color'), btn.data('type')].filter(Boolean).join(', '));
        $('#modal-camera').text(btn.data('camera'));
        $('#modal-direction').text(btn.data('direction'));
        $('#modal-confidence').text(btn.data('confidence') ? btn.data('confidence') + '%' : '—');

        // Plate close-up
        var snap = btn.data('snap');
        $('#modal-snap-container').html(snap ? '<img src="' + snap + '" class="img-fluid rounded border" style="max-height:200px;object-fit:contain;">' : '<span class="text-muted">No image</span>');

        // Overview
        var overview = btn.data('overview');
        $('#modal-overview-container').html(overview ? '<img src="' + overview + '" class="img-fluid rounded border" style="max-height:200px;object-fit:contain;">' : '<span class="text-muted">No image</span>');
    });
});

</script>

<?= $this->endSection() ?>