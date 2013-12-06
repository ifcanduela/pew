<h1>Sign Up</h1>

<?php foreach (flash() as $class => $message): ?>
    <p class="alert <?= $class ?>"><?= $message ?></p>    
<?php endforeach ?>

<form action="<?= url(here()) ?>" method="post" id="form-users-signup">
    <div>
        <label for="username">Username:</label>
        <input type="text" name="username" id="form-users-signup-username">
    </div>

    <div>
        <label for="password">Password:</label>
        <input type="password" name="password" id="form-users-signup-password">
    </div>

    <div>
        <label for="password_confirm">Confirm Password:</label>
        <input type="password" name="password_confirm" id="form-users-signup-password-confirm">
    </div>

    <div>
        <label for="email">E-mail address:</label>
        <input type="email" name="email" id="form-users-signup-email">
    </div>
    
    <div>
        <button type="submit" id="form-users-signup-submit">Sign Up</button>
    </div>
</form>
