<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="container-fluid">

    <div class="card shadow">

        <div class="card-header d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-0">
                    <i class="fas fa-microchip mr-2"></i>
                    <?= esc($pageTitle) ?>
                </h4>

                <small class="text-muted">
                    <?= esc($pageDescription) ?>
                </small>
            </div>

            <a href="<?= site_url('hardware/devices/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i>
                Add Device
            </a>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table id="deviceTable" class="table table-striped table-hover">

                    <thead class="thead-light">

                        <tr>
                            <th>Device</th>
                            <th>Location</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Last Seen</th>
                            <th width="220">Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($devices as $device): ?>

                        <tr>

                            <td>
                                <strong><?= esc($device['DeviceName']) ?></strong><br>
                                <small class="text-muted"><?= esc($device['DeviceType']) ?></small>
                            </td>

                            <td><?= esc($device['Location']) ?></td>

                            <td><?= esc($device['IPAddress']) ?></td>

                            <td>

                                <?php if ($device['IsOnline']): ?>

                                    <span id="status-<?= $device['Id'] ?>" class="badge badge-success">
                                        Online
                                    </span>

                                <?php else: ?>

                                    <span id="status-<?= $device['Id'] ?>" class="badge badge-danger">
                                        Offline
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= $device['LastSeen']
                                    ? date('d M Y H:i', strtotime($device['LastSeen']))
                                    : '-' ?>

                            </td>

                            <td>

                                <div class="btn-group btn-group-sm">

                                    <a href="<?= site_url('hardware/devices/edit/'.$device['Id']) ?>"
                                       class="btn btn-primary"
                                       title="Edit">

                                        <i class="fas fa-edit"></i>

                                    </a>

                                    <button class="btn btn-success test-device"
                                            data-id="<?= $device['Id'] ?>"
                                            title="Test Connection">

                                        <i class="fas fa-plug"></i>

                                    </button>

                                    <a href="<?= site_url('hardware/diagnostics/'.$device['Id']) ?>"
                                       class="btn btn-info"
                                       title="Diagnostics">

                                        <i class="fas fa-stethoscope"></i>

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>

$(function () {

    $('#deviceTable').DataTable({
        responsive: true,
        autoWidth: false,
        pageLength: 10,
        order: [[0, 'asc']]
    });

    $(document).on('click', '.test-device', function () {

        let id = $(this).data('id');
        let button = $(this);

        button.prop('disabled', true);

        $.ajax({

            url: "<?= site_url('hardware/devices/testConnection') ?>/" + id,
            type: "POST",
            dataType: "json",

            success: function (response) {

                if (response.success) {

                    let badge = $('#status-' + id);

                    if (response.online) {

                        badge.removeClass('badge-danger')
                             .addClass('badge-success')
                             .text('Online');

                    } else {

                        badge.removeClass('badge-success')
                             .addClass('badge-danger')
                             .text('Offline');

                    }

                    alert(response.message);

                } else {

                    alert(response.message);

                }

            },

            error: function () {

                alert('Unable to contact the server.');

            },

            complete: function () {

                button.prop('disabled', false);

            }

        });

    });

});

function refreshDevices() {

    $.ajax({
        url: "<?= site_url('hardware/devices/refreshStatus') ?>",
        dataType: "json",

        success: function(data) {

            console.log("Refresh response:", data);

            $("#onlineCount").text(data.online);
            $("#offlineCount").text(data.offline);

            $("#systemStatus").text(
                data.offline == 0 ? "Healthy" : "Attention Required"
            );

            $.each(data.devices, function(i, device) {

                let badge = $("#status-" + device.Id);

                if (device.IsOnline) {
                    badge.removeClass("badge-danger")
                         .addClass("badge-success")
                         .text("ONLINE");
                } else {
                    badge.removeClass("badge-success")
                         .addClass("badge-danger")
                         .text("OFFLINE");
                }
            });
        },

        error: function(xhr) {
            console.error("refreshStatus failed");
            console.error(xhr.status);
            console.error(xhr.responseText);
        }
    });
}

    $.getJSON("<?= site_url('hardware/devices/refreshStatus') ?>", function(data){

        $("#onlineCount").text(data.online);
        $("#offlineCount").text(data.offline);

        $("#systemStatus").text(
            data.offline == 0 ? "Healthy" : "Attention Required"
        );

        $.each(data.devices,function(i,device){

            let badge=$("#status-"+device.Id);

            if(device.IsOnline){

                badge.removeClass("badge-danger")
                     .addClass("badge-success")
                     .text("ONLINE");

            }else{

                badge.removeClass("badge-success")
                     .addClass("badge-danger")
                     .text("OFFLINE");

            }

            $("#lastSeen-"+device.Id).text(device.LastSeen);

        });

    });

}

setInterval(refreshDevices,30000);

</script>

<?= $this->endSection() ?>