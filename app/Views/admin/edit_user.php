<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<div class="card">
    <div class="card-header"><h2 class="card-title mb-0">Edit User</h2></div>
    <div class="card-body">
        <form method="post" action="/admin/editUser/<?= $user['id'] ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= esc($user['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password (leave blank to keep current)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= esc($user['email']) ?>" required>
            </div>
                <!-- Site -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Site</label>
                    <select name="site_id" class="form-select">
                        <option value="">— Global / All Sites —</option>
                        <?php foreach ($sites as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                <?= (($currentRole['site_id'] ?? null) == $s['id']) ? 'selected' : '' ?>>
                                <?= esc($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Role -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required>
                        <option value="">— Select Role —</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"
                                <?= (($currentRole['role_id'] ?? null) == $r['id']) ? 'selected' : '' ?>>
                                <?= esc($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/admin/users" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
