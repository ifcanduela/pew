<div>
    <h1>The Error Four Oh Four</h1>
    
    <p>Sorry, the page you were looking for does not exist. Try one of the following:</p>
    
    <ul>
        <li><a href="<?= url(here())  ?>">Try again</a>: If you think it might help.</li>
        <li><a href="<?= url() ?>">Go home</a>: And start from the beginning.</li>
    </ul>
    
    <p>In compensation, a picture of a cat:</p>
    
    <?php $cats = array('cat', 'cats', 'kitten', 'kittens', 'kitteh');
          $cat = $cats[array_rand($cats)]; ?>
    
    <img src="http://<?= $cat ?>.jpg.to" alt="This cat">
</div>
