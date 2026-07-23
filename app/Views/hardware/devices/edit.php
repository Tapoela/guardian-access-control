<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<form method="post"
      action="<?= site_url('hardware/devices/update/'.$device['Id']) ?>">

    <?= csrf_field() ?>

    <input type="hidden"
           name="FkSiteId"
           value="<?= $device['FkSiteId'] ?>">

    <?= $this->include('hardware/devices/form') ?>

</form>

<?= $this->endSection() ?>