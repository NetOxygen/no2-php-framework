<form id="disable-or-enable-user-form" action="<?= h($this->user->is_active ? $router->disable_user_url($this->user) : $router->enable_user_url($this->user)); ?>" method="POST" class="hidden">
    <input type="hidden" name="_csrf" value="<?= csrf_token(); ?>">
</form>

<div class="clearfix">
    <div class="btn-group pull-right">
        <?php if (current_user()->id != $this->user->id): ?>
            <a class="btn btn-default" href="mailto:<?php print h($this->user->email); ?>">
                <?php print t('E-Mail'); ?>
                <span class="glyphicon glyphicon-envelope"></span>
            </a>
        <?php endif; ?>
        <?php if (current_user()->can('update', $this->user)): ?>
            <a class="btn btn-default" href="<?php print h($router->edit_user_url($this->user)); ?>">
                <?php print t('edit'); ?>
                <span class="glyphicon glyphicon-pencil"></span>
            </a>
        <?php endif; ?>
        <?php if ($this->user->is_active && current_user()->can('disable', $this->user)): ?>
            <button class="btn btn-warning" type="submit" form="disable-or-enable-user-form">
                <?php print t('disable'); ?>
                <span class="glyphicon glyphicon-pause"></span>
            </button>
        <?php endif; ?>
        <?php if (!$this->user->is_active && current_user()->can('enable', $this->user)): ?>
            <button class="btn btn-warning" type="submit" form="disable-or-enable-user-form">
                <?php print t('enable'); ?>
                <span class="glyphicon glyphicon-play"></span>
            </button>
        <?php endif; ?>
        <?php if (current_user()->can('destroy', $this->user)): ?>
            <a class="btn btn-danger" href="<?php print h($router->destroy_user_url($this->user)); ?>">
                <?php print t('destroy'); ?>
                <span class="glyphicon glyphicon-trash"></span>
            </a>
        <?php endif; ?>
    </div>

    <article class="user">
        <h1>
            <?php print h($this->user->gender); ?>
            <?php print h($this->user->fullname); ?>
            <small><?php print h($this->user->email); ?></small>
        </h1>

        <dl class="user-meta dl-horizontal">
            <dt><?= t('created at'); ?></dt>
            <dd>
                <time datetime="<?php print $this->user->created_at->format(DateTime::W3C); ?>">
                    <?php print date_to_s($this->user->created_at); ?>
                </time>
            </dd>
            <?php if ($this->user->updated_at != $this->user->created_at): ?>
                <dt><?= t('updated at'); ?></dt>
                <dd>
                    <time datetime="<?php print $this->user->updated_at->format(DateTime::W3C); ?>">
                        <?php print date_to_s($this->user->updated_at); ?>
                    </time>
                </dd>
            <?php endif; ?>
            <dt><?= t('status'); ?></dt>
            <dd><?= t($this->user->is_active ? 'active' : 'inactive'); ?></dd>
            <dt><?= t('role'); ?></dt>
            <dd><?= h(User::roles($this->user->role)); ?></dd>
            <dt><?= t('description'); ?></dt>
            <dd>
                <?php print wysiwyg($this->user->description); ?>
            </dd>
        </dl>
    </article>

    <?php if (current_user()->can('read-abilities', $this->user)): ?>
        <hr>
        <div class="panel panel-default">
            <div class="panel-heading"><?= t('Abilities'); ?></div>
            <ul class="list-group">
                <?php foreach ($this->user->abilities() as $resource => $desc): ?>
                    <?php foreach ($desc as $action => $allowed): ?>
                        <li class="list-group-item">
                            <?= h($this->user->fullname) ?> can
                            <strong><?= $action ?></strong>
                            <em><?= h($allowed); ?></em>
                            <?= $resource ?>
                        </li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
