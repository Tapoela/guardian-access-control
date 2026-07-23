<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center">
<div class="col-md-6">
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-building mr-2"></i>Add Site</h6>
    </div>
    <div class="card-body">
        <form method="post" action="/admin/sites/add">
            <?= csrf_field() ?>
            <div class="form-group">
                <label><strong>Site Name <span class="text-danger">*</span></strong></label>
                <input type="text" name="name" class="form-control" required
                       placeholder="e.g. Riverside Estate" value="<?= esc(old('name')) ?>">
            </div>
            <div class="form-group">
                <label><strong>Address</strong></label>
                <input type="text" name="address" class="form-control"
                       placeholder="Physical address" value="<?= esc(old('address')) ?>">
            </div>
            <div class="form-group">
                <label><strong>Contact Person / Phone</strong></label>
                <input type="text" name="contact" class="form-control"
                       placeholder="e.g. John Smith 083 000 0000" value="<?= esc(old('contact')) ?>">
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="/admin/sites" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Site</button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?= $this->endSection() ?>
