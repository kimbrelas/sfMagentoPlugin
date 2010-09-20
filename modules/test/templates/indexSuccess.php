<h1>Processed Items</h1>
<ul>
  <?php foreach($data as $collections): ?>
    <?php foreach($collections as $item): ?>
      <li><?php echo $item->getName() ?></li>
    <?php endforeach; ?>
  <?php endforeach; ?>
</ul>