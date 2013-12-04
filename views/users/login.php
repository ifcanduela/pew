<h1>Log In</h1>

<?php foreach (flash() as $class => $message): ?>
    <p class="alert <?= $class ?>"><?= $message ?></p>    
<?php endforeach ?>

<form action="<?= url(here()) ?>" method="post" id="form-users-login">
    <div>
        <label for="username">Username:</label>
        <input type="text" name="username" id="form-users-login-username">
    </div>
    
    <div>
        <label for="password">Password:</label>
        <input type="password" name="password" id="form-users-login-password">
    </div>

    <div>
        <button type="submit" id="form-users-login-submit">Log In</button>
    </div>
</form>
