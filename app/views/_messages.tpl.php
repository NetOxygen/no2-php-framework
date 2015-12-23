<?php foreach (array('success', 'info', 'warning', 'error') as $level): ?>
    <?php if (array_key_exists($level, $this->flash)): ?>
        <div class="alert <?php print ($level === 'error' ? 'alert-danger' : "alert-$level"); ?> alert-dismissable" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?php if (is_array($this->flash[$level]) && count($this->flash[$level]) > 0): ?>
                <ul>
                <?php foreach ($this->flash[$level] as $msg): ?>
                    <li><?php print $msg; ?></li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?php print $this->flash[$level]; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
