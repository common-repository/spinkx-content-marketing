<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$spnx_admin_manage = new spnxAdminManage();
$settings          = get_option( SPINKX_CONTENT_LICENSE );
$settings          = maybe_unserialize( $settings );
$todaydate         = 0;
$enddate           = 0;
$startdate         = 0;
$custom_date       = $spnx_admin_manage->spinkx_cont_last_30_days();
$from_date         = spnxHelper::getFilterVar( 'from_date' );
$to_date           = spnxHelper::getFilterVar( 'to_date' );
$custom_js         = '';
if ( $from_date ) {
	$startdate = strtotime( $from_date ) * 1000;

} else {
	$from_date = date( 'Y-m-d', $custom_date[0] );
	$startdate = $custom_date[0] * 1000;
	$todaydate = $custom_date[2] * 1000;
}
if ( $to_date ) {
	$todaydate = strtotime( '+1 Day', strtotime( $to_date ) ) * 1000;
	$enddate   = strtotime( $to_date ) * 1000;
} else {
	$to_date = date( 'Y-m-d', $custom_date[1] );
	$enddate = $custom_date[1] * 1000;
}

$settings['site_id']      = isset( $settings['site_id'] ) ? $settings['site_id'] : 0;
$site_id                  = $settings['site_id'];
$settings['license_code'] = isset( $settings['license_code'] ) ? $settings['license_code'] : '';
$settings['reg_email']    = isset( $settings['reg_email'] ) ? $settings['reg_email'] : '';
$settings['due_date']     = isset( $settings['due_date'] ) ? $settings['due_date'] : '0000-00-00 00:00:00';
$sortby                   = spnxHelper::getFilterVar( 'sortby' );
$ptype                    = spnxHelper::getFilterVar( 'post_type' );
$p                        = 0;
$catlists                 = [];
if ( $settings['due_date'] == '0000-00-00 00:00:00' ) {
	echo 'You are not a registered user. You can create a boost post after registration. Click Here to <a href="admin.php?page=spinkx-site-register">Register</a>';
} else {
	$url      = esc_url( SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/site/get-point-price' );
	$post     = array( 'points' => 100 );
	$response = spnxHelper::doCurl( $url, $post );
	$response = json_decode( $response, true );

	$url      = esc_url( SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/system/cat-lists' );
	$catlists = json_decode( spnxHelper::doCurl( $url, true ), true );

	$url        = esc_url( SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/site/get-auto-boost' );
	$auto_boost = json_decode( spnxHelper::doCurl( $url, true ), true );
	$p          = [
		'site_id'      => $settings['site_id'],
		'license_code' => $settings['license_code'],
		'reg_email'    => $settings['reg_email'],
		'sortby2'      => $sortby,
		'post_type2'   => $ptype,
		'from_date'    => $from_date,
		'to_date'      => $to_date,
		'url'          => esc_url( SPINKX_CONTENT_DIST_URL ),
	];
	$p          = wp_json_encode( $p );
	$url        = esc_url( SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/content-playlist' );
}
$bwki_sites_display_length = spnxHelper::getFilterVar( 'bwki_sites_display_length' );
$page_length               = ( $bwki_sites_display_length ) ? $bwki_sites_display_length : 10;
$custom_js                 = 'var pt = ' . $p . '; var pageLength = ' . $page_length . '; ';
$custom_js                .= 'var start = moment(' . ( $startdate ) . ');';
$custom_js                .= 'var end = moment(' . ( $enddate ) . '); jQuery(function() {';

if ( $settings['due_date'] != '0000-00-00 00:00:00' ) {
	$custom_js .= 'jQuery("#daterange").dateRangePicker({container: "#daterange-picker-container",numberOfMonths: 3,datepickerShowing: true, maxDate: "0D",minDate: new Date(2016, 8, 01),test: true,today: ' . $todaydate . '});
	var $ = jQuery.noConflict();	
	loadDT(start.format(\'YYYY-MM-DD\'), end.format(\'YYYY-MM-DD\'));	
	});';
} else {
	$custom_js .= 'jQuery(\'.spnx_wdgt_wrapper\').hide(); });';
}
wp_enqueue_script( 'jquery-youtubeapi', 'https://www.youtube.com/iframe_api' );
$vendor = SPINKX_CONTENT_PLUGIN_URL . '/vendor/';
wp_enqueue_script( 'jquery-powertip', $vendor . 'jQuery-powertip/js/jquery.powertip.min.js' );
wp_add_inline_script( 'jquery-powertip', $custom_js );if ( $settings['due_date'] != '0000-00-00 00:00:00' ) {
	$diff = $spnx_admin_manage->spinkx_cont_get_license_date( $settings['due_date'] );
	if ( intval( $diff->format( '%R%a days' ) ) < 0 ) { ?>
			<div class="spnx-dshb-mn-cntr" style="width:1024px;">
				<div class="spnx-sec-mn-cntr" >
					<div class="spnx-sec-one-chld-mn-cntnr cmn-cls-spnx-tab dbd-banner-img" style="width: 100%; ">
						<div class="points-cmn-cls-spnx" style="font-size: 12px;font-weight: 600;">



							<div style="font-size: 10px;width: 120px;float: right;margin-right: 9%; margin-top: -9px" class="purchase-plugin dashb-buy-points"><form target="_blank" class="paypal" action="<?php echo SPINKX_CONTENT_BAPI_URL; ?>/payments" method="post" id="paypal_form">
									<input type="hidden" name="cmd" value="_xclick" />
									<input type="hidden" name="hosted_button_id" value="YT4RDQ6GGRCLA">
									<input type="hidden" name="no_note" value="<?php echo $settings['reg_email']; ?>" />
									<input type="hidden" name="bn" value="<?php echo $settings['license_code']; ?>" />
									<input type="hidden" name="payer_email" value="<?php echo $spnx_admin_manage->getCurrentUserEmail(); ?>" />
                                    <input type="hidden" name="return" value="<?php echo spnxHelper::getCurrentURL();?>" />
									<input type="hidden" name="item_number" value="<?php echo $settings['site_id']; ?>" />
                                    <select name="os0" style="margin-left: 33px; width: 180px; background: #fff !important;">
										<?php if( $response['currency_format'] === 'rupee-sign') { ?>
                                            <option value="Option 1">Option 1 : ₹400.00 INR - Monthly</option>
                                            <option value="Option 2">Option 2 : ₹4,500.00 INR - Yearly</option>
										<?php } else { ?>
                                            <option value="Option 1">Option 1 : $6.00 USD - monthly</option>
                                            <option value="Option 2">Option 2 : $60.00 USD - yearly</option>
										<?php } ?>
                                    </select>
									<img style="margin-left: -21px;width: 6%;margin-top: 12px; position: absolute;float: left;" src="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNi4wLjQsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB3aWR0aD0iMjM1cHgiIGhlaWdodD0iNjBweCIgdmlld0JveD0iMCAwIDIzNSA2MCIgZW5hYmxlLWJhY2tncm91bmQ9Im5ldyAwIDAgMjM1IDYwIiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxnPg0KCTxnPg0KCQk8Zz4NCgkJCTxwYXRoIGZpbGw9IiNGRkZGRkYiIGQ9Ik0xNzkuMjU1LDE0Ljc2NWgtMTMuMjFjLTAuOTAzLDAtMS42NzEsMC42NTMtMS44MSwxLjU0NWwtNS4zMzksMzMuODU5DQoJCQkJYy0wLjEwMywwLjY2NSwwLjQwOSwxLjI3NiwxLjA5LDEuMjc2aDYuNzdjMC42MzEsMCwxLjE3Mi0wLjQ2NywxLjI3LTEuMDg5bDEuNTEyLTkuNTk4YzAuMTQ0LTAuODg2LDAuOTExLTEuNTQ3LDEuODE0LTEuNTQ3DQoJCQkJaDQuMTc3YzguNjk2LDAsMTMuNzE1LTQuMjA5LDE1LjAyOC0xMi41NThjMC41ODgtMy42NDIsMC4wMTktNi41MTItMS42ODUtOC41MTlDMTg2Ljk5OCwxNS45MywxODMuNjY0LDE0Ljc2NSwxNzkuMjU1LDE0Ljc2NXoNCgkJCQkgTTE4MC43NzYsMjcuMTI3Yy0wLjcyMSw0Ljc0LTQuMzQ2LDQuNzQtNy44NDYsNC43NGgtMS45OTFsMS40LTguODQ5YzAuMDgyLTAuNTMxLDAuNTQ4LTAuOTI0LDEuMDg0LTAuOTI0aDAuOTEyDQoJCQkJYzIuMzg1LDAsNC42NCwwLDUuODAzLDEuMzQ4QzE4MC44MjYsMjQuMjU5LDE4MS4wNCwyNS40NjQsMTgwLjc3NiwyNy4xMjd6Ii8+DQoJCQk8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNODUuMDc3LDE0Ljc2NUg3MS44NjhjLTAuOTAzLDAtMS42NzEsMC42NTMtMS44MSwxLjU0NWwtNS4zMzUsMzMuODU5DQoJCQkJYy0wLjExMSwwLjY2NSwwLjQwNSwxLjI3NiwxLjA4NiwxLjI3Nmg2LjMwNGMwLjg5NiwwLDEuNjY3LTAuNjYxLDEuODExLTEuNTU1bDEuNDQzLTkuMTMyYzAuMTM4LTAuODg2LDAuOTA1LTEuNTQ3LDEuODA5LTEuNTQ3DQoJCQkJaDQuMThjOC42OTgsMCwxMy43MTUtNC4yMDksMTUuMDI3LTEyLjU1OGMwLjU4NC0zLjY0MiwwLjAyNS02LjUxMi0xLjY4MS04LjUxOUM5Mi44MjMsMTUuOTMsODkuNDksMTQuNzY1LDg1LjA3NywxNC43NjV6DQoJCQkJIE04Ni42MDEsMjcuMTI3Yy0wLjcyNCw0Ljc0LTQuMzQ2LDQuNzQtNy44NDEsNC43NGgtMS45OThsMS40MDItOC44NDljMC4wODItMC41MzEsMC41MzktMC45MjQsMS4wODMtMC45MjRoMC45MTUNCgkJCQljMi4zODMsMCw0LjYzOCwwLDUuNzksMS4zNDhDODYuNjQ3LDI0LjI1OSw4Ni44NTUsMjUuNDY0LDg2LjYwMSwyNy4xMjd6Ii8+DQoJCQk8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNMTI0LjU0LDI2Ljk3N2gtNi4zMTdjLTAuNTQ2LDAtMS4wMDgsMC4zOTYtMS4wOSwwLjkzMmwtMC4yODEsMS43NjZsLTAuNDQzLTAuNjM5DQoJCQkJYy0xLjM2OC0xLjk4Ni00LjQxOS0yLjY1My03LjQ2Ny0yLjY1M2MtNi45ODMsMC0xMi45NTEsNS4yOS0xNC4xMTIsMTIuNzIzYy0wLjYwOSwzLjY5OCwwLjI1Miw3LjI0MSwyLjM1Myw5LjcwOQ0KCQkJCWMxLjkyOSwyLjI3Miw0LjY4NiwzLjIxMiw3Ljk2MywzLjIxMmM1LjYzMywwLDguNzUyLTMuNjEzLDguNzUyLTMuNjEzbC0wLjI3OSwxLjc1N2MtMC4xMDgsMC42NjUsMC40MDksMS4yNzYsMS4wODMsMS4yNzZoNS42OTcNCgkJCQljMC45MDEsMCwxLjY2Ny0wLjY2MSwxLjgxMi0xLjU1NWwzLjQyLTIxLjY0NEMxMjUuNzMzLDI3LjU4LDEyNS4yMjIsMjYuOTc3LDEyNC41NCwyNi45Nzd6IE0xMTUuNzMsMzkuMjg0DQoJCQkJYy0wLjYxMywzLjYxMy0zLjQ4MSw2LjAzOC03LjE0LDYuMDM4Yy0xLjgyNywwLTMuMjk4LTAuNTg4LTQuMjQ1LTEuNzA3Yy0wLjkzNi0xLjEwNS0xLjI4My0yLjY4NS0wLjk5My00LjQ0MQ0KCQkJCWMwLjU3MS0zLjU3OCwzLjQ4NS02LjA4OCw3LjA4OC02LjA4OGMxLjgwMSwwLDMuMjU2LDAuNTk2LDQuMjExLDEuNzI5QzExNS42MjUsMzUuOTQ3LDExNi4wMDUsMzcuNTMxLDExNS43MywzOS4yODR6Ii8+DQoJCQk8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNMjE4LjcxNywyNi45NzdoLTYuMzI0Yy0wLjU0MywwLTEuMDAxLDAuMzk2LTEuMDg5LDAuOTMybC0wLjI3NSwxLjc2NmwtMC40NDItMC42MzkNCgkJCQljLTEuMzcxLTEuOTg2LTQuNDE5LTIuNjUzLTcuNDY3LTIuNjUzYy02Ljk4NSwwLTEyLjk1MSw1LjI5LTE0LjExMiwxMi43MjNjLTAuNjA0LDMuNjk4LDAuMjU2LDcuMjQxLDIuMzUzLDkuNzA5DQoJCQkJYzEuOTI5LDIuMjcyLDQuNjgzLDMuMjEyLDcuOTU5LDMuMjEyYzUuNjM0LDAsOC43NTgtMy42MTMsOC43NTgtMy42MTNsLTAuMjgyLDEuNzU3Yy0wLjEwNywwLjY2NSwwLjQwOCwxLjI3NiwxLjA4NSwxLjI3Nmg1LjY5OA0KCQkJCWMwLjkwMSwwLDEuNjY1LTAuNjYxLDEuODEtMS41NTVsMy40MjMtMjEuNjQ0QzIxOS45MTIsMjcuNTgsMjE5LjM5NiwyNi45NzcsMjE4LjcxNywyNi45Nzd6IE0yMDkuOTA2LDM5LjI4NA0KCQkJCWMtMC42MTEsMy42MTMtMy40NzYsNi4wMzgtNy4xMzksNi4wMzhjLTEuODMzLDAtMy4zLTAuNTg4LTQuMjQzLTEuNzA3Yy0wLjkzOS0xLjEwNS0xLjI4Ny0yLjY4NS0wLjk4OS00LjQ0MQ0KCQkJCWMwLjU2OS0zLjU3OCwzLjQ3Ni02LjA4OCw3LjA4MS02LjA4OGMxLjgwMywwLDMuMjU4LDAuNTk2LDQuMjIxLDEuNzI5QzIwOS44MDMsMzUuOTQ3LDIxMC4xODEsMzcuNTMxLDIwOS45MDYsMzkuMjg0eiIvPg0KCQkJPHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTE1OC4yMTYsMjYuOTc3aC02LjM1N2MtMC42MDcsMC0xLjE3MiwwLjMwMi0xLjUxNCwwLjgwNGwtOC43NjksMTIuOTEzbC0zLjcxNi0xMi40MQ0KCQkJCWMtMC4yMzMtMC43NzUtMC45NDUtMS4zMDctMS43NTMtMS4zMDdoLTYuMjUyYy0wLjc1NCwwLTEuMjc4LDAuNzM5LTEuMDQxLDEuNDU2bDYuOTk5LDIwLjUzN2wtNi41NzksOS4yODkNCgkJCQljLTAuNTE5LDAuNzI5LDAsMS43NDEsMC44OTIsMS43NDFoNi4zNTJjMC41OTgsMCwxLjE2NS0wLjMwMiwxLjUwOC0wLjc5MWwyMS4xMzQtMzAuNTA0DQoJCQkJQzE1OS42MjcsMjcuOTc0LDE1OS4xMDQsMjYuOTc3LDE1OC4yMTYsMjYuOTc3eiIvPg0KCQkJPHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTIyNi4xNjgsMTUuNjg5bC01LjQxOSwzNC40OGMtMC4xMDQsMC42NjUsMC40MTMsMS4yNzYsMS4wODgsMS4yNzZoNS40NQ0KCQkJCWMwLjkwNSwwLDEuNjczLTAuNjYxLDEuODExLTEuNTU1bDUuMzUxLTMzLjg1NmMwLjEwNC0wLjY2OC0wLjQxNi0xLjI3LTEuMDkyLTEuMjdoLTYuMQ0KCQkJCUMyMjYuNzE1LDE0Ljc2NSwyMjYuMjU2LDE1LjE2LDIyNi4xNjgsMTUuNjg5eiIvPg0KCQk8L2c+DQoJPC9nPg0KCTxnPg0KCQk8cGF0aCBmaWxsPSIjRkZGRkZGIiBkPSJNMzkuMjE4LDIwLjQ2NWMwLjYyOC0zLjk5My0wLjAwNi02LjcxLTIuMTY2LTkuMTcxYy0yLjM4LTIuNzEtNi42NzYtMy44NzItMTIuMTc2LTMuODcySDguOTINCgkJCWMtMS4xMjYsMC0yLjA4LDAuODE3LTIuMjU4LDEuOTI5TDAuMDE3LDUxLjQ4N2MtMC4xMzIsMC44MzIsMC41MTQsMS41ODUsMS4zNTIsMS41ODVoOS44NTRsLTAuNjgyLDQuMzEyDQoJCQljLTAuMTE0LDAuNzI5LDAuNDUzLDEuMzgzLDEuMTg0LDEuMzgzaDguMzA3YzAuOTgzLDAsMS44MTgtMC43MTEsMS45NzMtMS42ODVsMC4wODItMC40MjRsMS41NjQtOS45MTlsMC4wOTktMC41NDINCgkJCWMwLjE1MS0wLjk3LDAuOTkzLTEuNjg4LDEuOTczLTEuNjg4aDEuMjQ3YzguMDQsMCwxNC4zNDMtMy4yNjksMTYuMTc4LTEyLjcyNmMwLjc3NS0zLjk1MywwLjM3My03LjI0NS0xLjY1NS05LjU2Mg0KCQkJQzQwLjg3NSwyMS41MTksNDAuMTA3LDIwLjkzOCwzOS4yMTgsMjAuNDY1TDM5LjIxOCwyMC40NjUiLz4NCgkJPHBhdGggZmlsbD0iI0ZGRkZGRiIgZD0iTTM5LjIxOCwyMC40NjVjMC42MjgtMy45OTMtMC4wMDYtNi43MS0yLjE2Ni05LjE3MWMtMi4zOC0yLjcxLTYuNjc2LTMuODcyLTEyLjE3Ni0zLjg3Mkg4LjkyDQoJCQljLTEuMTI2LDAtMi4wOCwwLjgxNy0yLjI1OCwxLjkyOUwwLjAxNyw1MS40ODdjLTAuMTMyLDAuODMyLDAuNTE0LDEuNTg1LDEuMzUyLDEuNTg1aDkuODU0bDIuNDczLTE1LjY4OWwtMC4wNzksMC40ODgNCgkJCWMwLjE3OS0xLjExMSwxLjEyNi0xLjkyOSwyLjI1MS0xLjkyOWg0LjY4M2M5LjE5NCwwLDE2LjM5NC0zLjczOSwxOC41MDItMTQuNTQzQzM5LjExMSwyMS4wODIsMzkuMTY0LDIwLjc3MSwzOS4yMTgsMjAuNDY1Ii8+DQoJCTxwYXRoIGZpbGw9IiNGRkZGRkYiIGQ9Ik0xNi4zNTcsMjAuNTIxYzAuMTAzLTAuNjcyLDAuNTMzLTEuMjE3LDEuMTExLTEuNDkyYzAuMjYyLTAuMTIyLDAuNTUyLTAuMTksMC44NTktMC4xOWgxMi41MQ0KCQkJYzEuNDgyLDAsMi44NjcsMC4wOTQsNC4xMjcsMC4yOThjMC4zNTgsMC4wNTQsMC43MSwwLjEyNiwxLjA1NSwwLjE5N2MwLjM0MSwwLjA3OCwwLjY3NCwwLjE2MywwLjk5LDAuMjU4DQoJCQljMC4xNjQsMC4wNDQsMC4zMjMsMC4wOTQsMC40NzUsMC4xNDNjMC42MjEsMC4yMDgsMS4xOTksMC40NTMsMS43MzIsMC43MzFjMC42MjgtMy45OTMtMC4wMDYtNi43MS0yLjE2Ni05LjE3MQ0KCQkJYy0yLjM4LTIuNzEtNi42NzYtMy44NzItMTIuMTc2LTMuODcySDguOTJjLTEuMTI2LDAtMi4wOCwwLjgxNy0yLjI1OCwxLjkyOUwwLjAxNyw1MS40ODdjLTAuMTMyLDAuODMyLDAuNTE0LDEuNTg1LDEuMzUyLDEuNTg1DQoJCQloOS44NTRsMi40NzMtMTUuNjg5TDE2LjM1NywyMC41MjF6Ii8+DQoJPC9nPg0KCTxnPg0KCQk8cGF0aCBmaWxsPSIjMDA5Q0RFIiBkPSJNMTc5LjI1NSwxMS4wOTJoLTEzLjIxYy0wLjkwMywwLTEuNjcxLDAuNjUzLTEuODEsMS41NDdsLTUuMzM5LDMzLjg1Ng0KCQkJYy0wLjEwMywwLjY2OCwwLjQwOSwxLjI3OSwxLjA5LDEuMjc5aDYuNzdjMC42MzEsMCwxLjE3Mi0wLjQ2NiwxLjI3LTEuMDkybDEuNTEyLTkuNTk1YzAuMTQ0LTAuODksMC45MTEtMS41NSwxLjgxNC0xLjU1aDQuMTc3DQoJCQljOC42OTYsMCwxMy43MTUtNC4yMSwxNS4wMjgtMTIuNTU1YzAuNTg4LTMuNjQzLDAuMDE5LTYuNTExLTEuNjg1LTguNTIxQzE4Ni45OTgsMTIuMjU5LDE4My42NjQsMTEuMDkyLDE3OS4yNTUsMTEuMDkyeg0KCQkJIE0xODAuNzc2LDIzLjQ1NmMtMC43MjEsNC43NDItNC4zNDYsNC43NDItNy44NDYsNC43NDJoLTEuOTkxbDEuNC04Ljg0N2MwLjA4Mi0wLjUzNCwwLjU0OC0wLjkyOSwxLjA4NC0wLjkyOWgwLjkxMg0KCQkJYzIuMzg1LDAsNC42NCwwLDUuODAzLDEuMzUyQzE4MC44MjYsMjAuNTg3LDE4MS4wNCwyMS43OTIsMTgwLjc3NiwyMy40NTZ6Ii8+DQoJCTxwYXRoIGZpbGw9IiMwMDMwODciIGQ9Ik04NS4wNzcsMTEuMDkySDcxLjg2OGMtMC45MDMsMC0xLjY3MSwwLjY1My0xLjgxLDEuNTQ3bC01LjMzNSwzMy44NTYNCgkJCWMtMC4xMTEsMC42NjgsMC40MDUsMS4yNzksMS4wODYsMS4yNzloNi4zMDRjMC44OTYsMCwxLjY2Ny0wLjY2LDEuODExLTEuNTU5bDEuNDQzLTkuMTI4YzAuMTM4LTAuODksMC45MDUtMS41NSwxLjgwOS0xLjU1aDQuMTgNCgkJCWM4LjY5OCwwLDEzLjcxNS00LjIxLDE1LjAyNy0xMi41NTVjMC41ODQtMy42NDMsMC4wMjUtNi41MTEtMS42ODEtOC41MjFDOTIuODIzLDEyLjI1OSw4OS40OSwxMS4wOTIsODUuMDc3LDExLjA5MnoNCgkJCSBNODYuNjAxLDIzLjQ1NmMtMC43MjQsNC43NDItNC4zNDYsNC43NDItNy44NDEsNC43NDJoLTEuOTk4bDEuNDAyLTguODQ3YzAuMDgyLTAuNTM0LDAuNTM5LTAuOTI5LDEuMDgzLTAuOTI5aDAuOTE1DQoJCQljMi4zODMsMCw0LjYzOCwwLDUuNzksMS4zNTJDODYuNjQ3LDIwLjU4Nyw4Ni44NTUsMjEuNzkyLDg2LjYwMSwyMy40NTZ6Ii8+DQoJCTxwYXRoIGZpbGw9IiMwMDMwODciIGQ9Ik0xMjQuNTQsMjMuMzA2aC02LjMxN2MtMC41NDYsMC0xLjAwOCwwLjM5NC0xLjA5LDAuOTMzbC0wLjI4MSwxLjc2M2wtMC40NDMtMC42MzgNCgkJCWMtMS4zNjgtMS45ODYtNC40MTktMi42NTItNy40NjctMi42NTJjLTYuOTgzLDAtMTIuOTUxLDUuMjkyLTE0LjExMiwxMi43MmMtMC42MDksMy43MDEsMC4yNTIsNy4yNDUsMi4zNTMsOS43MTMNCgkJCWMxLjkyOSwyLjI3Miw0LjY4NiwzLjIxMiw3Ljk2MywzLjIxMmM1LjYzMywwLDguNzUyLTMuNjE3LDguNzUyLTMuNjE3bC0wLjI3OSwxLjc1N2MtMC4xMDgsMC42NjgsMC40MDksMS4yNzksMS4wODMsMS4yNzloNS42OTcNCgkJCWMwLjkwMSwwLDEuNjY3LTAuNjYsMS44MTItMS41NTlsMy40Mi0yMS42NDFDMTI1LjczMywyMy45MDgsMTI1LjIyMiwyMy4zMDYsMTI0LjU0LDIzLjMwNnogTTExNS43MywzNS42MQ0KCQkJYy0wLjYxMywzLjYxNy0zLjQ4MSw2LjAzOC03LjE0LDYuMDM4Yy0xLjgyNywwLTMuMjk4LTAuNTg4LTQuMjQ1LTEuNzA3Yy0wLjkzNi0xLjEwNC0xLjI4My0yLjY4Mi0wLjk5My00LjQzOA0KCQkJYzAuNTcxLTMuNTc4LDMuNDg1LTYuMDg3LDcuMDg4LTYuMDg3YzEuODAxLDAsMy4yNTYsMC41OTYsNC4yMTEsMS43MjdDMTE1LjYyNSwzMi4yNzUsMTE2LjAwNSwzMy44NjEsMTE1LjczLDM1LjYxeiIvPg0KCQk8cGF0aCBmaWxsPSIjMDA5Q0RFIiBkPSJNMjE4LjcxNywyMy4zMDZoLTYuMzI0Yy0wLjU0MywwLTEuMDAxLDAuMzk0LTEuMDg5LDAuOTMzbC0wLjI3NSwxLjc2M2wtMC40NDItMC42MzgNCgkJCWMtMS4zNzEtMS45ODYtNC40MTktMi42NTItNy40NjctMi42NTJjLTYuOTg1LDAtMTIuOTUxLDUuMjkyLTE0LjExMiwxMi43MmMtMC42MDQsMy43MDEsMC4yNTYsNy4yNDUsMi4zNTMsOS43MTMNCgkJCWMxLjkyOSwyLjI3Miw0LjY4MywzLjIxMiw3Ljk1OSwzLjIxMmM1LjYzNCwwLDguNzU4LTMuNjE3LDguNzU4LTMuNjE3bC0wLjI4MiwxLjc1N2MtMC4xMDcsMC42NjgsMC40MDgsMS4yNzksMS4wODUsMS4yNzloNS42OTgNCgkJCWMwLjkwMSwwLDEuNjY1LTAuNjYsMS44MS0xLjU1OWwzLjQyMy0yMS42NDFDMjE5LjkxMiwyMy45MDgsMjE5LjM5NiwyMy4zMDYsMjE4LjcxNywyMy4zMDZ6IE0yMDkuOTA2LDM1LjYxDQoJCQljLTAuNjExLDMuNjE3LTMuNDc2LDYuMDM4LTcuMTM5LDYuMDM4Yy0xLjgzMywwLTMuMy0wLjU4OC00LjI0My0xLjcwN2MtMC45MzktMS4xMDQtMS4yODctMi42ODItMC45ODktNC40MzgNCgkJCWMwLjU2OS0zLjU3OCwzLjQ3Ni02LjA4Nyw3LjA4MS02LjA4N2MxLjgwMywwLDMuMjU4LDAuNTk2LDQuMjIxLDEuNzI3QzIwOS44MDMsMzIuMjc1LDIxMC4xODEsMzMuODYxLDIwOS45MDYsMzUuNjF6Ii8+DQoJCTxwYXRoIGZpbGw9IiMwMDMwODciIGQ9Ik0xNTguMjE2LDIzLjMwNmgtNi4zNTdjLTAuNjA3LDAtMS4xNzIsMC4zMDEtMS41MTQsMC44MDNsLTguNzY5LDEyLjkxNWwtMy43MTYtMTIuNDEzDQoJCQljLTAuMjMzLTAuNzc1LTAuOTQ1LTEuMzA1LTEuNzUzLTEuMzA1aC02LjI1MmMtMC43NTQsMC0xLjI3OCwwLjczOC0xLjA0MSwxLjQ1Nmw2Ljk5OSwyMC41MzhsLTYuNTc5LDkuMjg0DQoJCQljLTAuNTE5LDAuNzMzLDAsMS43NDYsMC44OTIsMS43NDZoNi4zNTJjMC41OTgsMCwxLjE2NS0wLjMwMiwxLjUwOC0wLjc5MWwyMS4xMzQtMzAuNTA1DQoJCQlDMTU5LjYyNywyNC4zMDIsMTU5LjEwNCwyMy4zMDYsMTU4LjIxNiwyMy4zMDZ6Ii8+DQoJCTxwYXRoIGZpbGw9IiMwMDlDREUiIGQ9Ik0yMjYuMTY4LDEyLjAxOWwtNS40MTksMzQuNDc3Yy0wLjEwNCwwLjY2OCwwLjQxMywxLjI3OSwxLjA4OCwxLjI3OWg1LjQ1YzAuOTA1LDAsMS42NzMtMC42NiwxLjgxMS0xLjU1OQ0KCQkJbDUuMzUxLTMzLjg1NGMwLjEwNC0wLjY2Ny0wLjQxNi0xLjI3LTEuMDkyLTEuMjdoLTYuMUMyMjYuNzE1LDExLjA5MiwyMjYuMjU2LDExLjQ4OCwyMjYuMTY4LDEyLjAxOXoiLz4NCgk8L2c+DQoJPHBhdGggZmlsbD0iIzAwOUNERSIgZD0iTTM5LjIxOCwxNi43OTVjMC42MjgtMy45OTUtMC4wMDYtNi43MTItMi4xNjYtOS4xNzJjLTIuMzgtMi43MTEtNi42NzYtMy44NzMtMTIuMTc2LTMuODczSDguOTINCgkJYy0xLjEyNiwwLTIuMDgsMC44MTctMi4yNTgsMS45MjlMMC4wMTcsNDcuODE2Yy0wLjEzMiwwLjgzMywwLjUxNCwxLjU4NSwxLjM1MiwxLjU4NWg5Ljg1NGwtMC42ODIsNC4zMDkNCgkJYy0wLjExNCwwLjczMywwLjQ1MywxLjM4NiwxLjE4NCwxLjM4Nmg4LjMwN2MwLjk4MywwLDEuODE4LTAuNzEsMS45NzMtMS42ODhsMC4wODItMC40MmwxLjU2NC05LjkxOWwwLjA5OS0wLjU0Ng0KCQljMC4xNTEtMC45NjcsMC45OTMtMS42ODUsMS45NzMtMS42ODVoMS4yNDdjOC4wNCwwLDE0LjM0My0zLjI3LDE2LjE3OC0xMi43MjljMC43NzUtMy45NTIsMC4zNzMtNy4yNDMtMS42NTUtOS41NTkNCgkJQzQwLjg3NSwxNy44NDksNDAuMTA3LDE3LjI2OCwzOS4yMTgsMTYuNzk1TDM5LjIxOCwxNi43OTUiLz4NCgk8cGF0aCBmaWxsPSIjMDEyMTY5IiBkPSJNMzkuMjE4LDE2Ljc5NWMwLjYyOC0zLjk5NS0wLjAwNi02LjcxMi0yLjE2Ni05LjE3MmMtMi4zOC0yLjcxMS02LjY3Ni0zLjg3My0xMi4xNzYtMy44NzNIOC45Mg0KCQljLTEuMTI2LDAtMi4wOCwwLjgxNy0yLjI1OCwxLjkyOUwwLjAxNyw0Ny44MTZjLTAuMTMyLDAuODMzLDAuNTE0LDEuNTg1LDEuMzUyLDEuNTg1aDkuODU0bDIuNDczLTE1LjY4OWwtMC4wNzksMC40ODUNCgkJYzAuMTc5LTEuMTExLDEuMTI2LTEuOTI5LDIuMjUxLTEuOTI5aDQuNjgzYzkuMTk0LDAsMTYuMzk0LTMuNzM1LDE4LjUwMi0xNC41NDJDMzkuMTExLDE3LjQxMiwzOS4xNjQsMTcuMDk4LDM5LjIxOCwxNi43OTUiLz4NCgk8cGF0aCBmaWxsPSIjMDAzMDg3IiBkPSJNMTYuMzU3LDE2Ljg0OGMwLjEwMy0wLjY3LDAuNTMzLTEuMjE2LDEuMTExLTEuNDkxYzAuMjYyLTAuMTIzLDAuNTUyLTAuMTkxLDAuODU5LTAuMTkxaDEyLjUxDQoJCWMxLjQ4MiwwLDIuODY3LDAuMDk0LDQuMTI3LDAuMjk4YzAuMzU4LDAuMDUzLDAuNzEsMC4xMjYsMS4wNTUsMC4xOTZjMC4zNDEsMC4wODEsMC42NzQsMC4xNjMsMC45OSwwLjI2DQoJCWMwLjE2NCwwLjA0MiwwLjMyMywwLjA5MiwwLjQ3NSwwLjE0NGMwLjYyMSwwLjIwOCwxLjE5OSwwLjQ1MSwxLjczMiwwLjczMWMwLjYyOC0zLjk5NS0wLjAwNi02LjcxMi0yLjE2Ni05LjE3Mg0KCQljLTIuMzgtMi43MTEtNi42NzYtMy44NzMtMTIuMTc2LTMuODczSDguOTJjLTEuMTI2LDAtMi4wOCwwLjgxNy0yLjI1OCwxLjkyOUwwLjAxNyw0Ny44MTZjLTAuMTMyLDAuODMzLDAuNTE0LDEuNTg1LDEuMzUyLDEuNTg1DQoJCWg5Ljg1NGwyLjQ3My0xNS42ODlMMTYuMzU3LDE2Ljg0OHoiLz4NCjwvZz4NCjwvc3ZnPg0K" alt="PayPal">
									<input type="image" src="https://www.paypalobjects.com/en_GB/i/btn/btn_subscribeCC_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online!" style="margin-top: 10px; margin-left: 34px; padding: 0; border: 0;">
									<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
								</form></div>


						</div>
					</div>
				</div>
			</div>
			<?php } ?>

<div class="content_playlist_listing">

	<table id="bwki_sites_display" class="wp-list-table table-responsive " style="width:1024px;"><thead  style="border-bottom:1px solid #469fa1">
		<tr>
			<td  style="padding: 0px;text-align: right;">
				<span class="duration-text-first-span-span-crnt">Current Points: <span id="credit_points_value" class="pts_mny_cmn_cls"><?php echo $spnx_admin_manage->spinkx_cont_get_credit_points(); ?></span></span>
				

			</td>
			<td style="padding: 0px;">
				&nbsp;&nbsp;&nbsp;<button  class=" buy-more-point" id="buy-more-point" onclick="getpoints()">Buy More Point</button>

			</td>
			<td  style="padding: 0px;text-align:right;">
				<?php if ( is_array( $catlists ) && count( $catlists ) > 0 ) { ?>
				<div class="glbal_bp_main_cntnr">	
				<span class="duration-text-first-span-span-crnt glbl_stngs_lbl">Global Settings</span>
				<span class="glbl_setng_cb"><i class="fa fa-cog" aria-hidden="true" aria-hidden="true" onclick="modifiedcategory('g')"></i></span>
					<div class="cntnr-main-drn-cls modified-category-g" style="display:none;">
						<span class="duration-text-first-span"> Modified category</span>
									   <span class="fnt-awsm-spn"><i class="fa fa-times fnt-click-disable-drn fnt-click-close-drn" aria-hidden="true" onclick="modifiedcategory('1728')"></i></span>


						<div class="select-drtn-cls">
							<select class="categories" id="bp_categories_g" multiple="">
								<?php
								$flag = isset( $catlists[1] );
								foreach ( $catlists[0] as $key => $value ) {
									$selected = '';
									if ( $flag ) {
										if ( in_array( $key, $catlists[1] ) ) {
											$selected = 'selected';
										}
									}
									echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
								}
								?>

							</select>
						</div>
						<div style="clear: both;">
						</div>
						<div style="margin-top:10px;">
								  <span style="display:none;" class="tt-txt-btn-cmn-cls-input">
									<span class="duration-text-first-span">Duration</span>
									   <input type="number" id="bp_categories_g" class="txt-box-days" min="1" max="9999" placeholder="∞">
								  </span>
							<button class="clrsave updatecategories" type="button" data-id="g">save</button>
						</div>

					</div>
				</div>    
				<?php } ?>
			</td>
			

		</tr>
		<tr style="background-color: #e4eff4 !important;height: 27px;font-size: 12px; color:#0170b6;">
			<th style="padding: 0px;border:none;width:475px;">&nbsp;&nbsp;&nbsp;Post Details</th>
			<th style=" padding: 0 0 0 12px;border:none;width:252px;">&nbsp;&nbsp;&nbsp;Your Post Stats <div class="onoff_header" >
					<div class="onoffswitch">
						<input type="checkbox" data-id="all_local" data-site="<?php echo $settings['site_id']; ?>" name="playpauseswitch_local_all" class="onoffswitch-checkbox" id="playpauseswitch_local_all"  checked >
						<label class="onoffswitch-label" for="playpauseswitch_local_all">
							<span class="onoffswitch-inner"></span>
							<span class="onoffswitch-switch"></span>
						</label>

					</div>
					<input type="button" onclick="all_onoff('local')" value="Switch All" />
				</div></th>
			<th style="padding: 0 0 0 12px;border:none;width:252px;">&nbsp;&nbsp;&nbsp;Boost Post Stats <div class="onoff_header" >
					<div class="onoffswitch"  >
						<input type="checkbox" data-id="all_global" data-site="<?php echo $settings['site_id']; ?>" name="playpauseswitch_global_all" class="onoffswitch-checkbox" id="playpauseswitch_global_all"  checked >
						<label class="onoffswitch-label" for="playpauseswitch_global_all">
							<span class="onoffswitch-inner"></span>
							<span class="onoffswitch-switch"></span>
						</label>
					</div>
					<input type="button" onclick="all_onoff('global')" value="Auto Boost"  />
				</div></th>

		</tr>
		</thead><tbody><input type="hidden" id="chooks" value="0" /></tbody>
	</table></div>
<div class="clear"></div>
</div>
<!-- Modal -->

<div id="boostmodalshowMsgWidgetNotFrontShow" style="z-index: 9999;" class="modal small fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><strong>Boost Post</strong></h4>
			</div>

			<div class="modal-body">
				Please activate Spinkx Widget in your Front End  - <br/>1. Go to <a href="admin.php?page=spinkx_widget_design#widget_design">Widget Design</a> & Copy the Short code - which should look like this - [ spinkx id=”xx” ]
				<br/>2. Go to <a href="widgets.php">Appearances > Widgets</a> of your WordPress Sidebar and scroll to the bottom add “TEXT” to your Main Sidebar.	<br/>3. To accumulate more boost points and advertising revenue, try and place your widget on the top.
			</div>
		</div>
	</div>
</div>
<div id="boostmodalshowMsgWidgetNotActivate" style="z-index: 9999;" class="modal small fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><strong>Boost Post</strong></h4>
			</div>
			<div class="modal-body">
				All your Sidebar Widget are Inactive. All your Boost Posts will be Stopped! Please  <a href="admin.php?page=spinkx_widget_design#widget_design">activate the Widget</a> and display it on your Front end to enable display of 'Boost Post’ on other Websites
			</div>
		</div>
	</div>
</div>
<div id="boostmodalshowMsg" style="z-index: 9999;" class="modal small fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><strong>Boost Post</strong></h4>
			</div>
			<div class="modal-body">
				Your site is not registered. Kindly <a href="admin.php?page=spinkx-site-register.php">Register</a> to start using credit points to boost post.
			</div>

		</div>
	</div>
</div>
<div id="boostmodalbuyPoint" style="z-index: 9999;" class="modal small fade" role="dialog">
	<div class="modal-dialog">

		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header modal_header_cc_spnkx">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"><i class="fas fa-bullseye"></i><strong>Buy Points</strong></h4>
			</div>
			<?php

			?>
			<form class="paypal" action="<?php echo SPINKX_CONTENT_BAPI_URL; ?>/payments" method="post" id="paypal_form_buypoint" target="_blank">
			<div class="modal-body modal_body_cc_spnkx">
				<?php if ( isset( $response['reach'] ) ) { ?>
				<div class="cmn_cntnt_body_mdl">
					<span>Points</span>
					<input type="number" class="form-control" id="buy_point" name="buy_point" value="100"/>
				</div>
				<div class="cmn_cntnt_body_mdl">
					<span>Views</span>
					<span id="reach"><?php echo $response['reach']; ?></span>
				</div>

				<div class="cmn_cntnt_body_mdl">
					<span>Price</span>
					<span><i id="currency_format" class="fa fa-<?php echo strtolower( $response['currency_format'] ); ?>"
							 style="display: inline;"></i><span id="amount"><?php echo $response['price']; ?></span>
						<input type="hidden" id="point_amount" value="<?php echo $response['price']; ?>"/>
				</div>
			</div>
				<div class="modal-footer modal_footer_cc_spnkx">
					<?php
				}
				?>
					<!-- <button data-dismiss="modal">CANCEL</button> -->
					<div style="width: 150px;height: 20px;">
						<input type="image" id="payment-method-button" src="<?php echo SPINKX_CONTENT_DIST_URL; ?>images/papal-checkout-button-spinkx.png" border="0" name="submit" alt="PayPal – The safer, easier way to pay online!" style="border:0;padding:0;
	float: left;
	margin-left: 24px;
	width: 100%;
	">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
					</div>
				</div>
				<input type="hidden" name="cmd" value="_yclick" />
				<input type="hidden" name="no_note" value="<?php echo $settings['reg_email']; ?>" />
				<input type="hidden" name="bn" value="<?php echo $settings['license_code']; ?>" />
				<input type="hidden" name="payer_email" value="<?php echo $spnx_admin_manage->getCurrentUserEmail(); ?>" />
                <input type="hidden" name="return" value="<?php echo spnxHelper::getCurrentURL();?>" />
				<input type="hidden" name="item_number" value="<?php echo $settings['site_id']; ?>" />

			</form>
			
		</div>
	</div>
</div>
</div>
<script>
	jQuery(document).ready(function($) {
		jQuery('#buy_point').on('blur', function(event){
			var points = parseInt($(this).val());
			if(points === undefined || isNaN(points)) {
			  //  document.getElementById('payment-method-button').style.backgroundColor = 'lightblue';
				alert('Please enter amount in a number.');
				$("#payment-method-button").prop('disabled',true);
				return;
			}
			else if (points < 100) {
			   // document.getElementById('payment-method-button').style.backgroundColor = 'lightblue';
				alert('A minimum of 100 points are required for a purchase.');
				$("#payment-method-button").prop("disabled", true);

				return;
			} else {
				$("#payment-method-button").prop("disabled", false);

			}
		   // document.getElementById('payment-method-button').style.backgroundColor = 'lightblue';
			jQuery.ajax({
				url : spinkx_server_baseurl + '/wp-json/spnx/v1/site/get-point-price',
				type : "post",
				beforeSend: function() {
					$("#payment-method-button").prop("disabled", true);
				},
				data : {
					"site_id" : g_site_id,
					"points": points,
					"license_code": '<?php echo $settings['license_code']; ?>',
					"reg_email": '<?php echo $settings['reg_email']; ?>',
				},
				success : function(data) {
					$('#reach').text(data.reach);
					$('#amount').text(data.price);
					$('#point_amount').val(data.price);
					$('#payment-method-button').prop("disabled", false);
				 //   document.getElementById('payment-method-button').style.backgroundColor = '#23bf4a';
				}
			});
		});

	});

</script>
<?php } ?>
