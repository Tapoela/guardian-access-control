
<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col d-flex align-items-center justify-content-between">
        <h4><i class="fas fa-edit text-dark mr-2"></i>Edit Camera: <strong><?= esc($camera['name']) ?></strong></h4>
        <span id="camera-status" class="badge badge-secondary px-3 py-2" style="font-size:.85rem;">
            <i class="fas fa-circle-notch fa-spin mr-1"></i> Checking...
        </span>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="fas fa-camera mr-2"></i>Camera Details</h6>
            </div>
            <div class="card-body">
                <form method="post" action="/access/cameras/edit/<?= $camera['id'] ?>">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label><strong>Camera Name <span class="text-danger">*</span></strong></label>
                        <input type="text" name="name" class="form-control" required
                               value="<?= esc($camera['name']) ?>">
                    </div>

                    <div class="form-group">
                        <label><strong>Location Description</strong></label>
                        <input type="text" name="location" class="form-control"
                               value="<?= esc($camera['location'] ?? '') ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label><strong>Camera IP Address</strong></label>
                                <input type="text" name="ip_address" class="form-control"
                                       value="<?= esc($camera['ip_address'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Door/Gate Channel</strong></label>
                                <input type="number" name="channel" class="form-control"
                                       value="<?= (int)$camera['channel'] ?>" min="1" max="8">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Notes</strong></label>
                        <textarea name="notes" class="form-control" rows="2"><?= esc($camera['notes'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                   value="1" <?= $camera['is_active'] ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="is_active">Camera Active</label>
                        </div>
                    </div>

                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_monitored" name="is_monitored"
                                    value="1" <?= !empty($camera['is_monitored']) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="is_monitored">
                                <strong>Monitor Camera (CCTV)</strong>
                                <small class="d-block text-muted font-weight-normal">
                                        Alert operator and Telegram if this camera goes offline/online.
                                </small>
                            </label>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="gate_trigger" id="gate_trigger" value="1" <?= !empty($camera['gate_trigger']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="gate_trigger">
                                    Enable Boom Gate Control for this Camera
                            </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="boom_live_view" id="boom_live_view" value="1" <?= !empty($camera['boom_live_view']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="boom_live_view">
                                    Show this camera as Boom Live View
                            </label>
                            </div>
                        </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="regen_token" name="regen_token" value="1">
                            <label class="custom-control-label text-danger" for="regen_token">
                                <strong>Regenerate secret token</strong>
                                <small class="d-block text-muted font-weight-normal">
                                    The old token will stop working immediately. You must reconfigure the camera URL.
                                </small>
                            </label>
                        </div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold text-muted mb-3">
                        <i class="fas fa-camera-retro mr-1"></i> Overview Camera (Vehicle Shot)
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Overview Camera IP</label>
                                <input type="text" name="overview_camera_ip" class="form-control"
                                       placeholder="192.168.1.5"
                                       value="<?= esc($camera['overview_camera_ip'] ?? '') ?>">
                                <small class="form-text text-muted">Wide-angle camera for vehicle shots.</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="overview_camera_user" class="form-control"
                                       value="<?= esc($camera['overview_camera_user'] ?? 'admin') ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="overview_camera_pass" class="form-control"
                                       value="<?= esc($camera['overview_camera_pass'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Delay (seconds)</label>
                                <input type="number" name="overview_snapshot_delay" class="form-control"
                                       min="0" max="60"
                                       value="<?= (int)($camera['overview_snapshot_delay'] ?? 5) ?>">
                                <small class="form-text text-muted">0 = immediate.</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ANPR Push URL (copy this to the camera)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="tokenUrl"
                                    value="http://<?= $_SERVER['HTTP_HOST'] ?>/anpr/event/<?= esc($camera['token']) ?>"
                                    readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('tokenUrl').value);this.textContent='Copied!'">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Token: <code><?= esc($camera['token']) ?></code></small>
                        </div>

                        <!-- Regenerate token -->
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="regen_token" name="regen_token" value="1">
                                <label class="custom-control-label text-danger" for="regen_token">
                                    Regenerate token (camera will stop sending until you update its URL)
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold text-muted mb-3">
                        <i class="fas fa-door-open mr-1"></i> Boom Gate / Alarm Output
                    </h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Camera Username</label>
                                <input type="text" name="camera_user" class="form-control"
                                       value="<?= esc($camera['camera_user'] ?? 'admin') ?>">
                                <small class="form-text text-muted">Login for ISAPI alarm trigger.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Camera Password</label>
                                <input type="password" name="camera_pass" class="form-control"
                                       value="<?= esc($camera['camera_pass'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Alarm Channel</label>
                                <input type="number" name="alarm_output_channel" class="form-control"
                                       min="1" max="4"
                                       value="<?= (int)($camera['alarm_output_channel'] ?? 1) ?>">
                                <small class="form-text text-muted">IO output port (1–4).</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Duration (sec)</label>
                                <input type="number" name="alarm_duration" class="form-control"
                                       min="1" max="60"
                                       value="<?= (int)($camera['alarm_duration'] ?? 5) ?>">
                                <small class="form-text text-muted">How long relay stays active.</small>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/access/cameras" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-dark">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test fire -->
        <div class="card shadow-sm border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-vial mr-2"></i>Test Plate Lookup</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted">Simulate a plate check without needing a real camera event.</p>
                <form method="post" action="/access/check">
                    <?= csrf_field() ?>
                    <input type="hidden" name="api_token" value="<?= esc(env('access.api_token', '')) ?>">
                    <input type="hidden" name="location" value="<?= esc($camera['name']) ?>">
                    <div class="input-group input-group-sm">
                        <input type="text" name="registration" class="form-control text-uppercase"
                               placeholder="e.g. ABC123GP" style="letter-spacing:2px;">
                        <div class="input-group-append">
                            <button class="btn btn-info" type="submit">Check</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function copyUrl() {
    const el = document.getElementById('listenerUrl');
    el.select();
    document.execCommand('copy');
    alert('URL copied to clipboard!');
}

(function () {
    fetch('/access/cameras/ping/<?= $camera['id'] ?>')
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('camera-status');
            if (data.online) {
                el.className = 'badge badge-success px-3 py-2';
                el.innerHTML = '<i class="fas fa-circle mr-1"></i> Online (' + data.ip + ')';
            } else {
                el.className = 'badge badge-danger px-3 py-2';
                el.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Offline' + (data.reason ? ' — ' + data.reason : '');
            }
        })
        .catch(() => {
            const el = document.getElementById('camera-status');
            el.className = 'badge badge-warning px-3 py-2';
            el.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Check failed';
        });
})();

function checkCameraStatus() {
    fetch('/access/cameras/ping/<?= $camera['id'] ?>')
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('camera-status');
            if (data.online) {
                el.className = 'badge badge-success px-3 py-2';
                el.innerHTML = '<i class="fas fa-circle mr-1"></i> Online (' + data.ip + ')';
            } else {
                el.className = 'badge badge-danger px-3 py-2';
                el.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Offline' + (data.reason ? ' — ' + data.reason : '');
            }
        })
        .catch(() => {
            const el = document.getElementById('camera-status');
            el.className = 'badge badge-warning px-3 py-2';
            el.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i> Check failed';
        });
}
// Run immediately on load, then every 30 seconds
checkCameraStatus();
setInterval(checkCameraStatus, 30000);
</script>

<?= $this->endSection() ?>