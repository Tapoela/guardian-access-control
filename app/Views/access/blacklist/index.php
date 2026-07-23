<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-0"><i class="fas fa-ban text-danger mr-2"></i>Blacklist</h4>
        <small class="text-muted">Vehicles on the blacklist trigger a Telegram alert when detected.</small>
    </div>
    <div class="col-md-4 text-right">
        <a href="/access/blacklist/add" class="btn btn-danger btn-sm">
            <i class="fas fa-plus mr-1"></i> Add to Blacklist
        </a>
        <a href="/access/log" class="btn btn-secondary btn-sm ml-1">
            <i class="fas fa-history mr-1"></i> Access Log
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($list)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                <p class="mb-0">No registrations on the blacklist.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="blacklistTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Registration</th>
                            <th>Reason</th>
                            <th>Notes</th>
                            <th>Added By</th>
                            <th>Date Added</th>
                            <th class="text-center" style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                        <tr>
                            <td>
                                <span class="badge badge-danger badge-pill px-2 py-1" style="font-size:0.95rem;letter-spacing:1px;">
                                    <?= esc($row['registration']) ?>
                                </span>
                            </td>
                            <td><?= esc($row['reason'] ?? '—') ?></td>
                            <td><small class="text-muted"><?= esc($row['notes'] ?? '—') ?></small></td>
                            <td><small><?= esc($row['created_by_name'] ?? '—') ?></small></td>
                            <td><small><?= esc($row['created_at'] ?? '—') ?></small></td>
                            <td class="text-center">
                                <a href="/access/blacklist/edit/<?= $row['id'] ?>" class="btn btn-xs btn-outline-primary mr-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/access/blacklist/delete/<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-danger"
                                   title="Remove"
                                   onclick="return confirm('Remove <?= esc($row['registration']) ?> from blacklist?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer text-muted text-right">
        <small><?= count($list) ?> record(s)</small>
    </div>
</div>

<script>
// Quick filter
document.addEventListener('DOMContentLoaded', function () {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm mb-2';
    input.placeholder = 'Filter registrations…';
    const card = document.querySelector('.card');
    card.parentNode.insertBefore(input, card);
    input.addEventListener('keyup', function () {
        const val = this.value.toUpperCase();
        document.querySelectorAll('#blacklistTable tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toUpperCase().includes(val) ? '' : 'none';
        });
    });
});
</script>

<?= $this->endSection() ?>
