<!DOCTYPE html>
<html lang="<?= $this->app->jsLang() ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="robots" content="noindex,nofollow">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="referrer" content="no-referrer">

        <?php if (isset($board_public_refresh_interval)): ?>
            <meta http-equiv="refresh" content="<?= $board_public_refresh_interval ?>">
        <?php endif ?>

        <?= $this->asset->colorCss() ?>
        <?= $this->asset->css('assets/css/vendor.min.css') ?>
        <?php if (! isset($not_editable)): ?>
            <?= $this->asset->css('assets/css/'.$this->user->getTheme().'.min.css') ?>
        <?php else: ?>
            <?= $this->asset->css('assets/css/light.min.css') ?>
        <?php endif ?>
        <?= $this->asset->css('assets/css/print.min.css', true, 'print') ?>
        <?= $this->asset->customCss() ?>

        <?php if (! isset($not_editable)): ?>
            <?= $this->asset->js('assets/js/vendor.min.js') ?>
            <?= $this->asset->js('assets/js/app.min.js') ?>
            <?= $this->asset->js('assets/js/components/voice-commands.js') ?>
            <?= $this->asset->js('assets/js/core/voice-init.js') ?>
        <?php endif ?>

        <?= $this->hook->asset('css', 'template:layout:css') ?>
        <?= $this->hook->asset('js', 'template:layout:js') ?>

        <link rel="icon" href="<?= $this->url->dir() ?>assets/img/adaptive-favicon.svg" type="image/svg+xml">
        <link rel="icon" type="image/png" href="<?= $this->url->dir() ?>assets/img/favicon.png">
        <link rel="apple-touch-icon" href="<?= $this->url->dir() ?>assets/img/touch-icon-iphone.png">
        <link rel="apple-touch-icon" sizes="72x72" href="<?= $this->url->dir() ?>assets/img/touch-icon-ipad.png">
        <link rel="apple-touch-icon" sizes="114x114" href="<?= $this->url->dir() ?>assets/img/touch-icon-iphone-retina.png">
        <link rel="apple-touch-icon" sizes="144x144" href="<?= $this->url->dir() ?>assets/img/touch-icon-ipad-retina.png">

        <title>
            <?php if (isset($page_title)): ?>
                <?= $this->text->e($page_title) ?>
            <?php elseif (isset($title)): ?>
                <?= $this->text->e($title) ?>
            <?php else: ?>
                Kanboard
            <?php endif ?>
        </title>

        <?= $this->hook->render('template:layout:head') ?>

        <!-- Add this after the CSRF token meta tag -->
        <?php if (isset($project) && isset($project['id'])): ?>
        <meta name="project-id" content="<?= $project['id'] ?>">
        <?php endif ?>
    </head>
    <body data-status-url="<?= $this->url->href('UserAjaxController', 'status') ?>"
          data-login-url="<?= $this->url->href('AuthController', 'login') ?>"
          data-keyboard-shortcut-url="<?= $this->url->href('DocumentationController', 'shortcuts') ?>"
          data-timezone="<?= $this->app->getTimezone() ?>"
          data-js-date-format="<?= $this->app->getJsDateFormat() ?>"
          data-js-time-format="<?= $this->app->getJsTimeFormat() ?>"
    >

    <?php if (isset($no_layout) && $no_layout): ?>
        <?= $this->app->flashMessage() ?>
        <?= $content_for_layout ?>
    <?php else: ?>
        <?= $this->hook->render('template:layout:top') ?>
        <?= $this->render('header', array(
            'title' => $title,
            'description' => isset($description) ? $description : '',
            'board_selector' => isset($board_selector) ? $board_selector : array(),
            'project' => isset($project) ? $project : array(),
        )) ?>
        <section class="page">
            <?= $this->app->flashMessage() ?>
            <?= $content_for_layout ?>
        </section>
        <?= $this->hook->render('template:layout:bottom') ?>
    <?php endif ?>

    <!-- Add this just before the closing body tag -->
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize CSRF token from meta tag
            var metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                KB.token = metaToken.getAttribute('content');
                console.log('CSRF Token initialized:', !!KB.token);
            } else {
                console.error('CSRF token meta tag not found!');
            }
            
            // Initialize voice commands
            console.log('DOM loaded, initializing voice commands...');
            if (typeof KB !== 'undefined') {
                KB.on('dom.ready', function() {
                    console.log('KB ready, rendering components...');
                    KB.render();
                });
            } else {
                console.error('KB object not found!');
            }
        });

        function submitForm() {
            var form = getForm();
            if (form) {
                var url = form.getAttribute('action');
                if (url) {
                    KB.http.postForm(url, form).success(function(response) {
                        // Handle response
                    }).error(function(error) {
                        if (error.message === 'Access Forbidden') {
                            showStatus('Security token expired. Please refresh the page.', true);
                        }
                    });
                }
            }
        }

        function uploadFiles() {
            if (files.length > 0) {
                KB.http.uploadFile(options.url, files[currentFileIndex], options.csrf, onProgress, onComplete, onError, onServerError);
            }
        }

        function submitJson() {
            var url = 'your-json-endpoint.php';
            var command = 'your-command';
            var projectId = 'your-project-id';
            var taskId = 'your-task-id';
            var csrfToken = KB.token;

            KB.http.postJson(url, {
                command: command,
                project_id: projectId,
                task_id: taskId,
                csrf_token: csrfToken
            }).success(function(response) {
                // Handle response
            }).error(function(error) {
                if (error.message === 'Access Forbidden') {
                    showStatus('Security token expired. Please refresh the page.', true);
                }
            });
        }
    </script>
    </body>
</html>
