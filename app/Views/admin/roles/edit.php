<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Role</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
<div class="card">
    <div class="card-header"><h2 class="card-title mb-0">Edit Role</h2></div>
    <div class="card-body">
        <form method="post" action="/roles/edit/<?= $role['id'] ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Role Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= esc($role['name']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/roles" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
</body>
</html>
