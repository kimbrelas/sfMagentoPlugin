<h1>Processed Items</h1>
<ul>
  <?php foreach($data as $key => $item): ?>
    <li><?php echo $key ?> = <?php echo $item->getName() ?></li>
  <?php endforeach; ?>
</ul>