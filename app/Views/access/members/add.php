<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <h4><i class="fas fa-user-plus text-primary mr-2"></i>Add Member</h4>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-user mr-2"></i>Member Details</h6>
            </div>
            <div class="card-body">
                <form method="post" action="/access/members/add" id="memberForm">
                    <?= csrf_field() ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>First Name <span class="text-danger">*</span></strong></label>
                                <input type="text" name="first_name" class="form-control" required
                                       value="<?= esc(old('first_name')) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><strong>Last Name <span class="text-danger">*</span></strong></label>
                                <input type="text" name="last_name" class="form-control" required
                                       value="<?= esc(old('last_name')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Unit / Erf #</strong></label>
                                <input type="text" name="unit_number" class="form-control"
                                       value="<?= esc(old('unit_number')) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Phone</strong></label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= esc(old('phone')) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Status</strong></label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= old('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= old('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Email</strong></label>
                        <input type="email" name="email" class="form-control"
                               value="<?= esc(old('email')) ?>">
                    </div>

                    <div class="form-group">
                        <label><strong>Notes</strong></label>
                        <textarea name="notes" class="form-control" rows="2"><?= esc(old('notes')) ?></textarea>
                    </div>

                    <hr>
                    <h6 class="text-primary mb-3"><i class="fas fa-car mr-2"></i>Vehicles</h6>

                    <div id="vehiclesContainer">
                        <div class="vehicle-row card card-body mb-2 p-2 bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <label class="small mb-1">Registration *</label>
                                    <input type="text" name="registrations[]" class="form-control form-control-sm text-uppercase reg-input"
                                           placeholder="ABC123GP">
                                </div>
                                <div class="col-md-3">
                                    <label class="small mb-1">Make</label>
                                    <input type="text" name="makes[]" class="form-control form-control-sm" placeholder="Toyota">
                                </div>
                                <div class="col-md-2">
                                    <label class="small mb-1">Model</label>
                                    <input type="text" name="models[]" class="form-control form-control-sm" placeholder="Hilux">
                                </div>
                                <div class="col-md-2">
                                    <label class="small mb-1">Colour</label>
                                    <input type="text" name="colours[]" class="form-control form-control-sm" placeholder="White">
                                </div>
                                <div class="col-md-2 text-right">
                                    <label class="small mb-1 d-block invisible">Remove</label>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-vehicle" style="display:none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="addVehicleRow">
                        <i class="fas fa-plus mr-1"></i> Add Another Vehicle
                    </button>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/access/members" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Tips</h6></div>
            <div class="card-body small">
                <ul class="pl-3 mb-0">
                    <li>Registrations are stored in <strong>UPPERCASE</strong>.</li>
                    <li>After saving, add the member's vehicles to the <strong>Whitelist</strong> to grant boom gate access.</li>
                    <li>Inactive members' vehicles will NOT trigger the gate.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Uppercase registration as user types
document.querySelectorAll('.reg-input').forEach(function (el) {
    el.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });
});

// Add more vehicle rows
document.getElementById('addVehicleRow').addEventListener('click', function () {
    const container = document.getElementById('vehiclesContainer');
    const first = container.querySelector('.vehicle-row');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    const removeBtn = clone.querySelector('.remove-vehicle');
    removeBtn.style.display = '';
    removeBtn.addEventListener('click', function () { clone.remove(); });
    clone.querySelectorAll('.reg-input').forEach(function (el) {
        el.addEventListener('input', function () {
            const pos = this.selectionStart;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(pos, pos);
        });
    });
    container.appendChild(clone);
});
</script>

<?= $this->endSection() ?>
