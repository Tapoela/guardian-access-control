
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-video text-dark mr-2"></i>Cameras</h4>
        <a href="/access/cameras/add" class="btn btn-dark btn-sm">
            <i class="fas fa-plus mr-1"></i> Add Camera
        </a>
    </div>
</div>

<?php if (empty($cameras)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle mr-2"></i>No cameras configured yet.
        <a href="/access/cameras/add">Add your first camera</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Camera</th>
                    <th>Location</th>
                    <th>IP Address</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Features</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cameras as $cam): ?>
                <tr>
                    <td>
                        <strong><?= esc($cam['name'] ?? 'Unnamed') ?></strong>
                        <?php if (!empty($cam['token'])): ?>
                            <br><code class="small text-muted"><?= substr(esc($cam['token']), 0, 8) ?>...</code>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($cam['location'] ?? '—') ?></td>
                    <td>
                        <code><?= esc($cam['ip_address'] ?? 'N/A') ?></code>
                        <?php 
                        // Status indicator
                        $status = $cam['last_status'] ?? 'unknown';
                        $badge = match($status) {
                            'online'  => 'success',
                            'offline' => 'danger',
                            'error'   => 'warning',
                            default   => 'secondary'
                        };
                        ?>
                        <span class="badge badge-<?= $badge ?> ml-2">
                            <i class="fas fa-<?= $status === 'online' ? 'check-circle' : 'times-circle' ?>"></i>
                            <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td><?= esc($cam['channel'] ?? 1) ?></td>
                    <td>
                        <span class="badge badge-<?= ($cam['is_active'] ?? 0) ? 'success' : 'secondary' ?>">
                            <?= ($cam['is_active'] ?? 0) ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="small">
                            <?php if ($cam['gate_trigger'] ?? 0): ?>
                                <span class="badge badge-info" title="Can trigger gate">
                                    <i class="fas fa-door-open"></i> Gate
                                </span>
                            <?php endif; ?>
                            <?php if ($cam['boom_live_view'] ?? 0): ?>
                                <span class="badge badge-primary" title="Live view in boom">
                                    <i class="fas fa-expand"></i> Live
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <a href="/access/cameras/edit/<?= $cam['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="/access/cameras/delete/<?= $cam['id'] ?>" class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this camera?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Events</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">View ANPR detection events</p>
                </div>
                <div class="card-footer bg-light">
                    <a href="/access/cameras/events" class="btn btn-dark btn-sm btn-block">
                        View Events <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-heartbeat mr-2"></i>Status Log</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">Camera uptime and downtime tracking</p>
                </div>
                <div class="card-footer bg-light">
                    <a href="/access/cameras/status-log" class="btn btn-dark btn-sm btn-block">
                        Status Log <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>