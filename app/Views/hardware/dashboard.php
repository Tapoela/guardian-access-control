<?= $this->extend('layout') ?>

<?= $this->section('content') ?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Guardian Control Centre</h2>
            <small class="text-muted">Live status of all Guardian hardware controllers</small>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="card bg-success text-white shadow border-0">
                <div class="card-body text-center">
                    <h5>Online</h5>
                    <h1 id="onlineCount"><?= $online ?></h1>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white shadow border-0">
                <div class="card-body text-center">
                    <h5>Offline</h5>
                    <h1 id="offlineCount"><?= $offline ?></h1>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-primary text-white shadow border-0">
                <div class="card-body text-center">
                    <h5>Total Devices</h5>
                    <h1><?= $total ?></h1>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow border-0">
                <div class="card-body text-center">
                    <h5>System Status</h5>
                    <h4 id="systemStatus">
                        <?= ($offline == 0) ? 'Healthy' : 'Attention Required' ?>
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <!-- Device Cards -->
    <div class="row">

        <?php foreach ($devices as $device): ?>

            <div class="col-xl-4 col-lg-6 mb-4">

                <div class="card shadow border-0 h-100">

                    <!-- Card Header -->
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <div>
                            <strong><?= esc($device['DeviceName']) ?></strong><br>
                            <small class="text-muted">
                                <?= esc($device['DeviceType']) ?>
                            </small>
                        </div>

                        <?php if ($device['IsOnline']) : ?>

                            <span id="status-<?= $device['Id'] ?>" class="badge bg-success">
                                ONLINE
                            </span>

                        <?php else : ?>

                            <span id="status-<?= $device['Id'] ?>" class="badge bg-danger">
                                OFFLINE
                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- Card Body -->
                    <div class="card-body">

                        <table class="table table-sm table-borderless mb-3">

                            <tr>
                                <th width="120">IP Address</th>
                                <td><?= esc($device['IPAddress']) ?></td>
                            </tr>

                            <tr>
                                <th>Port</th>
                                <td><?= esc($device['TcpPort']) ?></td>
                            </tr>

                            <tr>
                                <th>Module</th>
                                <td><?= esc($device['ModuleAddress']) ?></td>
                            </tr>

                            <tr>
                                <th>Location</th>
                                <td><?= esc($device['Location']) ?></td>
                            </tr>

                            <tr>
                                <th>Last Seen</th>
                                <td>
                                    <?= !empty($device['LastSeen'])
                                        ? date('d M Y H:i', strtotime($device['LastSeen']))
                                        : '-' ?>
                                </td>
                            </tr>

                        </table>

                    </div>

                    <!-- Card Footer -->
                    <div class="card-footer bg-white">

                        <div class="btn-group w-100">

                            <button
                                class="btn btn-success test-device"
                                data-id="<?= $device['Id'] ?>">
                                <i class="fas fa-plug"></i> Test
                            </button>

                            <a
                                href="<?= site_url('hardware/diagnostics/' . $device['Id']) ?>"
                                class="btn btn-primary">
                                <i class="fas fa-stethoscope"></i> Diagnostics
                            </a>

                            <button
                                class="btn btn-warning boom-open"
                                data-id="<?= $device['Id'] ?>">
                                <i class="fas fa-door-open"></i> Open Boom
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>

$(function () {

    $('.test-device').click(function () {

        let button = $(this);
        let id = button.data('id');

        button.prop('disabled', true);

        $.ajax({

            url: "<?= site_url('hardware/devices/testConnection') ?>/" + id,
            type: "POST",
            dataType: "json",

            success: function (response) {

    console.log(response);

    let badge = $('#status-' + id);

    console.log("Badge found:", badge.length);
    console.log("Badge before:", badge.attr('class'));

    if (response.online) {

        badge.removeClass('badge-danger badge bg-danger')
             .addClass('badge-success bg-success')
             .text('Online');

    } else {

        badge.removeClass('badge-success badge bg-success')
             .addClass('badge-danger bg-danger')
             .text('Offline');

    }

    console.log("Badge after:", badge.attr('class'));

    alert(response.message);
},

            error: function(xhr, status, error) {

                console.log(xhr.responseText);

                alert(
                    "HTTP: " + xhr.status +
                    "\nStatus: " + status +
                    "\nError: " + error +
                    "\n\n" + xhr.responseText
                );

            },

            complete: function () {

                button.prop('disabled', false);

            }

        });

    });

});

</script>

<?= $this->endSection() ?>