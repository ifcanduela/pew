<?php
$this->layout("layout");
?>
<div>
    <h2>This is <?= __FILE__ ?></h2>

    <article>
        <?= $this->content() ?>
    </article>
</div>
