<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8">
    <title>Pew Error :: <?php echo $error_title; ?></title>
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
            font: normal 16px/165% 'trebuchet ms', helvetica, sans-serif;
        }
        h1 {
            background-color: #fff;
            border-bottom: 1px solid #999;
            margin: 0;
            padding: 0.4em;
            color: #643;
        }
        p {
            margin: 0;
            padding: 2em;
        }
    </style>
</head>

<body>
    
    <h1><?php echo $error_title; ?></h1>
    <p><?php echo $error_text; ?></p>
    
</body>
</html>
