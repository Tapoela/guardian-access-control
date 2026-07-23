<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center">
<div class="col-md-6">
<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white">
        <h6 class="mb-0"><i class="fas fa-edit mr-2"></i>Edit Site</h6>
    </div>
    <div class="card-body">
        <form method="post" action="/admin/sites/edit/<?= $site['id'] ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label><strong>Site Name <span class="text-danger">*</span></strong></label>
                <input type="text" name="name" class="form-control" required
                       value="<?= esc(old('name', $site['name'])) ?>">
            </div>
            <div class="form-group">
                <label><strong>Address</strong></label>
                <input type="text" name="address" class="form-control"
                       value="<?= esc(old('address', $site['address'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label><strong>Contact Person / Phone</strong></label>
                <input type="text" name="contact" class="form-control"
                       value="<?= esc(old('contact', $site['contact'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label><strong>Status</strong></label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= $site['is_active'] ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$site['is_active'] ? 'selected' : '' ?>>Disabled</option>
                </select>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <a href="/admin/sites" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update Site</button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?= $this->endSection() ?>
