# Pew-Pew-Pew

 > An amateur PHP framework.

Pew-Pew-Pew is a PHP 5.5 framework for simple websites.

I've used this for all my latest personal projects and it's
usable -- although a little bit unreliable -- I break things
on a weekly basis. Use at your own risk or, better yet, don't
use at all.

# Install

    $ git clone https://github.com/ifcanduela/pew-app
    $ cd pew-app
    $ composer install
    $ php -S localhost:8000 -t www

In case you're using PHP 5.4, the only PHP 5.5 functionality in use
are the `password_hash` and `password_verify` functions, and those
are optional and were backported: you can use Composer to require the
[`password_compat`](https://packagist.org/packages/ircmaxell/password-compat)
package.
