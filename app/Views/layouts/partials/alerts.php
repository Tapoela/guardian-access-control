<?php if (session()->getFlashdata('success')): ?>

<div class="alert alert-success alert-dismissible">
    <button class="close" data-dismiss="alert">&times;</button>
    <?= session()->getFlashdata('success') ?>
</div>

<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>

<div class="alert alert-danger alert-dismissible">
    <button class="close" data-dismiss="alert">&times;</button>
    <?= session()->getFlashdata('error') ?>
</div>

<?php endif; ?>