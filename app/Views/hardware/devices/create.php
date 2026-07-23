<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-md-12">
            <h3 class="mb-0">
                <i class="fas fa-microchip"></i> Add Hardware Device
            </h3>
            <small class="text-muted">
                Configure a new hardware controller.
            </small>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            Device Information
        </div>

        <div class="card-body">

            <form action="<?= site_url('hardware/devices/store') ?>" method="post">

                <?= csrf_field() ?>

                <?php if (session()->has('errors')) : ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach (session('errors') as $error) : ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?= $this->include('hardware/devices/form') ?>

                <hr>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Save Device
                </button>

                <a href="<?= site_url('hardware/devices') ?>" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

<?= $this->endSection() ?>