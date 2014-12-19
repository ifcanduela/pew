<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8">
    <title>Pew Application Error</title>
    <style type="text/css">
        html {
            background-color: white;
            margin: 1em;
            border: 1px dashed #999;
        }
        body {
            background-color: #f6f6f6;
            margin: 0;
            padding: 0;
            font: normal 16px/165% arial, helvetica, sans-serif;
        }
        h1.page-title {
            background-color: #333;
            border-bottom: 1px solid #333;
            margin: 0;
            padding: 20px;
            color: #fff;
        }
        .error-body {
            padding: 20px;
        }
        .error-body p {
            margin: 24px 0;
        }
        .error-body .trace-item {
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1 class="page-title"><?= $this->title() ?></h1>
    
    <div class="error-body">
        <?= $this->child() ?>
    </div>
</body>
</html>
