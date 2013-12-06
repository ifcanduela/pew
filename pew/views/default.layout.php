<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <base href="<?= url() ?>">
    <title><?= $title ?> | <?= pew('app_title') ?></title>
    <link rel="stylesheet" href="www/css/default.css">
</head>

<body>
    
    <header>
        <h1><a href="<?= url() ?>"><?= $title ?></a></h1>
    </header>

    <div id="main">
        <?= $output ?>
    </div>

    <footer>
        <p>2011-2013 ifcanduela | Powered by <a href="https://github.com/ifcanduela/Pew-Pew-Pew">Pew-Pew-Pew</a> <small><?= pew('version_string') ?></small></p>
    </footer>
    
    <script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
    <script type="text/javascript" src="www/js/app.js"></script>
    
</body>
</html>
