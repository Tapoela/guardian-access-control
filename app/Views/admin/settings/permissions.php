<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title mb-0">Role Permission Settings</h2>
        <small class="text-muted">Toggle permissions on/off per role. Administrator always has full access.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0 text-center">
            <thead class="thead-dark">
                <tr>
                    <th class="text-left" style="min-width:160px;">Role</th>
                    <?php foreach ($permissions as $perm): ?>
                        <th><?= esc(ucwords(str_replace('_', ' ', $perm['name']))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role): ?>
                    <tr>
                        <td class="text-left font-weight-bold"><?= esc(ucfirst($role['name'])) ?></td>
                        <?php foreach ($permissions as $perm): ?>
                            <td>
                                <div class="custom-control custom-switch">
                                    <input
                                        type="checkbox"
                                        class="custom-control-input perm-toggle"
                                        id="perm_<?= $role['id'] ?>_<?= $perm['id'] ?>"
                                        data-role-id="<?= $role['id'] ?>"
                                        data-perm-id="<?= $perm['id'] ?>"
                                        <?= $matrix[$role['id']][$perm['id']] ? 'checked' : '' ?>
                                    >
                                    <label class="custom-control-label" for="perm_<?= $role['id'] ?>_<?= $perm['id'] ?>"></label>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="alert alert-success" style="display:none;position:fixed;bottom:20px;right:20px;z-index:9999;min-width:200px;">
    Permission updated.
</div>

<script>
document.querySelectorAll('.perm-toggle').forEach(function(toggle) {
    toggle.addEventListener('change', function() {
        const roleId = this.dataset.roleId;
        const permId = this.dataset.permId;
        const grant  = this.checked ? 'true' : 'false';
        const csrf   = '<?= csrf_token() ?>';
        const csrfVal = document.cookie.split('; ').find(r => r.startsWith('csrf_cookie_name='))?.split('=')[1] ?? '';

        const formData = new FormData();
        formData.append('role_id', roleId);
        formData.append('permission_id', permId);
        formData.append('grant', grant);
        formData.append(csrf, csrfVal);

        fetch('/settings/togglePermission', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const toast = document.getElementById('toast');
            if (data.success) {
                toast.className = 'alert alert-success';
                toast.textContent = 'Permission updated successfully.';
            } else {
                toast.className = 'alert alert-danger';
                toast.textContent = data.message || 'Failed to update permission.';
                // Revert toggle
                this.checked = !this.checked;
            }
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 2500);
        })
        .catch(() => {
            this.checked = !this.checked;
        });
    });
});
</script>

<?= $this->endSection() ?>
