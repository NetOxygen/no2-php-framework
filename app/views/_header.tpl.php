<header>
    <?php if (current_user()->can('see_page_header')): ?>
        <div class="navbar navbar-default navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only"><?php print t('navigation.toggle'); ?></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <a class="navbar-brand" href="<?php print h($router->root_url()); ?>"><?php print t('app.title'); ?></a>
                </div>
                <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <?php if (current_user()->can('read', 'User')): ?>
                            <li class="active">
                                <a href="<?php print h($router->users_url()); ?>"><?php print t('navigation.navbar.users'); ?></a>
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
                                            <?php print t('navigation.navbar.me'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if (current_user()->can('logout')): ?>
                                    <li>
                                        <a href="<?php print h($router->logout_url()); ?>">
                                            <?php print t('navigation.navbar.logout'); ?>
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
