<?php if ($modules) { ?>
<aside id="column-left" class="col-sm-4 col-md-4 col-lg-3 hidden-xs <?php if ($route == '' || $route == 'common/home') { ?>hidden-sm<?php } ?>">
  <?php foreach ($modules as $module) { ?>
  <?php echo $module; ?>
  <?php } ?>
</aside>
<?php } ?>
