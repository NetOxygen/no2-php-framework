<!DOCTYPE html>
<html lang="<?= h(current_lang()); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author"    content="Net Oxygen Sàrl (https://netoxygen.ch)">
        <meta name="generator" content="no2(<?php print h(NO2VERSION); ?>)">
        <!-- à-la-Rails CSRF meta tags, so stuff like jquery.form can get it -->
        <meta name="csrf-param" content="_csrf">
        <meta name="csrf-token" content="<?= csrf_token(); ?>">

        <title><?php print ht('app.title'); ?></title>

        <!-- fav icon -->
        <link rel="shortcut icon" type="image/png" href="<?php print h($router->assets_url('img/favicon.png')); ?>">

        <!-- jQuery -->
        <script src="<?php print h($router->assets_url('js/jquery-3.1.0.min.js')); ?>"></script>
        <script src="<?php print h($router->assets_url('js/jquery-migrate-3.0.0.min.js')); ?>"></script>

        <!-- underscorejs -->
        <script src="<?php print h($router->assets_url('js/underscore-min.js')); ?>"></script>

        <!-- Twitter Bootstrap -->
        <script src="<?php print h($router->assets_url('js/bootstrap.min.js')); ?>"></script>
        <link href="<?php print h($router->assets_url('css/bootstrap.min.css')); ?>" rel="stylesheet">
        <link href="<?php print h($router->assets_url('css/bootstrap-theme.min.css')); ?>" rel="stylesheet">

        <!-- moment.js -->
        <script src="<?php print h($router->assets_url('js/moment-with-locales.min.js')); ?>"></script>

        <!-- bootstrap-datetimepicker -->
        <script src="<?php print h($router->assets_url('js/bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js')); ?>"></script>
        <link href=<?php print h($router->assets_url('js/bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css')); ?> rel="stylesheet">

        <!-- styles -->
        <style type="text/css">
            body {
                font-family: Helvetica, Arial, sans-serif;
                padding-top: 60px;
            }
            .sidebar-nav {
                padding: 9px 0;
            }
        </style>

        <script>
        jQuery(document).ready(function ($) {
            var lang = <?= html_json_encode(current_lang()); ?>;
            // bootstrap tooltip
            $('[rel=tooltip]').tooltip();
            // momentjs
            moment().format();
            moment.locale(lang);
            // bootstrap-datetimepicker
            $('.datepickerize').datetimepicker({
                locale: moment.locale(),
                format: <?= html_json_encode(strftime2momentjs(AppConfig::get('l10n.strftime_date_format'))); ?>
            });
            $('.datetimepickerize').datetimepicker({
                locale: moment.locale(),
                format: <?= html_json_encode(strftime2momentjs(AppConfig::get('l10n.strftime_datetime_format'))); ?>
            });
        });
        </script>
    </head>

    <body>
        <?php $view->render('header'); ?>

        <div class="container">
            <section id="content">
                <?php $view->render('messages'); ?>
                <?php $view->render('content'); ?>
            </section>

            <hr>

            <?php $view->render('footer'); ?>
        </div>
    </body>
</html>
