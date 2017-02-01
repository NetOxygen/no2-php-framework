<?php if (current_user()->can('create', 'User')): ?>
    <div class="pull-right btn-group">
        <a class="btn btn-primary" href="<?php print h($router->new_user_url()); ?>">
            <span class="glyphicon glyphicon-plus"></span>
            <?php print ht('admin.user.index.add_a_new_user'); ?>
        </a>
    </div>
<?php endif; ?>

<h1><?php print ht('admin.user.index.title'); ?></h1>

<table class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th class="text-muted"><?php print ht('admin.user.index.heading.id'); ?></th>
            <th><?php print ht('admin.user.index.heading.fullname'); ?></th>
            <th><?php print ht('admin.user.index.heading.email'); ?></th>
            <th><?php print ht('admin.user.index.heading.role'); ?></th>
            <th><?php print ht('admin.user.index.heading.created_at'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($this->users as $user): ?>
            <?php if (!current_user()->can('read', $user)) continue; ?>
            <tr class="<?= h($user->is_active ? '' : 'warning'); ?>">
                <td class="text-muted"><?php print h($user->id); ?></td>
                <td>
                    <a href="<?php print h($router->user_url($user)); ?>"><?php print h($user->fullname); ?></a>
                </td>
                <td>
                    <a href="mailto:<?php print h($user->email); ?>"><?php print h($user->email); ?></a>
                </td>
                <td>
                    <?php print h(User::roles($user->role)); ?>
                </td>
                <td>
                    <time datetime="<?php print $user->created_at->format(DateTime::W3C); ?>">
                        <?php print datetime_to_s($user->created_at); ?>
                    </time>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
