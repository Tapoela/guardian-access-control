<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">
            <?= isset($device) ? 'Edit Hardware Device' : 'Add Hardware Device'; ?>
        </h5>
    </div>

    <div class="card-body">

        <?php if (session()->has('errors')) : ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach (session('errors') as $error) : ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Device Name</label>
                <input type="text"
                       name="DeviceName"
                       class="form-control"
                       value="<?= old('DeviceName', $device['DeviceName'] ?? '') ?>">
            </div>

            <input type="hidden" name="FkSiteId" value="<?= $siteId ?>">

            <div class="col-md-6 mb-3">
                <label class="form-label">Device Type</label>

                <select name="DeviceType" class="form-control">

                    <option value="7065"
                        <?= old('DeviceType', $device['DeviceType'] ?? '') == '7065' ? 'selected' : '' ?>>
                        ICP DAS 7065
                    </option>

                    <option value="ICP DAS 7188E2D"
                        <?= old('DeviceType', $device['DeviceType'] ?? '') == 'ICP DAS 7188E2D' ? 'selected' : '' ?>>
                        ICP DAS 7188E2D
                    </option>

                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">IP Address</label>

                <input type="text"
                       name="IPAddress"
                       class="form-control"
                       value="<?= old('IPAddress', $device['IPAddress'] ?? '') ?>">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Port</label>

                <input type="number"
                       name="TcpPort"
                       class="form-control"
                       value="<?= old('TcpPort', $device['TcpPort'] ?? '10002') ?>">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Address</label>

                <input type="text"
                       name="ModuleAddress"
                       class="form-control"
                       value="<?= old('ModuleAddress', $device['ModuleAddress'] ?? '01') ?>">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Baud Rate</label>

                <input type="number"
                       name="BaudRate"
                       class="form-control"
                       value="<?= old('BaudRate', $device['BaudRate'] ?? '9600') ?>">
            </div>

            <div class="col-md-2 mb-3">
                <label class="form-label">Protocol</label>

                <input type="text"
                       name="Protocol"
                       class="form-control"
                       value="<?= old('Protocol', $device['Protocol'] ?? 'DCON') ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Location</label>

                <input type="text"
                       name="Location"
                       class="form-control"
                       value="<?= old('Location', $device['Location'] ?? '') ?>">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Description</label>

                <input type="text"
                       name="Description"
                       class="form-control"
                       value="<?= old('Description', $device['Description'] ?? '') ?>">
            </div>

        </div>

        <button class="btn btn-primary">
            Save Device
        </button>

        <a href="<?= base_url('hardware/devices') ?>" class="btn btn-secondary">
            Cancel
        </a>

    </div>
</div>