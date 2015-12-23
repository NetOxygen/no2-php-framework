<!DOCTYPE html>
<html lang="<?= h(current_lang()); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="author"    content="Net Oxygen Sàrl (https://netoxygen.ch)">
        <meta name="generator" content="no2(<?php print h(NO2VERSION); ?>)">

        <title><?php print t('The Application Title.'); ?></title>

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- fav icon -->
        <link rel="shortcut icon" typ="image/png" href="<?php print h($router->assets_url('img/favicon.png')); ?>">

        <!-- jQuery -->
        <script src="<?php print h($router->assets_url('js/jquery-2.1.4.min.js')); ?>"></script>

        <!-- underscorejs -->
        <script src="<?php print h($router->assets_url('js/underscore-min.js')); ?>"></script>

        <!-- Twitter Bootstrap -->
        <script src="<?php print h($router->assets_url('js/bootstrap.min.js')); ?>"></script>
        <link href="<?php print h($router->assets_url('css/bootstrap.min.css')); ?>" rel="stylesheet">
        <link href="<?php print h($router->assets_url('css/bootstrap-theme.min.css')); ?>" rel="stylesheet">

        <!-- moment.js -->
        <script src="<?php print h($router->assets_url('js/moment-with-locales.min.js')); ?>"></script>

        <!-- bootstrap-datetimepicker -->
        <?php // NOTE: don't update to 4.x until https://github.com/Eonasdan/bootstrap-datetimepicker/issues/740 is fixed. ?>
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
        window.lang = <?= json_encode(current_lang()); ?>;
        jQuery(document).ready(function ($) {
            // bootstrap tooltip
            $('[rel=tooltip]').tooltip();
            // momentjs
            moment().format();
            moment.locale(window.lang);
            // bootstrap-datetimepicker
            $('.datepickerize').datetimepicker({
                pickTime: false,
                language: window.lang,
                weekStart: 1,
                format: <?= json_encode(strftime2momentjs(AppConfig::get('l10n.strftime_date_format'))); ?>
            });
            $('.datetimepickerize').datetimepicker({
                pickTime: true,
                language: window.lang,
                weekStart: 1,
                format: <?= json_encode(strftime2momentjs(AppConfig::get('l10n.strftime_datetime_format'))); ?>
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
