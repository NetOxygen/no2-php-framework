<h1>
    <?php if ($this->user->is_new_record()): ?>
        <?php print t('add a new user'); ?>
    <?php else: ?>
        <?php print h(sprintf(t('edit %s (%s)'), $this->user->fullname, $this->user->email)); ?>
    <?php endif; ?>
</h1>

<div class="clearfix">
    <form id="user-form" class="form-horizontal" action="<?php print h($this->user->is_new_record() ? $router->create_user_url() : $router->update_user_url($this->user)); ?>" method="POST">
        <input type="hidden" name="_csrf" value="<?= csrf_token(); ?>">
        <fieldset>
            <legend>
                <?php print t('user informations.'); ?>
            </legend>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-gender"><?php print t('Gender'); ?></label>
                <div class="col-lg-10">
                    <select id="user-gender" name="user[gender]" class="form-control">
                        <option value="F" <?php print ($this->user->gender === 'F' ? 'selected' : ''); ?>>
                            <?php print t('Miss'); ?>
                        </option>
                        <option value="M" <?php print ($this->user->gender === 'M' ? 'selected' : ''); ?>>
                            <?php print t('Mister'); ?>
                        </option>
                        <option value="?" <?php print ($this->user->gender === '?' ? 'selected' : ''); ?>>
                            <?php print t('?'); ?>
                        </option>
                    </select>
                    <?php print errors_for($this->user, 'gender'); ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-fullname"><?php print t('Fullname'); ?></label>
                <div class="col-lg-10">
                    <input id="user-fullname" class="form-control" type="text" name="user[fullname]" required value="<?php print h($this->user->fullname); ?>" placeholder="<?php print t('Firstname Lastname'); ?>">
                    <?php print errors_for($this->user, 'fullname'); ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-email"><?php print t('Email'); ?></label>
                <div class="col-lg-10">
                    <input id="user-email" class="form-control" type="email" name="user[email]" required value="<?php print h($this->user->email); ?>">
                    <?php print errors_for($this->user, 'email'); ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-password"><?php print t('Password'); ?></label>
                <div class="col-lg-10">
                    <input id="user-password" class="form-control" type="password" name="new-password" <?php print ($this->user->is_new_record() ? 'required' : ''); ?> value="" placeholder="<?php print t('New password'); ?>">
                </div>
                <label class="col-lg-2 control-label" for="user-password_confirmation"><?php print t('Password (confirmation)'); ?></label>
                <div class="col-lg-10" style="margin-top:6px;">
                    <input id="user-password_confirmation" class="form-control" type="password" name="new-password-confirmation" <?php print ($this->user->is_new_record() ? 'required' : ''); ?> value="" placeholder="<?php print t('Confirm the new password'); ?>">
                    <span class="password-confirmation-status glyphicon glyphicon-ok" data-alt="OK"></span>
                    <span class="password-confirmation-status glyphicon glyphicon-remove" data-alt="mismatch"></span>
                </div>
            </div>

            <?php if (current_user()->can('pick-role', $this->user)): ?>
                <div class="form-group">
                    <label class="col-lg-2 control-label" for="user-role"><?php print t('Role'); ?></label>
                    <div class="col-lg-10">
                        <select id="user-role" class="form-control" name="user[role]" required>
                            <?php if ($this->user->is_new_record()): ?>
                                <option value=""><?php print t("Choose a role for this user."); ?></option>
                            <?php endif; ?>
                            <?php foreach(User::roles() as $role_key => $role_to_s): ?>
                                <option value="<?php print h($role_key); ?>" <?php print ($this->user->role === $role_key ? 'selected' : ''); ?>>
                                    <?php print h($role_to_s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php print errors_for($this->user, 'role'); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-description"><?php print t('Description'); ?></label>
                <div class="col-lg-10">
                    <textarea id="user-description" class="form-control" name="user[description]" class="wysiwygize"><?php print wysiwyg($this->user->description); ?></textarea>
                    <?php print errors_for($this->user, 'description'); ?>
                </div>
            </div>

            <div class="pull-right">
                <a href="<?php print h($this->user->is_new_record() ? $router->users_url() : $router->user_url($this->user)); ?>" class="btn btn-default">
                    <span class="glyphicon glyphicon-arrow-left"></span>
                    <?php print t('cancel'); ?>
                </a>

                <?php if ($this->user->is_new_record() && current_user()->can('create', $this->user) || !$this->user->is_new_record() && current_user()->can('update', $this->user)): ?>
                    <button class="btn btn-primary" type="submit">
                        <?php print t('save'); ?>
                        <span class="glyphicon glyphicon-floppy-disk"></span>
                    </button>
                <?php endif; ?>
            </div>

            <script>
            jQuery(document).ready(function ($) {
                // function to check the password confirmation field.
                var password_confirmation_is_valid = function () {
                    var passwd  = $('#user-password').val();
                    var passwd2 = $('#user-password_confirmation').val();
                    return (passwd == passwd2);
                };

                // when the password or password-confirmation fields change, update the
                // info on password-confirmation matching.
                $('#user-password, #user-password_confirmation').keyup(function () {
                    $('.password-confirmation-status').hide();
                    if (password_confirmation_is_valid()) {
                        if (!$('#user-password').val().length == 0)
                            $('[data-alt=OK]').show();
                    } else {
                        $('[data-alt=mismatch]').show();
                    }
                }).keyup();

                // when the form is submited, check the password confirmation match.
                $('form#user-form').submit(function (event) {
                    if (!password_confirmation_is_valid()) {
                        alert("<?php print t('The password confirmation mismatch'); ?>");
                        event.preventDefault(); // don't submit the form.
                    }
                });
            });
            </script>
        </fieldset>
    </form>
</div>
