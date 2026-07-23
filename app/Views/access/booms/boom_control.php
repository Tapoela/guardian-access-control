<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="container mt-4">
    <h3>Boom Gate

    <div class="row">
        <?php foreach ($cameras as $cam): ?>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header font-weight-bold"><?= esc($cam['name']) ?></div>
                <div class="card-body text-center">
                    <button 
                        class="btn btn-danger btn-lg boom-toggle-btn"
                        data-id="<?= $cam['id'] ?>"
                        data-state="closed"
                        data-ip="<?= esc($cam['ip_address']) ?>"
                        data-user="<?= esc($cam['camera_user']) ?>"
                        data-pass="<?= esc($cam['camera_pass']) ?>"
                        data-channel="<?= esc($cam['alarm_output_channel'] ?? 1) ?>"
                    >
                        <i class="fas fa-times-circle mr-1"></i> Boom Closed
                    </button>
                    <div class="mt-2" id="boomStatus-<?= $cam['id'] ?>"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.boom-toggle-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const cameraId = btn.dataset.id;
            const state = btn.dataset.state === 'closed' ? 'open' : 'closed';
            const statusDiv = document.getElementById('boomStatus-' + cameraId);
            statusDiv.textContent = state === 'open' ? 'Opening...' : 'Closing...';

            fetch('/access/boomcontrol/trigger', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `camera_id=${cameraId}&action=${state}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    btn.dataset.state = state;
                    if (state === 'open') {
                        btn.classList.remove('btn-danger');
                        btn.classList.add('btn-success');
                        btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Boom Open';
                    } else {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-danger');
                        btn.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Boom Closed';
                    }
                    statusDiv.textContent = data.message;
                } else {
                    statusDiv.textContent = 'Failed to change boom state';
                }
            });
        });
    });
});
</script>

<?= $this->endSection() ?>