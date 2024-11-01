<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$site_id = false;
global $wpdb;
$spnx_admin_manage = new spnxAdminManage();
$settings          = get_option( SPINKX_CONTENT_LICENSE );
$settings          = maybe_unserialize( $settings );
$logo_url          = SPINKX_CONTENT_DIST_URL . 'images/your_logo.png';
if ( isset( $settings ) && $settings ) {
	$site_id         = isset( $settings['site_id'] ) ? $settings['site_id'] : 0;
	$registeredemail = spnxHelper::getFilterVar( 'registeredemail' );
	if ( $registeredemail ) {
		$settings['reg_user_email'] = $registeredemail;
		$s                          = maybe_serialize( $settings );
		update_option( SPINKX_CONTENT_LICENSE, $s );
	}
}

if ( isset( $_POST['agree'] ) && $_POST['agree'] ) {
	$spost = $_POST;
	if ( $spost['image_attachment_id'] > 0 ) {
		$image_attributes = wp_get_attachment_image_src( $spost['image_attachment_id'], array( 50, 50 ) );
		if ( isset( $image_attributes[0] ) ) {
			$spost['logo_url'] = $image_attributes[0];
		}
	}
	$spost['cuser_email'] = $spnx_admin_manage->getCurrentUserEmail();
	// site info  is being updated
	$spost['spinkx_version'] = SPINKX_CONTENT_VERSION;
	if ( $site_id ) {
		$url            = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/site/update';
		$spost['sflag'] = 'site_update';
	} else {
		$url            = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/site/create';
		$spost['sflag'] = 'site_create';
	}
	$spost['site_email'] = get_option( 'admin_email' );
	$spost['site_url']   = get_site_url();
	$response            = spnxHelper::doCurl( $url, $spost );

	// Site name editing couldn't be completed as there are some associated issues for the license validation
	if ( $settings ) {
		$site_name = spnxHelper::getFilterVar( 'site_name', INPUT_POST );
		if ( $site_name ) {
			$settings['site_name'] = $site_name;
			update_option( SPINKX_CONTENT_LICENSE, maybe_serialize( $settings ) );
		}
	}

	if ( $response && ! $site_id ) {
		$output = json_decode( $response, true );
		if ( isset( $output['spnx_widget_list'] ) ) {
			update_option( 'spnx_widget_list', maybe_serialize( $output['spnx_widget_list'] ) );
			unset( $output['spnx_widget_list'] );
		}
		if ( ! isset( $output['message'] ) ) {
			$s = maybe_serialize( $output );
			update_option( SPINKX_CONTENT_LICENSE, $s );
			update_option( 'spnx_reg_update', true );
			// sync posts here.
			$settings  = get_option( SPINKX_CONTENT_LICENSE );
			$settings  = maybe_unserialize( $settings );
			$response  = $spnx_admin_manage->spinkx_cont_post_sync( $settings );
			$js_output = "<script>jQuery(document).ready(function () {
					$.growl.notice({
						message: 'Post Sync SuccessFully',
						location: 'tr',
						size: 'large'
					});
					window.location.replace('?page=spinkx_widget_design');
				});</script>";
			echo $js_output;
		} else {
			$settings             = get_option( SPINKX_CONTENT_LICENSE );
			$settings             = maybe_unserialize( $settings );
			$settings['reg_user'] = $output['reg_user'];
			$settings['due_date'] = $output['due_date'];
			$s                    = maybe_serialize( $settings );
			update_option( SPINKX_CONTENT_LICENSE, $s );
			update_option( 'spnx_reg_update', true );
			$js_output = "<script>jQuery(document).ready(function () {
					$.growl.notice({
						message: '" . $output['message'] . "',
						location: 'tr',
						size: 'large'
					});
				});</script>";
			echo $js_output;
		}
	} elseif ( $response ) {

			// when user forgets to agree to terms etc.
			$output = json_decode( $response, true );
		if ( isset( $output['spnx_widget_list'] ) ) {
			update_option( 'spnx_widget_list', maybe_serialize( $output['spnx_widget_list'] ) );
			unset( $output['spnx_widget_list'] );
		}
		if ( $output['message'] === 'Updated Successfully' ) {
			$settings             = get_option( SPINKX_CONTENT_LICENSE );
			$settings             = maybe_unserialize( $settings );
			$settings['reg_user'] = $output['reg_user'];
			$settings['due_date'] = $output['due_date'];
			$s                    = maybe_serialize( $settings );
			update_option( SPINKX_CONTENT_LICENSE, $s );
			update_option( 'spnx_reg_update', true );
			echo '<script>window.location.replace("?page=spinkx_widget_design");</script>';
		} else {

			if ( isset( $output['message'] ) ) {

					echo "<script>jQuery(document).ready(function () {
						jQuery.growl.error({
							message: '" . $output['message'] . "',
							location: 'tr',
							size: 'medium'
						});
					});</script>";

			}
		}
	}
}

	/******GET REQUEST FOR FORM ELEMENTS-CREATE*/
