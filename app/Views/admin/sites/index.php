<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-building text-primary mr-2"></i>Sites</h4>
    <a href="/admin/sites/add" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Add Site
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Address</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sites)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No sites found.</td></tr>
            <?php else: ?>
                <?php foreach ($sites as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><strong><?= esc($s['name']) ?></strong></td>
                    <td><?= esc($s['address'] ?? '—') ?></td>
                    <td><?= esc($s['contact'] ?? '—') ?></td>
                    <td>
                        <?php if ($s['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="/admin/sites/edit/<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($s['is_active']): ?>
                        <a href="/admin/sites/delete/<?= $s['id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Disable this site? Users assigned to it will still exist.')">
                            <i class="fas fa-ban"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
