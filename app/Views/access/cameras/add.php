<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <h4><i class="fas fa-video text-dark mr-2"></i>Add Camera</h4>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0"><i class="fas fa-camera mr-2"></i>New ANPR Camera</h6>
            </div>
            <div class="card-body">
                <form method="post" action="/access/cameras/add">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label><strong>Camera Name <span class="text-danger">*</span></strong></label>
                        <input type="text" name="name" class="form-control" required
                               placeholder="e.g. Main Gate Entry">
                    </div>

                    <div class="form-group">
                        <label><strong>Location Description</strong></label>
                        <input type="text" name="location" class="form-control"
                               placeholder="e.g. North entrance boom gate">
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label><strong>Camera IP Address</strong></label>
                                <input type="text" name="ip_address" class="form-control"
                                       placeholder="192.168.1.64">
                                <small class="form-text text-muted">Used to open the boom gate via ISAPI.</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><strong>Door/Gate Channel</strong></label>
                                <input type="number" name="channel" class="form-control"
                                       value="1" min="1" max="8">
                                <small class="form-text text-muted">Relay channel #.</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><strong>Notes</strong></label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group d-flex align-items-center gap-4">

                        <div class="custom-control custom-switch mr-4">
                            <input type="checkbox"
                                class="custom-control-input"
                                id="is_active"
                                name="is_active"
                                value="1"
                                checked>

                            <label class="custom-control-label" for="is_active">
                                Camera Active
                            </label>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                class="custom-control-input"
                                id="gate_trigger"
                                name="gate_trigger"
                                value="1">

                            <label class="custom-control-label" for="gate_trigger">
                                Enable Boom Gate Control
                            </label>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                class="custom-control-input"
                                id="boom_live_view"
                                name="boom_live_view"
                                value="1">

                            <label class="custom-control-label" for="boom_live_view">
                                Show this camera as Boom Live View
                            </label>
                        </div>

                    </div>
                    
                    <div class="alert alert-info small mb-3">
                        <i class="fas fa-key mr-1"></i>
                        A unique secret token will be generated automatically.
                        The full listener URL will be shown after saving.
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="/access/cameras" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-dark">
                            <i class="fas fa-save mr-1"></i> Save Camera
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