if ( ! $site_id ) {
	$api_form_elements_url = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/site/form-elements';
	$output                = spnxHelper::doCurl( $api_form_elements_url, true );
	$dropdown              = json_decode( $output );
	if ( isset( $dropdown->message ) ) {
		?>
<div class="spnx-reg-mn-cntainter">
	<div class="text-spninks">
		<div class="image-container-cls-reg-spnx"><img src="<?php echo esc_url( SPINKX_CONTENT_DIST_URL ); ?>spinkx-logo.png" /></div>
		<div class="reg-lbl-txt">REGISTRATION</div>
	</div>
	<div>
		<?php
		echo $dropdown->message;
		exit;
		?>
	</div>
</div>

		<?php
	}
	$buy_now = '';
	if ( isset( $dropdown->selected_site ) ) {
		$buy_now = $dropdown->selected_site->buy_now;
	}
	$selected_url = get_site_url();
	if ( class_exists( 'Domainmap_Utils' ) ) {
		$selected_url = \Domainmap_Utils::get_mapped_domain();
		// echo $selected_url;
	}
} /******GET REQUEST FOR FORM ELEMENTS-UPDATE*/
else {

	$api_form_elements_url = SPINKX_CONTENT_BAPI_URL . '/wp-json/spnx/v1/site/form-elements/' . $site_id;
	$output                = spnxHelper::doCurl( $api_form_elements_url, false );
	$dropdown              = json_decode( $output );
	$selected_url          = '';
	if ( isset( $dropdown->selected_site->site_url ) ) {
		$selected_url = $dropdown->selected_site->site_url;
	}
	$user_key = null;
	if ( isset( $dropdown->selected_site->user_key ) ) {
		$user_key = $dropdown->selected_site->user_key;

	}
	$business_name = '';
	if ( isset( $dropdown->selected_site->business_name ) ) {
		$business_name = $dropdown->selected_site->business_name;
	}
	$business_address = '';
	if ( isset( $dropdown->selected_site->address ) ) {
		$business_address = $dropdown->selected_site->address;
	}
	$business_city = '';
	if ( isset( $dropdown->selected_site->city ) ) {
		$business_city = $dropdown->selected_site->city;
	}
	$business_pincode = '';
	if ( isset( $dropdown->selected_site->pincode ) ) {
		$business_pincode = $dropdown->selected_site->pincode;
	}
	$business_state = '';
	if ( isset( $dropdown->selected_site->state ) ) {
		$business_state = $dropdown->selected_site->state;
	}
	$business_phone = '';
	if ( isset( $dropdown->selected_site->phone ) ) {
		$business_phone = $dropdown->selected_site->phone;
	}
	$business_paypal_id = '';
	if ( isset( $dropdown->selected_site->paypal_email_id ) ) {
		$business_paypal_id = $dropdown->selected_site->paypal_email_id;
	}
	$country_id = 0;

	if ( isset( $dropdown->selected_site->country_id ) ) {
		$country_id = $dropdown->selected_site->country_id;
	}
	$category_arr = array();
	if ( isset( $dropdown->selected_site->categories_id ) ) {
		$category_arr = explode( ',', $dropdown->selected_site->categories_id );

	}
	$buy_now = null;
	if ( isset( $dropdown->selected_site->buy_now ) && $dropdown->selected_site->buy_now ) {
		$buy_now .= $dropdown->selected_site->buy_now;
	}

	if ( isset( $dropdown->selected_site->logo_url ) && $dropdown->selected_site->logo_url ) {
		$logo_url = $dropdown->selected_site->logo_url;
	}
	$settings['due_date'] = isset( $dropdown->selected_site->due_date ) ? $dropdown->selected_site->due_date : null;
	if ( isset( $dropdown->selected_site->registeredemail ) ) {
		$registeredemail = $dropdown->selected_site->registeredemail;
		if ( isset( $registeredemail ) ) {
			$settings['reg_user_email'] = $registeredemail;
			$s                          = maybe_serialize( $settings );
			update_option( SPINKX_CONTENT_LICENSE, $s );
		}
	}
}
// gets mapped domain if it's in use to use actual domain
if ( class_exists( 'Domainmap_Utils' ) ) {
	$obj  = new Domainmap_Utils();
	$temp = $obj->get_mapped_domain();
	if ( $temp ) {
		$selected_url = $temp;
	}
}

