<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Roles</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Roles</h2>
        <a href="/roles/add" class="btn btn-success">Add Role</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= esc($role['id']) ?></td>
                    <td><?= esc($role['name']) ?></td>
                    <td>
                        <a href="/roles/edit/<?= $role['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                        <a href="/roles/delete/<?= $role['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this role?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
</body>
</html>
