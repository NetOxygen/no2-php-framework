<div class="clearfix">
    <form action="<?php print h($router->destroy_user_url($this->user)); ?>" method="POST">
        <input type="hidden" name="_csrf" value="<?= csrf_token(); ?>">
        <h1><?php print ht('admin.user.destroy.confirmation_title'); ?></h1>
        <p class="text-danger">
            <span class="glyphicon glyphicon-warning-sign"></span>
            <strong><?php print ht('admin.user.destroy.are_you_sure'); ?></strong><br>
            <?php print h($this->user->fullname); ?>
            (<?php print h($this->user->email); ?>)
        </p>

        <div class="pull-right">
            <a class="btn btn-default" href="<?php print h($router->user_url($this->user)); ?>">
                <span class="glyphicon glyphicon-chevron-left"></span>
                <?php print ht('admin.user.destroy.no_i_am_not_sure'); ?>
            </a>
            <button class="btn btn-danger" type="submit">
                <?php print ht('admin.user.destroy.yes_i_am_sure'); ?>
                <span class="glyphicon glyphicon-trash"></span>
            </button>
        </div>
    </form>
</div>
