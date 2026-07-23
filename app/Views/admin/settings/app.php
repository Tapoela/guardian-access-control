<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title mb-0">App Notification Settings</h2>
    </div>
    <form method="post" action="/admin/appsettings/save">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <?php foreach ($notificationTypes as $type): ?>
                            <th><?= ucfirst(str_replace('_', ' ', $type)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?= esc($role['name']) ?></td>
                            <?php foreach ($notificationTypes as $type): ?>
                                <td>
                                    <input type="checkbox"
                                           name="role_<?= $role['id'] ?>_<?= $type ?>"
                                           <?= !empty($settings[$role['id']][$type]) ? 'checked' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>