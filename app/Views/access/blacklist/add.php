<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <h4><i class="fas fa-ban text-danger mr-2"></i>Add to Blacklist</h4>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<?php
$conflict = $whitelist_conflict ?? false;
$pre      = $prefill ?? [];
?>

<?php if ($conflict): ?>
<!-- ── Whitelist conflict — ask user what to do ─────────────────── -->
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card border-warning shadow">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Plate is Currently Whitelisted
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong><?= esc($conflict_reg) ?></strong> is registered to
                    <strong><?= esc($conflict_owner) ?></strong> and is currently on the
                    <span class="badge badge-success px-2">Whitelist</span>.
                </p>
                <p class="mb-4 text-muted small">
                    A plate can only be on <strong>one list at a time</strong>. Confirming will
                    remove it from the whitelist and add it to the blacklist.
                </p>

                <form method="post" action="/access/blacklist/add">
                    <?= csrf_field() ?>
                    <input type="hidden" name="registration"           value="<?= esc($conflict_reg) ?>">
                    <input type="hidden" name="reason"                 value="<?= esc($pre['reason'] ?? '') ?>">
                    <input type="hidden" name="notes"                  value="<?= esc($pre['notes']  ?? '') ?>">
                    <input type="hidden" name="confirm_remove_whitelist" value="yes">

                    <div class="d-flex flex-wrap">
                        <button type="submit" class="btn btn-danger mr-2 mb-2">
                            <i class="fas fa-exchange-alt mr-1"></i>
                            Move to Blacklist (removes from Whitelist)
                        </button>
                        <a href="/access/blacklist" class="btn btn-secondary mb-2">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Normal add form ─────────────────────────────────────────── -->
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="fas fa-car mr-2"></i>New Blacklist Entry</h6>
            </div>
            <div class="card-body">
                <form method="post" action="/access/blacklist/add">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="registration">
                            <strong>Registration <span class="text-danger">*</span></strong>
                        </label>
                        <input type="text" name="registration" id="registration"
                               class="form-control text-uppercase"
                               placeholder="e.g. ABC123GP"
                               required
                               style="letter-spacing:2px; font-size:1.1rem;"
                               value="<?= esc(old('registration', $pre['registration'] ?? '')) ?>">
                        <small class="form-text text-muted">Enter the full licence plate number. It will be stored in uppercase.</small>
                    </div>

                    <div class="form-group">
                        <label for="reason"><strong>Reason <span class="text-danger">*</span></strong></label>
                        <input type="text" name="reason" id="reason"
                               class="form-control"
                               placeholder="e.g. Theft suspect, Banned tenant"
                               required
                               value="<?= esc(old('reason', $pre['reason'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="notes"><strong>Additional Notes</strong></label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"
                                  placeholder="Optional: more detail about this entry"><?= esc(old('notes', $pre['notes'] ?? '')) ?></textarea>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/access/blacklist" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban mr-1"></i> Add to Blacklist
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('registration').addEventListener('input', function () {
    const pos = this.selectionStart;
    this.value = this.value.toUpperCase();
    this.setSelectionRange(pos, pos);
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>
