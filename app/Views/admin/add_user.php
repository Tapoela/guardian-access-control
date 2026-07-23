<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<div class="card">
    <div class="card-header"><h2 class="card-title mb-0">Add User</h2></div>
    <div class="card-body">
        <form method="post" action="/admin/addUser">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
                <!-- Site -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Site <span class="text-danger">*</span></label>
                    <select name="site_id" class="form-select">
                        <option value="">— Global / All Sites (super-admin only) —</option>
                        <?php foreach ($sites as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= old('site_id') == $s['id'] ? 'selected' : '' ?>>
                                <?= esc($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Leave blank to create a global super-admin user.</div>
                </div>

                <!-- Role -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">— Select Role —</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= old('role_id') == $r['id'] ? 'selected' : '' ?>>
                                <?= esc($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <button type="submit" class="btn btn-success">Add User</button>
            <a href="/admin/users" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
