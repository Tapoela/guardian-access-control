<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-0"><i class="fas fa-shield-alt text-success mr-2"></i>Whitelist</h4>
        <small class="text-muted">Whitelisted vehicles trigger the boom gate when detected.</small>
    </div>
    <div class="col-md-4 text-right">
        <a href="/access/whitelist/add" class="btn btn-success btn-sm">
            <i class="fas fa-plus mr-1"></i> Add Vehicle
        </a>
        <a href="/access/members" class="btn btn-outline-secondary btn-sm ml-1">
            <i class="fas fa-users mr-1"></i> Members
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($list)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-car fa-2x mb-2"></i>
                <p class="mb-0">No vehicles on the whitelist yet. <a href="/access/whitelist/add">Add one now</a>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="whitelistTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Registration</th>
                            <th>Make / Colour</th>
                            <th>Member</th>
                            <th>Unit</th>
                            <th>Valid From</th>
                            <th>Valid Until</th>
                            <th>Added By</th>
                            <th class="text-center" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row):
                            $today    = date('Y-m-d');
                            $expired  = $row['valid_until'] && $row['valid_until'] < $today;
                        ?>
                        <tr class="<?= $expired ? 'table-warning' : '' ?>">
                            <td>
                                <span class="badge badge-success badge-pill px-2 py-1" style="font-size:0.95rem;letter-spacing:1px;">
                                    <?= esc($row['registration']) ?>
                                </span>
                                <?php if ($expired): ?>
                                    <span class="badge badge-warning ml-1">Expired</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= esc($row['make'] ?? '') ?>
                                <?php if ($row['vehicle_model'] ?? ''): ?> <?= esc($row['vehicle_model']) ?><?php endif; ?>
                                <?php if ($row['colour'] ?? ''): ?>
                                    <br><small class="text-muted"><?= esc($row['colour']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/access/members/edit/<?= $row['member_id'] ?? '' ?>">
                                    <?= esc($row['first_name'] . ' ' . $row['last_name']) ?>
                                </a>
                            </td>
                            <td><?= esc($row['unit_number'] ?? '—') ?></td>
                            <td><small><?= $row['valid_from'] ? esc($row['valid_from']) : '<span class="text-muted">—</span>' ?></small></td>
                            <td><small><?= $row['valid_until'] ? esc($row['valid_until']) : '<span class="text-muted">No expiry</span>' ?></small></td>
                            <td><small><?= esc($row['created_by_name'] ?? '—') ?></small></td>
                            <td class="text-center">
                                <a href="/access/whitelist/delete/<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline-danger"
                                   title="Remove"
                                   onclick="return confirm('Remove <?= esc($row['registration']) ?> from whitelist?')">
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
        <small><?= count($list) ?> vehicle(s) whitelisted</small>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm mb-2';
    input.placeholder = 'Filter by registration, name, unit…';
    const card = document.querySelector('.card');
    card.parentNode.insertBefore(input, card);
    input.addEventListener('keyup', function () {
        const val = this.value.toUpperCase();
        document.querySelectorAll('#whitelistTable tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toUpperCase().includes(val) ? '' : 'none';
        });
    });
});
</script>

<?= $this->endSection() ?>
