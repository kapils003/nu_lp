<div class="wrap">
    <h2>LP Group</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('lp-group-group'); ?>
        <?php @do_settings_fields('lp-group-group'); ?>

        <?php do_settings_sections('lp-group'); ?>

        <?php @submit_button(); ?>
    </form>
</div>