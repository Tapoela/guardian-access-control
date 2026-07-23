<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h1 class="mb-1"><?= esc($pageTitle ?? 'Guardian') ?></h1>

        <?php if (!empty($pageDescription)): ?>

            <small class="text-muted">
                <?= esc($pageDescription) ?>
            </small>

        <?php endif; ?>

    </div>

    <div>

        <?= $this->renderSection('pageActions') ?>

    </div>

</div>