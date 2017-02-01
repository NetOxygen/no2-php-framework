<form id="disable-or-enable-user-form" action="<?= h($this->user->is_active ? $router->disable_user_url($this->user) : $router->enable_user_url($this->user)); ?>" method="POST" class="hidden">
    <input type="hidden" name="_csrf" value="<?= csrf_token(); ?>">
</form>

<div class="clearfix">
    <div class="btn-group pull-right actions">
        <?php if (current_user()->id != $this->user->id): ?>
            <a class="btn btn-default" href="mailto:<?php print h($this->user->email); ?>">
                <?php print ht('admin.user.show.actions.send_an_email_btn'); ?>
                <span class="glyphicon glyphicon-envelope"></span>
            </a>
        <?php endif; ?>
        <?php if (current_user()->can('update', $this->user)): ?>
            <a class="btn btn-default" href="<?php print h($router->edit_user_url($this->user)); ?>">
                <?php print ht('admin.user.show.actions.edit_btn'); ?>
                <span class="glyphicon glyphicon-pencil"></span>
            </a>
        <?php endif; ?>
        <?php if ($this->user->is_active && current_user()->can('disable', $this->user)): ?>
            <button class="btn btn-warning" type="submit" form="disable-or-enable-user-form">
                <?php print ht('admin.user.show.actions.disable_btn'); ?>
                <span class="glyphicon glyphicon-pause"></span>
            </button>
        <?php endif; ?>
        <?php if (!$this->user->is_active && current_user()->can('enable', $this->user)): ?>
            <button class="btn btn-warning" type="submit" form="disable-or-enable-user-form">
                <?php print ht('admin.user.show.actions.enable_btn'); ?>
                <span class="glyphicon glyphicon-play"></span>
            </button>
        <?php endif; ?>
        <?php if (current_user()->can('destroy', $this->user)): ?>
            <a class="btn btn-danger" href="<?php print h($router->destroy_user_url($this->user)); ?>">
                <?php print ht('admin.user.show.actions.destroy_btn'); ?>
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
            <dt><?= ht('admin.user.show.created_at'); ?></dt>
            <dd>
                <time datetime="<?php print $this->user->created_at->format(DateTime::W3C); ?>">
                    <?php print date_to_s($this->user->created_at); ?>
                </time>
            </dd>
            <?php if ($this->user->updated_at != $this->user->created_at): ?>
                <dt><?= ht('admin.user.show.updated_at'); ?></dt>
                <dd>
                    <time datetime="<?php print $this->user->updated_at->format(DateTime::W3C); ?>">
                        <?php print date_to_s($this->user->updated_at); ?>
                    </time>
                </dd>
            <?php endif; ?>
            <dt><?= ht('user.fields.status'); ?></dt>
            <dd><?= ht($this->user->is_active ? 'user.status.active' : 'user.status.inactive'); ?></dd>
            <dt><?= ht('user.fields.role'); ?></dt>
            <dd><?= h(User::roles($this->user->role)); ?></dd>
            <dt><?= ht('user.fields.description'); ?></dt>
            <dd>
                <?php print wysiwyg($this->user->description); ?>
            </dd>
        </dl>
    </article>

    <?php if (current_user()->can('read-abilities', $this->user)): ?>
        <hr>
        <div class="panel panel-default">
            <div class="panel-heading"><?= ht('admin.user.show.abilities'); ?></div>
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
