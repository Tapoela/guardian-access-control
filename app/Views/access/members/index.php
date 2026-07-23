<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col-md-8">
        <h4 class="mb-0"><i class="fas fa-users text-primary mr-2"></i>Members</h4>
        <small class="text-muted">Complex residents / members with registered vehicles.</small>
    </div>
    <div class="col-md-4 text-right">
        <a href="/access/members/add" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus mr-1"></i> Add Member
        </a>
        <a href="/access/whitelist" class="btn btn-outline-success btn-sm ml-1">
            <i class="fas fa-shield-alt mr-1"></i> Whitelist
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
        <?php if (empty($members)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-user-slash fa-2x mb-2"></i>
                <p class="mb-0">No members added yet. <a href="/access/members/add">Add the first member</a>.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="membersTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th class="text-center">Vehicles</th>
                            <th class="text-center">Status</th>
                            <th>Added By</th>
                            <th class="text-center" style="width:110px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <strong><?= esc($m['first_name'] . ' ' . $m['last_name']) ?></strong>
                            </td>
                            <td><?= esc($m['unit_number'] ?? '—') ?></td>
                            <td><small><?= esc($m['phone'] ?? '—') ?></small></td>
                            <td><small><?= esc($m['email'] ?? '—') ?></small></td>
                            <td class="text-center">
                                <span class="badge badge-<?= ($m['vehicle_count'] > 0) ? 'primary' : 'light border' ?>">
                                    <?= (int) $m['vehicle_count'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (($m['status'] ?? 'active') === 'active'): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= esc($m['created_by_name'] ?? '—') ?></small></td>
                            <td class="text-center">
                                <a href="/access/members/edit/<?= $m['id'] ?>" class="btn btn-xs btn-outline-primary mr-1" title="Edit / Manage Vehicles">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="/access/members/delete/<?= $m['id'] ?>"
                                   class="btn btn-xs btn-outline-danger"
                                   title="Delete"
                                   onclick="return confirm('Delete member <?= esc($m['first_name'] . ' ' . $m['last_name']) ?> and all their vehicles?')">
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
        <small><?= count($members) ?> member(s)</small>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'form-control form-control-sm mb-2';
    input.placeholder = 'Filter members…';
    const card = document.querySelector('.card');
    card.parentNode.insertBefore(input, card);
    input.addEventListener('keyup', function () {
        const val = this.value.toUpperCase();
        document.querySelectorAll('#membersTable tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toUpperCase().includes(val) ? '' : 'none';
        });
    });
});
</script>

<?= $this->endSection() ?>
