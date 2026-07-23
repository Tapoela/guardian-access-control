<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <h4><i class="fas fa-shield-alt text-success mr-2"></i>Add Vehicle to Whitelist</h4>
        <small class="text-muted">Only active member vehicles not already whitelisted are shown.</small>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-1"></i>
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($blacklist_conflict)): ?>
            <!-- ── Blacklist conflict confirmation ───────────────── -->
            <div class="card border-danger shadow-sm mb-3">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Blacklist Conflict
                </div>
                <div class="card-body">
                    <p>
                        <strong><?= esc($conflict_reg) ?></strong> is currently on the
                        <span class="badge badge-danger">blacklist</span>
                        <?php if (!empty($conflict_reason)): ?>
                            (reason: <em><?= esc($conflict_reason) ?></em>)
                        <?php endif; ?>
                    </p>
                    <p>How would you like to proceed?</p>

                    <p class="mb-4 text-muted small">
                        A plate can only be on <strong>one list at a time</strong>. Confirming will
                        remove it from the blacklist and add it to the whitelist.
                    </p>

                    <!-- Single option: move to whitelist, remove from blacklist -->
                    <form method="post" action="/access/whitelist/add" class="mb-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="vehicle_id"               value="<?= esc($prefill['vehicle_id']) ?>">
                        <input type="hidden" name="valid_from"               value="<?= esc($prefill['valid_from'] ?? '') ?>">
                        <input type="hidden" name="valid_until"              value="<?= esc($prefill['valid_until'] ?? '') ?>">
                        <input type="hidden" name="confirm_remove_blacklist" value="yes">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-exchange-alt mr-1"></i>
                            Move to Whitelist (removes from Blacklist)
                        </button>
                    </form>

                    <a href="/access/whitelist/add" class="btn btn-secondary btn-block">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </div>
        <?php else: ?>

        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-car mr-2"></i>Whitelist Entry</h6>
            </div>
            <div class="card-body">
                <?php if (empty($vehicles)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        All active member vehicles are already whitelisted, or no member vehicles exist.
                        <a href="/access/members/add" class="alert-link">Add a member first</a>.
                    </div>
                <?php else: ?>
                    <form method="post" action="/access/whitelist/add">
                        <?= csrf_field() ?>

                        <div class="form-group">
                            <label for="vehicle_id"><strong>Member Vehicle <span class="text-danger">*</span></strong></label>
                            <select name="vehicle_id" id="vehicle_id" class="form-control" required>
                                <option value="">— Select a vehicle —</option>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>"
                                        <?= (!empty($prefill['vehicle_id']) && $prefill['vehicle_id'] == $v['id']) ? 'selected' : '' ?>>
                                        <?= esc($v['registration']) ?>
                                        — <?= esc($v['first_name'] . ' ' . $v['last_name']) ?>
                                        (Unit <?= esc($v['unit_number'] ?? '?') ?>)
                                        <?= $v['make'] ? '| ' . esc($v['make']) : '' ?>
                                        <?= $v['colour'] ? esc($v['colour']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="valid_from"><strong>Valid From</strong></label>
                                    <input type="date" name="valid_from" id="valid_from"
                                           class="form-control"
                                           value="<?= esc($prefill['valid_from'] ?? date('Y-m-d')) ?>">
                                    <small class="form-text text-muted">Leave blank for no start restriction.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="valid_until"><strong>Valid Until</strong></label>
                                    <input type="date" name="valid_until" id="valid_until"
                                           class="form-control"
                                           value="<?= esc($prefill['valid_until'] ?? '') ?>">
                                    <small class="form-text text-muted">Leave blank for no expiry.</small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between">
                            <a href="/access/whitelist" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-shield-alt mr-1"></i> Add to Whitelist
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
