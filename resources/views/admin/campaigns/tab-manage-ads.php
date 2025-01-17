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
	$settings          = get_option( SPINKX_CONTENT_LICENSE );
	$settings          = maybe_unserialize( $settings );
	$todaydate         = 0;
	$enddate           = 0;
	$startdate         = 0;
	$from_date         = spnxHelper::getFilterVar( 'from_date' );
	$to_date           = spnxHelper::getFilterVar( 'to_date' );
	$spnx_admin_manage = new spnxAdminManage();
	$custom_date       = $spnx_admin_manage->spinkx_cont_last_30_days();
	$dist              = SPINKX_CONTENT_DIST_URL;
	$css               = 'scss';
if ( SPINKX_CONTENT_PRODUCTION ) {
	$css = 'css';
}
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

	$p         = [
		'site_id'      => $settings['site_id'],
		'license_code' => $settings['license_code'],
		'from_date'    => $from_date,
		'to_date'      => $to_date,
		'reg_email'    => $settings['reg_email'],
		'sortby'       => $sortby,
		'post_type'    => $ptype,
		'plg_url'      => esc_url( SPINKX_CONTENT_DIST_URL ),
	];
	$p         = wp_json_encode( $p );
	$url       = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/campaign';
	$custom_js = '';
	if ( $todaydate ) {
		$custom_js .= 'var start = moment(' . ( $startdate ) . ');';
		$custom_js .= 'var end = moment(' . ( $enddate ) . ');';
	} else {
		$custom_js .= 'var start = moment();';
		$custom_js .= 'var end = moment();';
	}
	$bwki_sites_display_length = spnxHelper::getFilterVar( 'bwki_sites_display_length' );
	$page_length               = ( $bwki_sites_display_length ) ? $bwki_sites_display_length : 10;
	$custom_js                .= 'var todaydate = start; var pageLength = ' . $page_length . ';
	var pt = ' . $p . '; jQuery(function() {
	jQuery("#daterange").dateRangePicker({container: "#daterange-picker-container",numberOfMonths: 3,datepickerShowing: true, maxDate: "0D",minDate: new Date(2016, 8, 01),test: true,today: ' . $todaydate . '});	
	var $ = jQuery.noConflict();
	loadDT(start, end);		
	});';
	$vendor                    = SPINKX_CONTENT_PLUGIN_URL . '/vendor/';
	wp_enqueue_style( 'bootstrap-datetimepicker-css', $vendor . 'bootstrap/css/bootstrap-datetimepicker.css', array(), true );
	wp_register_script( 'jquery', ( 'https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js' ), array(), '2.0.3', true );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-bootstrap-js', $vendor . 'bootstrap/js/bootstrap.min.js', array(), true, true );
	wp_localize_script( 'jquery-bootstrap-js', 'spinkxPUrl', array( 'pluginsUrl' => plugins_url( 'spinkx-content-marketing/' ) ) );
	wp_enqueue_media();
	wp_enqueue_script( 'jquery-youtubeapi', 'https://www.youtube.com/iframe_api' );
	wp_add_inline_script( 'jquery-bootstrap-js', $custom_js );
	wp_enqueue_script( 'jquery-daterange-picker', '//cdn.jsdelivr.net/bootstrap.daterangepicker/2/daterangepicker.js' );
	$spost = spnxHelper::getFilterPost();
	if ( is_array( $settings ) && isset( $settings['site_id'] ) && esc_attr( $settings['site_id'] ) ) {
		if ( isset( $spost['add_camp'] ) ) {
			$validate = ( new spnxHelper() )->campaignValidation( $spost );
			if ( isset( $validate['success'] ) && $validate['success'] ) {
				if ( ! ( isset( $spost['c_id'] ) && $spost['c_id'] > 0 ) ) {
					if ( ! isset( $spost['is_video'] ) ) {
						if ( $spost['image_attachment_id'] > 0 ) {
							$image_attributes      = wp_get_attachment_image_src( $spost['image_attachment_id'], 'full' );
							$spost['ad_image_url'] = $image_attributes[0];
						}
					}
					$spost['access_key'] = $settings['license_code'];
					$spost['site_id']    = $settings['site_id'];
					$url                 = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/campaign/create';
					$response            = spnxHelper::doCurl( $url, $spost );
				} else {
					if ( $spost ) {
						if ( ! isset( $spost['is_video'] ) ) {
							if ( $spost['image_attachment_id'] > 0 ) {
								$image_attributes      = wp_get_attachment_image_src( $spost['image_attachment_id'], 'medium' );
								$spost['ad_image_url'] = $image_attributes[0];
							}
						}
						$spost['access_key'] = $settings['license_code'];
						$spost['site_id']    = $settings['site_id'];
						$url                 = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/campaign/update';
						$response            = spnxHelper::doCurl( $url, $spost );

					}
				}
				$response = json_decode( $response );
				if ( isset( $response->error ) ) {
					echo $response->message;
				}
			} else { ?>
		<script>alert('<?php echo $validate['msg']; ?>')</script>
				<?php
			}
		}
		$url           = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/campaign/form-elements';
		$output        = spnxHelper::doCurl( $url, true );
		$output        = json_decode( $output, true );
		$currencyClass = '';
		if ( isset( $output['countries'] ) ) {
			$countries = $output['countries'];
		}
		if ( isset( $output['categories'] ) ) {
			$categories = $output['categories'];
		}
		if ( isset( $output['languages'] ) ) {
			$languages = $output['languages'];
		}
		if ( isset( $output['currencyClass'] ) ) {

			$currencyClass = $output['currencyClass'];
		}
		if ( isset( $output['cpm'] ) ) {
			$cpm = $output['cpm'];
		}
		if ( isset( $output['cpc'] ) ) {
			$cpc = $output['cpc'];
		}
		$group_name = '';
		if ( isset( $output['groupname'] ) ) {
			$group_name = $output['groupname'];
		}
		if ( isset( $output['error'] ) && $output['error'] === false ) {
			wp_localize_script(
				'jquery-bootstrap-js',
				'spinkxJs',
				array(
					'categories'    => $categories,
					'languages'     => $languages,
					'countries'     => $countries,
					'currencyClass' => $currencyClass,
					'cpm'           => $cpm,
					'cpc'           => $cpc,
					'groupList'     => $group_name,
				)
			);
		}
	} else {

		$output = array( 'error' => 'You are not a registered user. You can create a boost post after registration. Click Here to <a href="admin.php?page=spinkx-site-register">Register</a>' );

	}
	?>
