<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <h4><i class="fas fa-edit text-danger mr-2"></i>Edit Blacklist Entry</h4>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0">
                    <i class="fas fa-car mr-2"></i>
                    Editing: <strong><?= esc($entry['registration']) ?></strong>
                </h6>
            </div>
            <div class="card-body">
                <form method="post" action="/access/blacklist/edit/<?= $entry['id'] ?>">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="registration"><strong>Registration <span class="text-danger">*</span></strong></label>
                        <input type="text" name="registration" id="registration"
                               class="form-control text-uppercase"
                               required
                               style="letter-spacing:2px; font-size:1.1rem;"
                               value="<?= esc($entry['registration']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="reason"><strong>Reason <span class="text-danger">*</span></strong></label>
                        <input type="text" name="reason" id="reason"
                               class="form-control"
                               required
                               value="<?= esc($entry['reason'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="notes"><strong>Additional Notes</strong></label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"><?= esc($entry['notes'] ?? '') ?></textarea>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/access/blacklist" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save mr-1"></i> Save Changes
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

<?= $this->endSection() ?>
