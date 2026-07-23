<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? $title ?? 'Guardian') ?></title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <style>
        .nav-link-locked {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }
        .nav-link-locked .lock-icon {
            margin-left: auto;
            font-size: 0.75rem;
            color: #f39c12;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<?php helper(['permission']); ?>

<div class="wrapper">

    <?= $this->include('layouts/partials/navbar') ?>

    <?= $this->include('layouts/partials/sidebar') ?>

    <div class="content-wrapper">

        <section class="content pt-3">

            <div class="container-fluid">

                <?= $this->include('layouts/partials/alerts') ?>

                <?= $this->include('layouts/partials/page_header') ?>

                <?= $this->renderSection('content') ?>

            </div>

        </section>

    </div>

    <?= $this->include('layouts/partials/footer') ?>

</div>
<!-- ./wrapper -->

<!-- AdminLTE JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script>
    // Enable Bootstrap tooltips on locked nav items
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