<?php if ( isset( $output['error'] ) && $output['error'] === false ) { ?>
	<div class="campaign_page col-sm-12 col-md-12">
	<div style="float: right;display:none">Balance:<span id="user-balance"><i class="fa <?php echo $currencyClass; ?>"></i><?php echo $output['user_bal']; ?></span>

	</div>
	<?php
} else {
	echo isset( $output['error'] ) ? $output['error'] : '';
	?>
	<script>
	jQuery('.spnx_wdgt_wrapper').hide();
</script>
<?php } ?>
<?php if ( isset( $output['error'] ) && $output['error'] === false ) { ?>
<div class="content_playlist_listing" style="display: none;">
	<table id="bwki_sites_display" class="wp-list-table table-responsive"  style="width:1024px;"><thead>
		<tr>
			<td style="padding: 0px;text-align: right;">
			
				
			</td>
			<td style="padding: 0px;">
				<span class="duration-text-first-span-span-crnt">
					Wallet Balance:
					<span class="pts_mny_cmn_cls">
					<i class="fa <?php echo $currencyClass; ?>"></i><?php printf( '%.2f', $output['user_bal'] ); ?>
					</span>
				</span>
			</td>

			<td style="padding: 0px;">
				<button class=" buy-more-point" id="add_money_wallet"  onclick="jQuery('#campaignmodaladdMoney').modal({
					backdrop: 'static',
					keyboard: false,
					show: true
				})">Add Money to Wallet</button>
				<span style="display: none;"><img src="<?php echo esc_url( SPINKX_CONTENT_DIST_URL ); ?>images/sort-icon.png" style="height: 15px; margin-right: 7px;"> <a href="#" id="sortby_global_reach">Views</a>|<a href="#" id="sortby_global_ctr">Engagement</a></span>
				<span style="display: none;"><img src="<?php echo esc_url( SPINKX_CONTENT_DIST_URL ); ?>images/sort-icon.png" style="height: 15px; margin-right: 7px;"><a href="#" id="sortby_local_reach">Views</a>|<a href="#" id="sortby_local_ctr">Engagement</a></span>
				</td>
		</tr>
		<tr style="background-color: #e4eff4 !important;height: 30px;font-size: 12px; color:#0170b6;">
			<th style="padding: 0px;border:none;width:475px;">&nbsp;&nbsp;&nbsp;Campaign Details</th>
			<th style="padding: 0 0 0 12px;border:none;width:252px;">&nbsp;&nbsp;&nbsp;Campaign Statistics </th>
			<th style="padding: 0 0 0 0px;border:none;width:252px; text-align:center; background-color: #337ab7 !important;">&nbsp;&nbsp;&nbsp; <button  id="button-create-ad" onclick="createAd(this)"><span  style="font-size:12px;">Create Ad </span><span style="font-size:13px;">+</span></button></th>
		</tr>

		</thead><tbody><input type="hidden" id="chooks" value="0" /></tbody>
		</table></div>
