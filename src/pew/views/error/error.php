<h2 class="trace-message"><?= $exception->getMessage() ?></h2>

<p>Thrown at [<em><?= $exception->getFile() ?>:<?= $exception->getLine() ?></em>]</p>

<ol>
    <?php foreach ($exception->getTrace() as $trace): ?>
        <?php if ($trace['function'] === 'exception_error_handler') continue; ?>
        
        <li class="trace-item">
            <?php if (isSet($trace['file'])): ?>
                <span class="trace-filename"><?= $trace['file'] ?></span>:<span class="trace-line-number"><?= $trace['line'] ?></span>
            <?php else: ?>
                <span>(no file)</span>
            <?php endif ?>
            <br>
            <?php if (isSet($trace['class'])): ?>
                <?= $trace['class'] . $trace['type'] ?><strong><?= $trace['function'] ?></strong>()
            <?php else: ?>
                <strong><?= $trace['function'] ?></strong>()
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ol>
