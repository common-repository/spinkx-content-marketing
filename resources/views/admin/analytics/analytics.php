<?php
/**
 * This is spinkx analytics parent file. This file call to tab-analytics.php and spinkx header menu.
 *
 * @package WordPress.
 * @subpackage spinkx.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$spnx_admin_manage  = new spnxAdminManage();
$spost              = array();
$custom_date        = $spnx_admin_manage->spinkx_cont_last_30_days();
$spost['from_date'] = date( 'Y-m-d', $custom_date[0] );
$spost['to_date']   = date( 'Y-m-d', $custom_date[1] );
$todaydate          = $custom_date[2] * 1000;
$custom_js          = ' var client_url = "' . esc_url( SPINKX_CONTENT_PLUGIN_URL ) . '";';
$custom_js         .= 'jQuery(function() { jQuery("#daterange").dateRangePicker({container: "#daterange-picker-container",numberOfMonths: 3,datepickerShowing: true, maxDate: "0D",minDate: new Date(2016, 8, 01),test: true,today: ' . $todaydate . '});
});';
$js_url             = esc_url( SPINKX_CONTENT_DIST_URL . 'js/' );
wp_enqueue_script( 'jquery-dashboard', $js_url . 'analytics.js', array(), true, true );
wp_add_inline_script( 'jquery-dashboard', $custom_js );
?>
<div class="spnx_wdgt_wrapper"><div class="cssload-loader"></div></div>
<div class="wrap">
	<!-- Main tabs here  -->
	<div id="distributiontabs" style="width:100%;">
		<?php spinkx_header_menu(); ?>
		<div class="wrap-inner" style="clear: both;">
			<div class="tab-contents" style="padding-top: 20px;">
				<div id="dashboard"><!--Dashboard -->
					<?php require SPINKX_CONTENT_ADMIN_VIEW_DIR . 'analytics/tab-analytics.php'; ?>
				</div>

			</div>

		</div>
	</div>
</div>
<?php