<div class="clear"></div>
	<div style="display: none">
	<?php
	if ( isset( $output['add_money'] ) && $output['add_money'] ) {
		echo $output['add_money'];
		echo do_shortcode( $output['add_money'] );
	}

	?>
	</div>
	<div id="campaignmodaladdMoney" style="z-index: 9999;" class="modal fade" role="dialog">
		<div class="modal-dialog modal_dialog_main_cntanr">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header modal_header_cc_spnkx">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title"><i class="far fa-credit-card"></i><strong>Add Money to Wallet</strong></h4>
				</div>
			<form target="_blank" class="paypal" action="<?php echo SPINKX_CONTENT_BAPI_URL; ?>/payments" method="post" id="paypal_form_buypoint">
				<div class="modal-body modal_body_cc_spnkx">
					<div class="cmn_cntnt_body_mdl amt_cnttnt_dv_cls">
						<span class="cmpgn-dv-entr-amnt" for="point_amount">Enter Amount</span>
							<span> 
								<i class="fa <?php echo $currencyClass; ?>"></i>
								<input type="text" class="form-control" id="wallet_amount" name="wallet_amount" value="100"/>
							</span>
						   
					</div>
					<div class="cmn_cntnt_body_mdl note_mny_cmn_class">
						<span>You do not need to add money if you already have money in your SPINKX wallet.</span>
						
					</div>
					
				</div>
				<div class="modal-footer modal_footer_cc_spnkx">
										 <div style="width: 150px;height: 20px;">
						<input type="image" id="payment-method-button" src="<?php echo SPINKX_CONTENT_DIST_URL; ?>images/papal-checkout-button-spinkx.png" border="0" name="submit" alt="PayPal – The safer, easier way to pay online!" style="border:0;padding:0;
	float: left;
	margin-left: 24px;
	width: 100%;
	">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
						</div>

					<!--<button data-dismiss="modal">CANCEL</button> -->

				</div>
			</div>
				<input type="hidden" name="cmd" value="_zclick" />
				<input type="hidden" name="no_note" value="<?php echo $settings['reg_email']; ?>" />
				<input type="hidden" name="bn" value="<?php echo $settings['license_code']; ?>" />
				<input type="hidden" name="payer_email" value="<?php echo $spnx_admin_manage->getCurrentUserEmail(); ?>" />
				<input type="hidden" name="return" value="<?php echo spnxHelper::getCurrentURL();?>" />
				<input type="hidden" name="item_number" value="<?php echo $settings['site_id']; ?>" />

			</form>
		</div>
	</div>
	<?php
	add_action( 'admin_footer', 'spinkx_cont_media_selector_print_scripts' );
}