if ( ! $selected_url ) {
	$selected_url = get_site_url();
}
$userkey = spnxHelper::getFilterVar( 'userkey' );
if ( $userkey ) {
	$user_key = $userkey;
}
$datetime = null;
$color    = '#469fa1';

if ( isset( $settings['due_date'] ) && $settings['due_date'] != '0000-00-00 00:00:00' ) {
	$datetime = strtotime( $settings['due_date'] );

	$diff = $spnx_admin_manage->spinkx_cont_get_license_date( $settings['due_date'] );
	if ( intval( $diff->format( '%R%a days' ) ) < 0 ) {
		$color = '#f69fa1';
		// do_action( 'network_admin_notices' );
	}
}

$plugin_type_id = isset( $dropdown->selected_site->plugin_type_id ) ? intval($dropdown->selected_site->plugin_type_id) : 0;

?>
<div class="spnx_wdgt_wrapper"><div class="cssload-loader"></div></div>
<script src="//www.paypalobjects.com/api/checkout.js"></script>
<!--    <script src="<?php echo SPINKX_CONTENT_DIST_URL; ?>/js/test.js"></script> -->
<script type='text/javascript'>
	/* <![CDATA[ */
	var spnx_sec_cats = <?php echo wp_json_encode( isset( $dropdown->categories ) ? $dropdown->categories : '' ); ?>; /* ]]> */
