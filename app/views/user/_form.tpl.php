<h1>
    <?php if ($this->user->is_new_record()): ?>
        <?php print ht('admin.user.form.add_a_new_user'); ?>
    <?php else: ?>
        <?php print ht('admin.user.form.edit_user', ['%name%' => $this->user->fullname]); ?>
    <?php endif; ?>
</h1>

<div class="clearfix">
    <form id="user-form" class="form-horizontal" action="<?php print h($this->user->is_new_record() ? $router->create_user_url() : $router->update_user_url($this->user)); ?>" method="POST">
        <input type="hidden" name="_csrf" value="<?= csrf_token(); ?>">
        <fieldset>
            <legend>
                <?php print ht('admin.user.form.user_informations_legend'); ?>
            </legend>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-gender"><?php print ht('user.fields.gender'); ?></label>
                <div class="col-lg-10">
                    <select id="user-gender" name="user[gender]" class="form-control">
                        <option value="F" <?php print ($this->user->gender === 'F' ? 'selected' : ''); ?>>
                            <?php print ht('user.gender.F'); ?>
                        </option>
                        <option value="M" <?php print ($this->user->gender === 'M' ? 'selected' : ''); ?>>
                            <?php print ht('user.gender.M'); ?>
                        </option>
                        <option value="?" <?php print ($this->user->gender === '?' ? 'selected' : ''); ?>>
                            <?php print ht('user.gender.?'); ?>
                        </option>
                    </select>
                    <?php print errors_for($this->user, 'gender'); ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-fullname"><?php print ht('user.fields.fullname'); ?></label>
                <div class="col-lg-10">
                    <input id="user-fullname" class="form-control" type="text" name="user[fullname]" required value="<?php print h($this->user->fullname); ?>" placeholder="<?php print ht('admin.user.form.fullname_placeholder'); ?>">
                    <?php print errors_for($this->user, 'fullname'); ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-email"><?php print ht('user.fields.email'); ?></label>
                <div class="col-lg-10">
                    <input id="user-email" class="form-control" type="email" name="user[email]" required value="<?php print h($this->user->email); ?>">
                    <?php print errors_for($this->user, 'email'); ?>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-2 control-label" for="user-password"><?php print ht('user.fields.password'); ?></label>
                <div class="col-lg-10">
                    <input id="user-password" class="form-control" type="password" name="new-password" <?php print ($this->user->is_new_record() ? 'required' : ''); ?> value="" placeholder="<?php print ht('admin.user.form.password_placeholder'); ?>">
                </div>
                <label class="col-lg-2 control-label" for="user-password_confirmation"><?php print ht('admin.user.form.password_confirmation'); ?></label>
                <div class="col-lg-10" style="margin-top:6px;">
                    <input id="user-password_confirmation" class="form-control" type="password" name="new-password-confirmation" <?php print ($this->user->is_new_record() ? 'required' : ''); ?> value="" placeholder="<?php print ht('admin.user.form.password_confirmation_placeholder'); ?>">
                    <span class="password-confirmation-status glyphicon glyphicon-ok" data-alt="OK"></span>
                    <span class="password-confirmation-status glyphicon glyphicon-remove" data-alt="mismatch"></span>
                </div>
            </div>

            <?php if (current_user()->can('pick-role', $this->user)): ?>
                <div class="form-group">
                    <label class="col-lg-2 control-label" for="user-role"><?php print ht('user.fields.role'); ?></label>
                    <div class="col-lg-10">
                        <select id="user-role" class="form-control" name="user[role]" required>
                            <?php if ($this->user->is_new_record()): ?>
                                <option value=""><?php print ht('admin.user.form.pick_a_role'); ?></option>
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
                <label class="col-lg-2 control-label" for="user-description"><?php print ht('user.fields.description'); ?></label>
                <div class="col-lg-10">
                    <textarea id="user-description" class="form-control" name="user[description]" class="wysiwygize"><?php print wysiwyg($this->user->description); ?></textarea>
                    <?php print errors_for($this->user, 'description'); ?>
                </div>
            </div>

            <div class="pull-right">
                <a href="<?php print h($this->user->is_new_record() ? $router->users_url() : $router->user_url($this->user)); ?>" class="btn btn-default">
                    <span class="glyphicon glyphicon-arrow-left"></span>
                    <?php print ht('admin.user.form.cancel_btn'); ?>
                </a>

                <?php if ($this->user->is_new_record() && current_user()->can('create', $this->user) || !$this->user->is_new_record() && current_user()->can('update', $this->user)): ?>
                    <button class="btn btn-primary" type="submit">
                        <?php print t('admin.user.form.save_btn'); ?>
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
                        alert("<?php print ht('admin.user.form.password_confirmation_missmatch'); ?>");
                        event.preventDefault(); // don't submit the form.
                    }
                });
            });
            </script>
        </fieldset>
    </form>
</div>
