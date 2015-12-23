<form action="<?php print h($router->login_url()); ?>" method="POST" role="form">
    <input type="hidden" name="_csrf" value="<?= csrf_token(); ?>">

    <h1><?php print http_host() . ': ' . t('User login'); ?></h1>

    <div class="form-group">
        <label for="login-form-email"><?php print t('email'); ?></label>
        <input id="login-form-email" class="form-control" type="email" name="email" required>
        <label for="login-form-password"><?php print t('Password'); ?></label>
        <div class="input-group">
            <input id="login-form-password" class="form-control" type="password" name="cleartext" required>
            <span class="input-group-btn">
                <button type="submit" class="btn btn-primary">
                    <?php print t('Login'); ?>
                    <span class="glyphicon glyphicon-chevron-right"></span>
                </button>
            </span>
        </div>
    </div>
</form>
