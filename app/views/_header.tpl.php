<header>
    <?php if (current_user()->can('see_page_header')): ?>
        <div class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only"><?php print t('Toggle navigation'); ?></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <a class="navbar-brand" href="<?php print h($router->root_url()); ?>"><?php print t('No2.App'); ?></a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <?php if (current_user()->can('read', 'User')): ?>
                            <li class="active">
                                <a href="<?php print h($router->users_url()); ?>"><?php print t('Users'); ?></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="nav navbar-nav pull-right">
                        <li class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                <span class="glyphicon glyphicon-user"></span>
                                <?php print h(current_user()->fullname); ?>
                                <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (current_user()->can('read', current_user())): ?>
                                    <li>
                                        <a href="<?php print h($router->user_url(current_user())); ?>">
                                            <?php print t('My profile'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (current_user()->can('logout')): ?>
                                    <li>
                                        <a href="<?php print h($router->logout_url()); ?>">
                                            <?php print t('Logout'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </div>
    <?php endif; ?>
</header>
