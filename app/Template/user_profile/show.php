<div class="form-columns">
    <div class="form-column">
        <form method="post" action="<?= $this->url->href('UserProfileController', 'update', array('user_id' => $user['id'])) ?>" autocomplete="off">
            <?= $this->form->csrf() ?>

            <fieldset>
                <legend><?= t('Profile') ?></legend>
                <?= $this->form->label(t('Username'), 'username') ?>
                <?= $this->form->text('username', $values, $errors, array('required')) ?>

                <?= $this->form->label(t('Name'), 'name') ?>
                <?= $this->form->text('name', $values, $errors) ?>

                <?= $this->form->label(t('Email'), 'email') ?>
                <?= $this->form->email('email', $values, $errors) ?>

                <?= $this->form->label(t('WhatsApp Number'), 'whatsapp_number') ?>
                <?= $this->form->text('whatsapp_number', $values, $errors, array('placeholder' => '+1234567890')) ?>

                <?= $this->form->label(t('Language'), 'language') ?>
                <?= $this->form->select('language', $languages, $values, $errors) ?>

                <?= $this->form->label(t('Timezone'), 'timezone') ?>
                <?= $this->form->select('timezone', $timezones, $values, $errors) ?>

                <?= $this->form->label(t('Notifications'), 'notifications_enabled') ?>
                <?= $this->form->checkbox('notifications_enabled', t('Enable notifications'), 1, $values['notifications_enabled'] == 1) ?>

                <?= $this->form->label(t('Notification types'), 'notification_types') ?>
                <?= $this->form->checkboxes('notification_types', $notification_types, $values['notification_types']) ?>

                <?= $this->form->label(t('WhatsApp Notifications'), 'whatsapp_notifications_enabled') ?>
                <?= $this->form->checkbox('whatsapp_notifications_enabled', t('Enable WhatsApp notifications'), 1, $values['whatsapp_notifications_enabled'] == 1) ?>

                <?= $this->form->label(t('Notification filters'), 'notifications_filter') ?>
                <?= $this->form->select('notifications_filter', $notification_filters, $values, $errors) ?>

                <?= $this->form->label(t('Projects'), 'notification_projects') ?>
                <?= $this->form->checkboxes('notification_projects', $projects, $values['notification_projects']) ?>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-blue"><?= t('Save') ?></button>
            </div>
        </form>
    </div>
</div> 