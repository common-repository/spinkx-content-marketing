<?php
/**
 * This is spinkx analytics dashboard file.
 *
 * @package WordPress.
 * @subpackage spinkx.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$spnx_admin_manage = new spnxAdminManage();
?>
<div class="spnx_wdgt_wrapper"><div class="cssload-loader"></div></div>
<div class="wrap">

	<!-- Main tabs here  -->
	<div id="distributiontabs" style="width:100%;">
		<?php spinkx_header_menu(); ?>
		<div class="wrap-inner" style="margin: 10px auto 10px auto;">
			<div class="tab-contents">
				<div id="content_play_list">
					<?php require SPINKX_CONTENT_ADMIN_VIEW_DIR . 'campaigns/tab-manage-ads.php'; ?>
				</div>
			</div>
		</div>
	</div>
</div>
