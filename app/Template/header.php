<?php $_title = $this->render('header/title', array(
    
    'project' => isset($project) ? $project : null,
    'task' => isset($task) ? $task : null,
    'description' => isset($description) ? $description : null,
    'title' => $title,
)) ?>

<?php $_top_right_corner = implode('&nbsp;', array(
        $this->render('header/user_notifications'),
        $this->render('header/creation_dropdown'),
        $this->render('header/user_dropdown')
    )) ?>

<header>
    <div class="title-container">
        <?= $_title ?>
    </div>
    <div class="board-selector-container">
        <?php if (! empty($board_selector)): ?>
            <?= $this->render('header/board_selector', array('board_selector' => $board_selector)) ?>
        <?php endif ?>
    </div>
    <div class="menus-container">
        <?= $_top_right_corner ?>
    </div>
    
    <div class="voice-command-container js-voice-commands">
        <button id="startVoiceBtn" class="voice-btn" title="<?= t('Start Voice Command') ?>">
            <i class="fa fa-microphone"></i>
        </button>
        <button id="stopVoiceBtn" class="voice-btn hidden" title="<?= t('Stop Voice Command') ?>">
            <i class="fa fa-microphone-slash"></i>
        </button>
        <div id="voiceStatus" class="voice-status hidden"></div>
    </div>
</header>

<?= $this->asset->colorCss() ?>
<?= $this->asset->css('assets/css/vendor.min.css') ?>
<?php if (! isset($not_editable)): ?>
    <?= $this->asset->css('assets/css/'.$this->user->getTheme().'.min.css') ?>
<?php else: ?>
    <?= $this->asset->css('assets/css/light.min.css') ?>
<?php endif ?>
<?= $this->asset->css('assets/css/print.min.css', true, 'print') ?>
<?= $this->asset->css('assets/css/app.css') ?>
<?= $this->asset->customCss() ?>

<meta name="csrf-token" content="<?= $this->app->getToken()->getReusableCSRFToken() ?>">
