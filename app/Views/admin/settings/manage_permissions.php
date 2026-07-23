<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Add Permission -->
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add New Permission</h3>
            </div>
            <form method="post" action="/settings/addPermission">
                <?= csrf_field() ?>
                <div class="card-body">
                    <p class="text-muted">Use lowercase with underscores (e.g. <code>camera_management</code>). Spaces will be converted automatically.</p>
                    <div class="form-group">
                        <label for="name">Permission Name</label>
                        <input type="text" class="form-control" id="name" name="name"
                               placeholder="e.g. camera_management" required
                               pattern="[a-zA-Z0-9_ ]+" title="Letters, numbers, spaces and underscores only">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Add Permission</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Permissions -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Existing Permissions</h3>
                <a href="/settings/permissions" class="btn btn-sm btn-secondary">
                    <i class="fas fa-toggle-on"></i> Manage Role Access
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Permission Name</th>
                            <th>Display Name</th>
                            <th class="text-center">Core</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $core = ['user_management', 'role_management', 'settings'];
                        foreach ($permissions as $perm):
                        ?>
                            <tr>
                                <td><?= esc($perm['id']) ?></td>
                                <td><code><?= esc($perm['name']) ?></code></td>
                                <td><?= esc(ucwords(str_replace('_', ' ', $perm['name']))) ?></td>
                                <td class="text-center">
                                    <?php if (in_array($perm['name'], $core)): ?>
                                        <span class="badge badge-warning">Core</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Custom</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!in_array($perm['name'], $core)): ?>
                                        <a href="/settings/deletePermission/<?= $perm['id'] ?>"
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete permission \'<?= esc($perm['name']) ?>\'? This will remove it from all roles.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($permissions)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No permissions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