</script>
<div class="spnx-reg-mn-cntainter">
	<div class="text-spninks">
	<div class="image-container-cls-reg-spnx"><a target="_blank" href="https://www.spinkx.com"><img src="<?php echo SPINKX_CONTENT_DIST_URL; ?>images/spinkx-logo.png" /></a></div>
	<div class="reg-lbl-txt">REGISTRATION</div>
	</div>
	<?php if ( $settings['due_date'] !== '0000-00-00' ) { ?>
		<?php if ( isset( $diff ) && intval( $diff->format( '%R%a days' ) ) < 0 ) { ?>
	<div class="spnx-dshb-mn-cntr">
		<div class="spnx-sec-mn-cntr" >
			<div class="spnx-sec-one-chld-mn-cntnr cmn-cls-spnx-tab dbd-banner-img" style="width: 99.2%;">
				<div class="points-cmn-cls-spnx" style="font-size: 12px;font-weight: 600;">



							<div style="font-size: 10px;width: 120px;float: right;margin-right: 9%;" class="purchase-plugin dashb-buy-points"><form target="_blank" class="paypal" action="<?php echo SPINKX_CONTENT_BAPI_URL; ?>/payments" method="post" id="paypal_form">
									<input type="hidden" name="cmd" value="_xclick" />
									<input type="hidden" name="hosted_button_id" value="YT4RDQ6GGRCLA">
									<input type="hidden" name="no_note" value="<?php echo $settings['reg_email']; ?>" />
									<input type="hidden" name="bn" value="<?php echo $settings['license_code']; ?>" />
									<input type="hidden" name="payer_email" value="<?php echo $spnx_admin_manage->getCurrentUserEmail(); ?>" />
                                    <input type="hidden" name="return" value="<?php echo spnxHelper::getCurrentURL();?>" />
									<input type="hidden" name="item_number" value="<?php echo $settings['site_id']; ?>" />
                                    <select name="os0" style="margin-left: 33px; width: 180px; background: #fff !important;">
										<?php if($dropdown->selected_site->currency === 'INR') { ?>
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
	<?php } ?>
	<form method="post" enctype="multipart/form-data">
	<div class="spnx-sec-mn-cntainter">

		<div class="spnx-stckr-cmn-class spnx-box-reg-cmn-cls">

			<div class="cmn-cls-verticl-bus-spnx-reg-sitename">
				<div style="width:40px; height: 40px; margin-bottom: 10px; display: inline-block; margin-right: 24px;">
					<img  alt="" id="image-preview"  style="width: 40px; height: 40px; object-fit:contain;" src="<?php echo $logo_url; ?>">
					<input type="hidden" name="image_attachment_id" id="image_attachment_id">
				</div>
				<span class="reg_upload_file_type catog-des-cmn-cls-spnx-reg reg-upload-logo">
					<i class="fas fa-file"></i>
					Add Logo
				</span>
				<span class="error-cmn-clas-spnx">Logo Required</span>

				
				
			</div>
			<div class="vrticl-align-cmn-cls-spnx-reg">
				<span class="catog-des-cmn-cls-spnx-reg">Site url </span>
				<span class="horizntal-align-cmn-cls-spnx-reg align-spnx-css"> <?php echo $selected_url; ?></span>
				<input type="hidden" name="site_url" value="<?php echo $selected_url; ?>">
			</div>
			<div class="cmn-cls-verticl-bus-spnx-reg-sitename ">
				<span class="catog-des-cmn-cls-spnx-reg">Site name </span>
				<span class="horizntal-align-cmn-cls-spnx-reg">
				<?php if ( isset( $settings['site_name'] ) && $settings['site_name'] ) { ?>
					<input type="text" class="awsome_input-spnx-reg-sitename" name="site_name" required value="<?php echo $settings['site_name']; ?>"/>
				<?php } else { ?>
					<input type="text"  class="awsome_input-spnx-reg-sitename" name="site_name" required value="<?php echo get_bloginfo( 'name' ); ?>"/>
				<?php } ?>
				</span>
			</div>
			
		</div>
<?php //echo $plugin_type_id; ?>
		<div class="spnx-box-reg-cmn-cls">
			<div class="header-cmn-cls-spnx">Tell us about your goals. Choose one</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" name="plugin-type" id="plugin-type-1" value="1"  
				<?php

				if ( 1 === $plugin_type_id ) {
					echo "checked='checked'"; }
				?>
				/></div>
				<div class="label-cntnr-mn-cls-cmommon-sec"> I want to only earn Revenue. I have more than 100,000 visitors per month.</div>
			</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" value="2" name="plugin-type" id="plugin-type-2" 
				<?php
				if ( 2 === $plugin_type_id ) {
					echo "checked='checked'"; }
				?>
				/></div>
				<div  class="label-cntnr-mn-cls-cmommon-sec"> I want to grow my website traffic for free + SEO + Backlinks & multiply my Ad Revenue by upto 15% per mon</div>
			</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" value="3" name="plugin-type" id="plugin-type-3" 
				<?php
				if ( 3 === $plugin_type_id ) {
					echo "checked='checked'"; }
				?>
				/></div>
				<div  class="label-cntnr-mn-cls-cmommon-sec"> My site is new. I want to grow my website traffic for Free + SEO + Backlinks </div>
			</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" value="4" name="plugin-type" id="plugin-type-4" 
				<?php
				if ( 4 === $plugin_type_id ) {
					echo "checked='checked'"; }
				?>
				/></div>
				<div  class="label-cntnr-mn-cls-cmommon-sec"> I want to promote my website. Product or Services. I do not do any blogging or content marketing.</div>
			</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" value="5" name="plugin-type" id="plugin-type-5" 
				<?php
				if ( 5 === $plugin_type_id ) {
					echo "checked='checked'"; }
				?>
				/></div>
				<div  class="label-cntnr-mn-cls-cmommon-sec"> I am a Digital Ad-agency. I would like to run campaigns for my Clients & make money from client margins.</div>
			</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" value="6" name="plugin-type" id="plugin-type-6" 
				<?php
				if ( 6 === $plugin_type_id ) {
					echo "checked='checked'"; }
				?>
				/></div>
				<div  class="label-cntnr-mn-cls-cmommon-sec"> I am a Multisite Network. I want to earn commissions out of every sales of plugin or Ad Revenue.</div>
			</div>
			<div class="spnx-rdio-dv-cmn-cls">
				<div class="radio-cntnr-mn-cls-cmommon"><input type="radio" value="7" name="plugin-type" id="plugin-type-6" 
				<?php
				if ( 7 === $plugin_type_id ) {
					echo "checked='checked'";}
				?>
				/></div>
				<div  class="label-cntnr-mn-cls-cmommon-sec">I would like to build my own Ad network. i want a White Labeled version of Spinkx. I can earn large commission from the network i build. </div>
			</div>
			<div class="error-cmn-clas-spnx chck-radio-btn-error-cls">Please Select Your Goal</div>
			<div class="spnx-knw-more-cmn-cls"><a href="https://www.spinkx.com/use-case/" target="_blank">Know More</a></div>
		</div>

	</div>
	<div class="spnx-sec-mn-cntainter">
		<div class="spnx-stckr-cmn-class spnx-box-reg-cmn-cls">
			<div class="vrticl-align-cmn-cls-spnx-reg">
				<span class=" catog-des-cmn-cls-spnx-reg">Registered site email</span>
				<span class="horizntal-align-cmn-cls-spnx-reg"><?php echo esc_attr( $settings['reg_email'] ); ?> <br/></span>
			</div>
			<div class="vrticl-align-cmn-cls-spnx-reg " style="display: none;">
				<span class=" catog-des-cmn-cls-spnx-reg">Registered user email</span>
				<span class="horizntal-align-cmn-cls-spnx-reg"><?php echo isset( $settings['reg_user_email'] ) ? $settings['reg_user_email'] : ''; ?></span>
			</div>

		</div>
		<div class="spnx-box-reg-cmn-cls">
			<div class="header-cmn-cls-spnx">Geography & Language</div>
			<div class="catog-des-cmn-cls-spnx-reg vrticl-btm-cmn-cls-reg">Country</div>
			<div class="select-div-common-class-spnx-reg select-vrticl-align-cmn-cls" id="geography-spnx-reg" style="position: relative;">
				<div style="position: relative;">
				<select name="site_target" id="site-gerography-select" class="target-country-cmn">
					<?php
					foreach ( $dropdown->countries as $key => $value ) {
						$countries = '';
						if ( isset( $dropdown->selected_site->country_id ) && 1 === $key && ! $dropdown->selected_site->country_id ) {
							$countries = "selected='selected'";
						} elseif ( isset( $dropdown->selected_site->country_id ) && $key === $dropdown->selected_site->country_id ) {
							$countries = "selected='selected'";
						}
						echo '<option  value="' . esc_attr( $key ) . '" ' . esc_attr( $countries ) . '>' . esc_attr( $value ) . '</option>';
					}
					?>
				</select>
					<span class="cmn-arw-cmn-clas-dv"><i class="fa fa-sort-down fa-lg" aria-hidden="true"></i></span>
				</div>
			</div>
			<div class="catog-des-cmn-cls-spnx-reg vrticl-btm-cmn-cls-reg">Language</div>
			<div class="select-div-common-class-spnx-reg select-vrticl-align-cmn-cls" style="position: relative;">
				<div style="position: relative;">
				<select name="site_language">
					<?php
					foreach ( $dropdown->languages as $key => $value ) {
						$languages = '';
						if ( isset( $dropdown->selected_site->language_id ) && 1 === $key && ! $dropdown->selected_site->language_id ) {
							$languages = "selected='selected'";
						} elseif ( isset( $dropdown->selected_site->language_id ) && $key === $dropdown->selected_site->language_id ) {
							$languages = "selected='selected'";
						}
						echo '<option  value="' . esc_attr( $key ) . '" ' . esc_attr( $languages ) . '>' . esc_attr( $value ) . '</option>';
					}
					?>
				</select>
					<span class="cmn-arw-cmn-clas-dv"><i class="fa fa-sort-down fa-lg" aria-hidden="true"></i></span>

				</div>
			</div>

			<div class="spnx-knw-more-cmn-cls" style="margin-top: 11px;"></div>
		</div>

		<div class="spnx-box-reg-cmn-cls">
			<div class="header-cmn-cls-spnx">Category</div>
			<div class="catog-des-cmn-cls-spnx-reg vrticl-btm-cmn-cls-reg">Category describe what your business is, not what it does or sells.</div>
			<div class="catog-des-cmn-cls-spnx-reg vrticl-btm-cmn-cls-reg">Primary category</div>
			<div class="select-div-common-class-spnx-reg select-vrticl-align-cmn-cls">
				<div style="position: relative;"><select name="primary_category">
						<?php
						foreach ( $dropdown->primary_categories as $key => $value ) {
							$primary_cat = '';
							if ( isset( $dropdown->selected_site->pri_cat_id ) && $key === $dropdown->selected_site->pri_cat_id ) {
								$primary_cat = "selected='selected'";
							}
							echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $primary_cat ) . '>' . esc_attr( $value ) . '</option>';
						}
						?>
				</select>
					<span class="cmn-arw-cmn-clas-dv"><i class="fa fa-sort-down fa-lg" aria-hidden="true"></i></span>
			</div>


			</div>
			<div class="catog-des-cmn-cls-spnx-reg vrticl-btm-cmn-cls-reg">Additional category</div>
			<div class="select-div-common-class-spnx-reg select-vrticl-align-cmn-cls select-add-cat" style="position: relative; width:100%; display:none;">
				<div class="select-drtn-cls">
                    <?php ?>
					<select class="categories"  id="categories" multiple name="site_cat[]">
						<?php
							$categories          = $dropdown->categories;
							$selected_categories = '';
						foreach ( $categories as $key => $category ) {
							if ( $key == $dropdown->selected_site->pri_cat_id ) {
								$selected_categories = (array) $category;
								break;
							}
						}


							if ( $selected_categories ) {
								foreach ( $selected_categories as $key => $value ) {
									$category = '';
									if ( isset( $category_arr ) && in_array( $key, $category_arr ) ) {
										$category = "selected='selected'";
									}
									echo '<option  value="' . esc_attr( $key ) . '" ' . esc_attr( $category ) . '>' . esc_attr( $value ) . '</option>';
								}
							}

						?>
					</select>
					<div class="error-cmn-clas-spnx error-cmn-clas-spnx-additional-act">Please select at least one additional category</div>
				</div>
				<!--<span class="font-awesome-icon-align-cmn-cls-down-ctegry"><i class="fa fa-sort-desc" aria-hidden="true"></i></span>
					<span class="fa-time-cmn-cls-cross">
					<i class="fa fa-times" aria-hidden="true"></i>
					</span>-->
			</div>

			<div class="spnx-knw-more-cmn-cls" style="padding-bottom:14px; clear: both;"> </div>
		</div>
		<div class="lower-text-cmon-cls-spnx-reg">
			<span>
				<span class="pls-note-cmn-cls-spnx">Please note:</span>
				<span class="lower-color-cmn-cls-spnx-reg">Edit may be reviewed for quality and can take up to 3 days to published.</span>
				<span class="spnx-knw-more-cmn-cls lrn-more-spnx-reg"> <a href="https://www.spinkx.com/faqs/" target="_blank">Learn More</a></span>
			</span>
		</div>
	</div>
	<div class="spnx-sec-mn-cntainter">
		<div class="spnx-box-reg-cmn-cls bs-cmn-class-thrd">
			<div class="cmn-cls-verticl-bus-spnx-reg">
				<div class="catog-des-cmn-cls-spnx-reg">Business name</div>
				<div>
					<input class="awsome_input-spnx-reg" type="text" name="bussiness_name" value="<?php echo esc_attr( $business_name ); ?>" id="bussiness-name-spnx">
					<span class="awsome_input_border-spnx-reg"></span>
					<div class="error-cmn-clas-spnx">Business Name Required</div>
				</div>
				<span class="font-awesome-icon-align-cmn-cls"><i class="fa fa-user" aria-hidden="true"></i></span>
			</div>
			<div class="cmn-cls-verticl-bus-spnx-reg cntry-rgn-cmn-cls-spnx-reg">
				<div class="catog-des-cmn-cls-spnx-reg">Country / Region </div>
				<div>
					<select name="bussiness_region" class="random">
						<?php
						foreach ( $dropdown->countries as $key => $value ) {
							$countries = '';
							if ( isset( $dropdown->selected_site->country ) && 1 === $key && ! $dropdown->selected_site->country ) {
								$countries = "selected='selected'";
							} elseif ( isset( $dropdown->selected_site->country ) && $key === $dropdown->selected_site->country ) {
								$countries = "selected='selected'";
							}
							echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $countries ) . '>' . esc_attr( $value ) . '</option>';
						}
						?>
					</select>
				</div>
				<span class="font-awesome-icon-align-cmn-cls-down"><i class="fa fa-sort-down fa-lg" aria-hidden="true"></i></span>
			</div>
			<div class="cmn-cls-verticl-bus-spnx-reg">
				<div class="catog-des-cmn-cls-spnx-reg">Street Address</div>
				<div>
					<input class="awsome_input-spnx-reg" type="text" name="bussiness_street" value="<?php echo esc_attr( $business_address ); ?>" id="street-ad-bs-spnx">
					<span class="awsome_input_border-spnx-reg"></span>
					<div class="error-cmn-clas-spnx">Street Address Required</div>
				</div>
			</div>
			<div class="cmn-cls-verticl-bus-spnx-reg">
				<div class="catog-des-cmn-cls-spnx-reg">City </div>
				<div><input class="awsome_input-spnx-reg" type="text" name="bussiness_city" value="<?php echo esc_attr( $business_city ); ?>" id="city-bs-spnx">
					<span class="awsome_input_border-spnx-reg"></span>
					<div class="error-cmn-clas-spnx">City Required</div>
				</div>
			</div>
			<div class="cmn-cls-verticl-bus-spnx-reg">
				<div class="pin-code-main-cntnr">
					<div class="catog-des-cmn-cls-spnx-reg">Zip code</div>
					<div>
						<input class="awsome_input-spnx-reg" type="text" id="zip_code_number" name="bussiness_zip" value="<?php echo esc_attr( $business_pincode ); ?>">
						<span class="awsome_input_border-spnx-reg"></span>
						<div class="error-cmn-clas-spnx">Please Enter Valid Zip code</div>
					</div>
				</div>
				<div class="state-main-cntnr">
					<div class="catog-des-cmn-cls-spnx-reg">State</div>
					<div>
						<input class="awsome_input-spnx-reg" type="text" name="bussiness_state" value="<?php echo esc_attr( $business_state ); ?>" id="state-ad-bs-spnx">
						<span class="awsome_input_border-spnx-reg"></span>
						<div class="error-cmn-clas-spnx">State Required</div>
					</div>
				</div>
			</div>
			<div class="cmn-cls-verticl-bus-spnx-reg">
				<div class="catog-des-cmn-cls-spnx-reg">Main business phone</div>
				<div>
					<input class="awsome_input-spnx-reg" type="number" name="bussiness_phone" value="<?php echo esc_attr( $business_phone ); ?>" id="phone-ad-bs-spnx">
					<span class="awsome_input_border-spnx-reg"></span>
					<div class="error-cmn-clas-spnx">Please Enter Valid Phone Number</div>
				</div>
			</div>
		</div>
		<div class="spnx-box-reg-cmn-cls money-trnsfer-main-class-spnx-cntnr">
			<div class="header-cmn-cls-spnx">Money Tranfer to your account</div>
			<div>
				<input type="email" placeholder="Paypal email id" name="paypal_id" value="<?php echo esc_attr( $business_paypal_id ); ?>" id="paypal-bs-spnx">
				<div class="error-cmn-clas-spnx error-cmn-clas-spnx-paypl">Please Enter Valid Email</div>

			</div>
		</div>
		<div class="lower-text-cmon-cls-spnx-reg">
			<div>
				<span class="pls-note-cmn-cls-spnx"> Please note:</span><span class="lower-color-cmn-cls-spnx-reg"> Minimum Transfer amount $100. Payment made every month. It may take upto 15 working days to transfer money & we may ask you for your tax information as per your country law.</span>
			</div>
		</div>
		<div class="spnx-box-reg-cmn-cls">
			<div class="continue-spnx-reg-button-cntnr">
				<span style="font-size:13px;">
					<input type="checkbox" name="agree" id="checked-registration" <?php echo isset( $settings['reg_user'] ) ? 'checked' : ''; ?> />
					I agree with the
					<a target="_blank" href="http://www.spinkx.com/terms-conditions/">
						Terms & Conditions
					</a>
				</span>
				<span style="text-align: right;"><button type="submit" >SUBMIT</button></span>
				<div class="error-cmn-clas-spnx error-cmn-clas-spnx-terms-condition">Please Check Terms and conditions</div>
			</div>
		</div>

	</div>


	</form>



</div>
<?php
add_action( 'admin_footer', 'spinkx_cont_media_selector_print_scripts' );
function my_editor_image_sizes( $sizes ) {
	$sizes = array_merge(
		$sizes,
		array(
			'wide-image' => __( 'Image 900px wide' ),
		)
	);
	return $sizes;
}
add_filter( 'image_size_names_choose', 'my_editor_image_sizes' );

