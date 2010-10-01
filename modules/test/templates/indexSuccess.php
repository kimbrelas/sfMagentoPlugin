<h1>Processed Items</h1>
<ul>
  <?php foreach($data as $class => $objects): ?>
    <?php foreach($objects as $key => $obj): ?>
      <li><?php echo $key ?> = <?php echo $obj->getName() ?></li>
    <?php endforeach; ?>
  <?php endforeach; ?>
</ul>