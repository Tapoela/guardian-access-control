<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <h4>
            <i class="fas fa-user-edit text-primary mr-2"></i>
            Edit Member: <strong><?= esc($member['first_name'] . ' ' . $member['last_name']) ?></strong>
        </h4>
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
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Member Details -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-user mr-2"></i>Member Details</h6>
            </div>
            <div class="card-body">
                <form method="post" action="/access/members/edit/<?= $member['id'] ?>">
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>First Name <span class="text-danger">*</span></strong></label>
                                <input type="text" name="first_name" class="form-control" required
                                       value="<?= esc($member['first_name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Last Name <span class="text-danger">*</span></strong></label>
                                <input type="text" name="last_name" class="form-control" required
                                       value="<?= esc($member['last_name']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Unit / Erf #</strong></label>
                                <input type="text" name="unit_number" class="form-control"
                                       value="<?= esc($member['unit_number'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Phone</strong></label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= esc($member['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Status</strong></label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= ($member['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($member['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Email</strong></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc($member['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label><strong>Notes</strong></label>
                        <textarea name="notes" class="form-control" rows="2"><?= esc($member['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/access/members" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vehicles -->
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-car mr-2"></i>Registered Vehicles</h6>
                <button type="button" class="btn btn-sm btn-light" data-toggle="collapse" data-target="#addVehicleForm">
                    <i class="fas fa-plus mr-1"></i> Add Vehicle
                </button>
            </div>

            <!-- Add vehicle inline form -->
            <div class="collapse" id="addVehicleForm">
                <div class="card-body border-bottom bg-light">
                    <form method="post" action="/access/members/addVehicle/<?= $member['id'] ?>">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group mb-2">
                                    <label class="small mb-0">Registration *</label>
                                    <input type="text" name="registration" class="form-control form-control-sm text-uppercase reg-input" required placeholder="ABC123GP">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-2">
                                    <label class="small mb-0">Make</label>
                                    <input type="text" name="make" class="form-control form-control-sm" placeholder="Toyota">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-2">
                                    <label class="small mb-0">Model</label>
                                    <input type="text" name="model" class="form-control form-control-sm" placeholder="Hilux">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group mb-2">
                                    <label class="small mb-0">Colour</label>
                                    <input type="text" name="colour" class="form-control form-control-sm" placeholder="White">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-sm btn-primary mb-2 w-100">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (empty($vehicles)): ?>
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-car fa-2x mb-2 d-block"></i>
                        No vehicles registered. Click <strong>Add Vehicle</strong> above.
                    </div>
                <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Registration</th>
                                <th>Make / Model</th>
                                <th>Colour</th>
                                <th class="text-center">Active</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-dark px-2 py-1" style="letter-spacing:1px;">
                                        <?= esc($v['registration']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?= esc($v['make'] ?? '') ?>
                                        <?= esc($v['model'] ?? '') ?>
                                    </small>
                                </td>
                                <td><small><?= esc($v['colour'] ?? '—') ?></small></td>
                                <td class="text-center">
                                    <?php if ($v['is_active'] ?? 1): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="/access/members/deleteVehicle/<?= $v['id'] ?>"
                                       class="btn btn-xs btn-outline-danger"
                                       title="Remove vehicle"
                                       onclick="return confirm('Remove <?= esc($v['registration']) ?>?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted text-right">
                <small><?= count($vehicles) ?> vehicle(s)</small>
                <a href="/access/whitelist/add" class="btn btn-xs btn-success ml-2">
                    <i class="fas fa-shield-alt mr-1"></i> Manage Whitelist
                </a>
            </div>
        </div>

        <!-- Danger zone -->
        <div class="card shadow-sm border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Danger Zone</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">Deleting a member will also remove all their vehicles and whitelist entries.</p>
                <a href="/access/members/delete/<?= $member['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Permanently delete <?= esc($member['first_name'] . ' ' . $member['last_name']) ?> and all their vehicles?')">
                    <i class="fas fa-trash-alt mr-1"></i> Delete Member
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.reg-input').forEach(function (el) {
    el.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
});
</script>

<?= $this->endSection() ?>
