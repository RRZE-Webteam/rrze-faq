<?php
/**
 * The is the template for displaying the foot
 *
 *
 * @package WordPress
 * @subpackage FAU
 * @since FAU 1.0
*/

use RRZE\FAQ\Layout;

$thisThemeGroup = Layout::getThemeGroup();

if ($thisThemeGroup == 'fauthemes') { ?>
    </main>
</div>
</div>
</div>
</div>
<?php get_template_part('template-parts/footer', 'social');
} elseif($thisThemeGroup == 'rrzethemes') { ?>

    </div>
</div>

<?php }else{ ?>
</main>
</div>
<?php }

get_footer();