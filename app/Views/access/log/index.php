<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-0"><i class="fas fa-history text-secondary mr-2"></i>Access Log</h4>
        <small class="text-muted">Last 150 plate check events (most recent first).</small>
    </div>
    <div class="col-md-4 text-right">
        <a href="/access/blacklist" class="btn btn-outline-danger btn-sm mr-1">
            <i class="fas fa-ban mr-1"></i> Blacklist
        </a>
        <a href="/access/whitelist" class="btn btn-outline-success btn-sm">
            <i class="fas fa-shield-alt mr-1"></i> Whitelist
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($log)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">No access events recorded yet.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="logTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Time</th>
                            <th>Registration</th>
                            <th class="text-center">Result</th>
                            <th>Snapshots</th>
                            <th>Location</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($log as $entry): ?>
                        <?php
                            $result   = $entry['result'] ?? 'unknown';
                            $badgeMap = [
                                'granted'     => 'badge-success',
                                'blacklisted' => 'badge-danger',
                                'unknown'     => 'badge-secondary',
                            ];
                            $badge = $badgeMap[$result] ?? 'badge-secondary';
                        ?>
                                                <tr>
                            <td><small><?= esc($entry['created_at'] ?? '') ?></small></td>
                            <td>
                                <span class="badge badge-dark px-2 py-1" style="letter-spacing:1px;">
                                    <?= esc($entry['registration'] ?? '') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badge ?> px-2 py-1">
                                    <?= esc(ucfirst($result)) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $snapUrl     = !empty($entry['snapshot_path'])
                                    ? site_url('anpr/snapshot?path=' . urlencode($entry['snapshot_path']))
                                    : null;
                                $overviewUrl = !empty($entry['overview_snapshot_path'])
                                    ? site_url('anpr/snapshot?path=' . urlencode($entry['overview_snapshot_path']))
                                    : null;
                                ?>
                                <div class="d-flex gap-1 align-items-center">
                                    <?php if ($snapUrl): ?>
                                        <a href="<?= $snapUrl ?>" target="_blank">
                                            <img src="<?= $snapUrl ?>"
                                                 alt="<?= esc($entry['registration']) ?>"
                                                 class="anpr-thumb rounded border"
                                                 style="height:48px;max-width:70px;object-fit:cover;cursor:pointer;"
                                                 data-full="<?= $snapUrl ?>"
                                                 data-plate="<?= esc($entry['registration']) ?>"
                                                 title="Plate close-up">
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($overviewUrl): ?>
                                        <a href="<?= $overviewUrl ?>" target="_blank">
                                            <img src="<?= $overviewUrl ?>"
                                                 alt="Overview <?= esc($entry['registration']) ?>"
                                                 class="anpr-thumb rounded border"
                                                 style="height:48px;max-width:70px;object-fit:cover;cursor:pointer;border-color:#0d6efd!important;"
                                                 data-full="<?= $overviewUrl ?>"
                                                 data-plate="<?= esc($entry['registration']) ?> (Overview)"
                                                 title="Vehicle overview">
                                        </a>
                                    <?php elseif ($snapUrl): ?>
                                        <span class="text-muted" style="font-size:0.65rem;line-height:1.2;">overview<br>pending</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><small><?= esc($entry['location'] ?? '—') ?></small></td>
                            <td><small class="text-muted"><?= esc($entry['notes'] ?? '—') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted"><?= count($log) ?> event(s) shown</small>
        <div>
            <span class="badge badge-success px-2">Granted</span>
            <span class="badge badge-danger px-2 ml-1">Blacklisted</span>
            <span class="badge badge-secondary px-2 ml-1">Unknown</span>
        </div>
    </div>
</div>

<script>

    document.addEventListener('DOMContentLoaded', function () {

        // ── Quick filter ──────────────────────────────────────────
        const input = document.createElement('input');
        input.type        = 'text';
        input.className   = 'form-control form-control-sm mb-2';
        input.placeholder = 'Filter log…';
        const card = document.querySelector('.card');
        card.parentNode.insertBefore(input, card);
        input.addEventListener('keyup', function () {
            const val = this.value.toUpperCase();
            document.querySelectorAll('#logTable tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toUpperCase().includes(val) ? '' : 'none';
            });
        });

    });
</script>

<?= $this->endSection() ?>
