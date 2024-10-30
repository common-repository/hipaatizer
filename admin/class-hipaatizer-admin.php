<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    HIPAAtizer
 * @subpackage HIPAAtizer/admin
 * @author     HIPAAtizer
 */
class HIPAAtizer_Admin {
	private $hipaatizer;
	private $version;

	public function __construct( $hipaatizer, $version ) {

		$this->hipaatizer = $hipaatizer;
		$this->version = $version;
        add_action( 'admin_menu', array( $this, 'hipaa_admin_menu' )  );
		add_action( 'init', array( $this, 'hipaatizer_id' )  );
		add_action( 'init', array( $this, 'hipaa_whiteLabet' )  );
		add_action( 'init', array( $this, 'hipaa_transfer_cf7_uregistered' )  );
		add_action( 'init', array( $this, 'hipaa_transfer_wpf_uregistered' )  );
		add_action( 'init', array( $this, 'hipaa_transfer_gf_uregistered' )  );
		add_filter( 'script_loader_tag', array( $this,'hipaa_script_tags'), 10, 2);
		add_action( 'wp_ajax_refresh_hipaa_forms',  array( $this, 'hipaa_refresh_hipaa_forms' ) );
        add_action( 'wp_ajax_nopriv_refresh_hipaa_forms',  array( $this, 'hipaa_refresh_hipaa_forms' ) );
		add_action( 'wp_ajax_tabs_hipaa_forms',  array( $this, 'hipaa_tabs_hipaa_forms' ) );
        add_action( 'wp_ajax_nopriv_tabs_hipaa_forms',  array( $this, 'hipaa_tabs_hipaa_forms' ) );


	}

    public function hipaatizer_id() {
		global $wpdb, $hipaaID, $hipaa_message, $cf7key, $message, $site_id;

		$site_id  = ( is_multisite() ) ? get_current_blog_id() : '';
		$dbprefix = ( is_multisite() ) ? $wpdb->get_blog_prefix(0) : $wpdb->prefix;
		$cf7key   = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));

		if( !empty( $site_id )):
		$hipaaID = $wpdb->get_var( $wpdb->prepare( "SELECT hipaatizer_id FROM `{$dbprefix}hipaatizer` WHERE site_id=%s", $site_id) );
		$rowID   = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$dbprefix}hipaatizer` WHERE site_id=%s", $site_id) );
		
		else:
			$hipaaID = $wpdb->get_var( "SELECT hipaatizer_id FROM `{$dbprefix}hipaatizer`" );
			$rowID   = $wpdb->get_var( "SELECT id FROM `{$dbprefix}hipaatizer`" );
		endif;

		if( !empty($_GET['code']) ){
			$code = sanitize_key($_GET['code']);

			if( !empty($_GET['cf7']) ){

				$this->hipaa_transfer_cf7();

				if( $message == "Successful operation."){
					$curl =  HIPAATIZER_APP.'/api/v1/account/activate?code='.$code.'&contactForm7Id='.$cf7key;
				} else {
					$curl =  HIPAATIZER_APP.'/api/v1/account/activate?code='.$code;
				}

			} else {
				$curl =  HIPAATIZER_APP.'/api/v1/account/activate?code='.$code;
			}


            $response      = wp_remote_get( $curl);
            $body          = wp_remote_retrieve_body( $response );
			$res      	   = json_decode($body, true);
			$hipaa_message = ( !empty($res['message']) ) ? $res['message'] : '';

			if( $hipaa_message == '' ){
				$new_hipaaID  = sanitize_key($body);
				if( !empty($new_hipaaID)){

					if (isset($hipaaID)){
						if( !empty($site_id)) :
						$wpdb->query( $wpdb->prepare( "UPDATE `{$dbprefix}hipaatizer` SET hipaatizer_id = %s WHERE id = %s  AND site_id = %s", $new_hipaaID, $rowID, $site_id )  );
                        else:
						$wpdb->query( $wpdb->prepare( "UPDATE `{$dbprefix}hipaatizer` SET hipaatizer_id = %s WHERE id = %s", $new_hipaaID, $rowID )  );
						endif;
					} else {
						if( !empty($site_id)) :
							$wpdb->query( $wpdb->prepare( "INSERT INTO `{$dbprefix}hipaatizer`  (hipaatizer_id, site_id)  VALUES ( %s, %s)", $new_hipaaID, $site_id )  );
						else:
							$wpdb->query( $wpdb->prepare( "INSERT INTO `{$dbprefix}hipaatizer`  (hipaatizer_id)  VALUES ( %s )", $new_hipaaID )  );
						endif;
					}
					unset($_COOKIE['hipaaID']);
					setcookie('hipaaID', '', time() - 3600);
					$path = admin_url( 'admin.php' ).'?page=hipaatizer';
					wp_redirect( esc_url($path) );
                    exit;
				}

			}

		}
		if( !empty($_COOKIE['hipaaID'])){
			$new_hipaaID = sanitize_key($_COOKIE['hipaaID']);

			if( !empty($hipaaID)){
				if( !empty($site_id)) :
				$wpdb->query( $wpdb->prepare( "UPDATE `{$dbprefix}hipaatizer` SET hipaatizer_id = %s WHERE id = %s  AND site_id = %s", $new_hipaaID, $rowID, $site_id )  );
				else:
				$wpdb->query( $wpdb->prepare( "UPDATE `{$dbprefix}hipaatizer` SET hipaatizer_id = %s WHERE id = %s", $new_hipaaID, $rowID )  );
				endif;
			} else {
				if( !empty($site_id)) :
					$wpdb->query( $wpdb->prepare( "INSERT INTO `{$dbprefix}hipaatizer`  (hipaatizer_id, site_id)  VALUES ( %s, %s)", $new_hipaaID, $site_id )  );
				else:
					$wpdb->query( $wpdb->prepare( "INSERT INTO `{$dbprefix}hipaatizer`  (hipaatizer_id)  VALUES ( %s )", $new_hipaaID )  );
				endif;
			}
			unset($_COOKIE['hipaaID']);
			setcookie('hipaaID', '', time() - 3600);
			$path = admin_url( 'admin.php' ).'?page=hipaatizer';
			wp_redirect( esc_url($path) );
            exit;
		}


		if( isset($_GET['hipaa_logout']) && $_GET['hipaa_logout'] == 1){
			unset($_COOKIE['hipaaID']);
			setcookie('hipaaID', '', time() - 3600);
			if( !empty($site_id)) :
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$dbprefix}hipaatizer` WHERE  hipaatizer_id=%s AND site_id=%s", $hipaaID, $site_id  )  );
			else:
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$dbprefix}hipaatizer` WHERE  hipaatizer_id=%s", $hipaaID )  );
			endif;
		}
	}
	public function enqueue_styles() {

		wp_enqueue_style( $this->hipaatizer, plugin_dir_url( __FILE__ ) . 'css/hipaatizer-admin.css', array(), $this->version, 'all' );

	}


	public function enqueue_scripts() {
		global $wpdb, $hipaaID, $cf7key, $message, $site_id;
		$iframe = '';
		$screen = get_current_screen();
		$dbprefix = ( is_multisite() ) ? $wpdb->get_blog_prefix(0) : $wpdb->prefix;
		$login = '/login';

		if( !empty($site_id ) && !isset($hipaaID) ) {
			$siteID  = $wpdb->get_var( "SELECT site_id FROM `{$dbprefix}hipaatizer`");
			if( $site_id != $siteID ) {
				$login = '/login/logout-callback';

			}
		}

		if( !empty($_GET['hipaa_account']) && $_GET['hipaa_account'] == 'signup'  ) {


			if( !empty($_GET['cf7']) ){

				$this->hipaa_transfer_cf7();

				if( $message == "Successful operation."){
					$iframe = HIPAATIZER_APP.'/sign-up?utm_source=wppl&source='.get_site_url().'&contactForm7Id='.$cf7key;

				} else {
					$iframe = HIPAATIZER_APP.'/sign-up?utm_source=wppl&source='.get_site_url();

				}

			} else {
				$iframe = HIPAATIZER_APP.'/sign-up?utm_source=wppl&source='.get_site_url();

			}

		} elseif( !empty($_GET['hipaa_account']) && $_GET['hipaa_account'] == 'login' ) {
			if( !empty($_GET['cf7']) ){
				$this->hipaa_transfer_cf7();

				if( $message == "Successful operation."){
					$iframe = HIPAATIZER_APP.$login.'?utm_source=wppl&source='.get_site_url().'&contactForm7Id='.$cf7key.'&ignoreAuthed=true';
				} else {
					$iframe = HIPAATIZER_APP.$login.'?utm_source=wppl&source='.get_site_url().'&ignoreAuthed=true';
				}
			}  else {
				$iframe = HIPAATIZER_APP.$login.'?utm_source=wppl&source='.get_site_url().'&ignoreAuthed=true';
			}
		}
		$urlc = HIPAATIZER_APP.'/workflow/';
		if($screen->id == 'toplevel_page_hipaatizer'){
		wp_enqueue_script( $this->hipaatizer, plugin_dir_url( __FILE__ ) . 'js/hipaatizer-admin.js', array( 'jquery' ), $this->version, false );

		if( !isset($_GET['hipaa_account'])) {
		wp_enqueue_script( 'freshworks', 'https://widget.freshworks.com/widgets/72000002319.js', array( 'jquery' ), $this->version, false );

		}
		}

		wp_localize_script( $this->hipaatizer, 'hipaa_params', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'admin_url' =>	admin_url( 'admin.php' ),
		'url'      => $iframe,
		'pluginUrl' => admin_url(sprintf('admin.php?page=hipaatizer')),
		'curl'     => $urlc,
		'nonce'    => wp_create_nonce( 'hipaa_refresh_hipaa_forms_nonce' ),
	) );


	}

	public function hipaa_script_tags ( $tag, $handle ) {
        if ( 'freshworks' !== $handle ) {
            return $tag;
        }
        return str_replace( ' src', ' async defer src', $tag );
	}

	public function hipaa_whiteLabet() {
	global $hipaaID, $whiteLabelUrl;

	if ( $hipaaID != ''  ):

		$curl     = HIPAATIZER_APP.'/api/v1/account/'.$hipaaID.'/public_info';
		$response = wp_remote_get( $curl);
		$body     = wp_remote_retrieve_body( $response );
		$res      = json_decode($body, true);

		$whiteLabelUrl = ( isset($res['whiteLabelUrl']) ) ? $res['whiteLabelUrl'] : '';

		if($whiteLabelUrl != '' && strrev($whiteLabelUrl)[0]==='/') {
			$whiteLabelUrl = rtrim($whiteLabelUrl, '/');
		}

	endif;

	return $whiteLabelUrl;
}
    public function hipaa_admin_menu() {
        global $hipaaID;

		$domain = ( !empty( $this->hipaa_whiteLabet() ) ) ? $this->hipaa_whiteLabet() : HIPAATIZER_APP;
        add_menu_page(
            __( 'HIPAAtizer', 'hipaatizer' ),
            __( 'HIPAAtizer', 'hipaatizer' ),
            'manage_options',
            'hipaatizer',
            array( $this, 'hipaa_admin_content' ),
            plugin_dir_url( __FILE__ ).'img/icon.png',
            20
        );


		if ( $hipaaID != '') {

		add_submenu_page(
		'hipaatizer',
		__( 'HIPAAtizer Dashboard', 'hipaatizer' ),
		'<span class="item_target_blank">'.__( 'HIPAAtizer Dashboard', 'hipaatizer' ).'</span>',
		'manage_options',
        esc_url($domain.'/my-forms?utm_source=wppl')
			);

		if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ):

			add_submenu_page(
			'hipaatizer',
			__( 'Import CF7 Forms', 'hipaatizer' ),
			'<span id="item_import_cf7">'.__( 'Import CF7 Forms', 'hipaatizer' ).'</span>',
            'manage_options',
            '?page=hipaatizer&import_cf7=1'
			);

		endif;

		/* if( is_plugin_active( 'wpforms-lite/wpforms.php' ) || is_plugin_active( 'wpforms/wpforms.php' )):

			add_submenu_page(
				'hipaatizer',
				__( 'Import WPForms', 'hipaatizer' ),
				'<span id="item_import_wpf">'.__( 'Import WPForms', 'hipaatizer' ).'</span>',
				'manage_options',
				'?page=hipaatizer&import_wpf=1'
			);

		endif; 

		if( is_plugin_active( 'gravityforms/gravityforms.php' ) ):

			add_submenu_page(
				'hipaatizer',
				__( 'Import WPForms', 'hipaatizer' ),
				'<span id="item_import_gf">'.__( 'Import Gravity Forms', 'hipaatizer' ).'</span>',
				'manage_options',
				'?page=hipaatizer&import_gf=1'
			);

		endif;  */

		add_submenu_page(
			'hipaatizer',
			__( 'Create Form', 'hipaatizer' ),
			'<span class="item_target_blank">'.__( 'Create Form', 'hipaatizer' ).'</span>',
		'manage_options',
        esc_url($domain.'/create-workflow-wizard?utm_source=wppl&isAdminCreateOwnForm=false&isHipaasignForm=false&step=create-form')
			);

		add_submenu_page(
			'hipaatizer',
			__( 'Create HIPAAsign form', 'hipaatizer' ),
			'<span class="item_target_blank">'.__( 'Create HIPAAsign form', 'hipaatizer' ).'</span>',
		'manage_options',
        esc_url($domain.'/create-workflow-wizard?utm_source=wppl&isAdminCreateOwnForm=false&isHipaasignForm=true&step=create-form')
			);

		add_submenu_page(
			'hipaatizer',
			__( 'Create Workflow', 'hipaatizer' ),
			'<span class="item_target_blank">'.__( 'Create Workflow', 'hipaatizer' ).'</span>',
		'manage_options',
        esc_url($domain.'/workflows?utm_source=wppl&isDisplayCreateWorkflowModal=true')
			);

		add_submenu_page(
			'hipaatizer',
			__( 'Link another HIPAAtizer account', 'hipaatizer' ),
			'<span id="item_change_account">'.__( 'Link another HIPAAtizer account', 'hipaatizer' ).'</span>',
		'manage_options',
		'?page=hipaatizer&change_account=1'
			);

		}


    }
	public function hipaa_refresh_hipaa_forms(){

		check_ajax_referer( 'hipaa_refresh_hipaa_forms_nonce', 'nonce' );
		echo $this->hipaa_get_forms();
		wp_die();
	}

	public function hipaa_tabs_hipaa_forms(){

		echo $this->hipaa_get_forms();
		wp_die();
	}


	public function hipaa_transfer_cf7(){
		global $cf7key, $message;
		$arr = array();

		foreach($_GET['cf7'] as $val) {

		$form            = array();
		$cf7_post        = get_post($val);
		$form['id']      = $val;
		$form['title']   = $cf7_post->post_title;
		$form['content'] = $cf7_post->post_content;
		array_push($arr, $form);
		}

		$data = json_encode($arr);
		$url  = HIPAATIZER_APP.'/api/v1/sign_up/prepare_cf7_forms_for_import?utm_source=wppl&contactForm7Id='.$cf7key;

		$response = wp_remote_post( $url, array(
			'headers' => array(  'content-type' => 'application/json' ),
			'body'    => $data,
		) );
		$body    = wp_remote_retrieve_body( $response );
		$res     = json_decode($body, true);
		$message = $res['message'];

	}

	public function hipaa_transfer_cf7_uregistered(){
		global $cf7key, $message;
		if( !empty($_GET['cf7']) &&  !empty($_GET['hipaaID']) ){
			$this->hipaa_transfer_cf7();

			if( $message == "Successful operation."){
				if(!empty($_GET['hipaaID'])) {
					$url  = HIPAATIZER_APP.'/api/v1/account/import/contact_from_7?utm_source=wppl&contactForm7Id='.$cf7key.'&accountId='.sanitize_key($_GET['hipaaID']);
					$response = wp_remote_get( $url );
					$body     = wp_remote_retrieve_body( $response );
					$res      = json_decode($body, true);
					$message_import = $res['message'];
					if( $message_import == "Successful operation."){
						$url = admin_url( 'admin.php' )."?page=hipaatizer&import_cf7=success";
					} else {
					$url = admin_url( 'admin.php' )."?page=hipaatizer&import_cf7=error";
				}

				} else {
					$url = admin_url( 'admin.php' )."?page=hipaatizer&import_cf7=error";
				}
					wp_redirect( esc_url($url) );
					exit;
			}
		}
	}

	public function hipaa_transfer_wpf(){
			global $cf7key, $message;
			$arr = array();

			foreach($_GET['wpf'] as $val) {

				$form            = array();
				$wpf_post        = get_post($val);
				$form['id']      = $val;
				$form['title']   = $wpf_post->post_title;
				$form['content'] = $wpf_post->post_content;
					array_push($arr, $form);
			}

			$data = json_encode($arr); wp_die($data);
			$url  = HIPAATIZER_APP.'/api/v1/sign_up/prepare_cf7_forms_for_import?wpformsId='.$cf7key;


		$response = wp_remote_post( $url, array(
				'headers' => array(  'content-type' => 'application/json' ),
				'body'    => $data,
			) );
			$body    = wp_remote_retrieve_body( $response );
			$res     = json_decode($body, true);
			$message = $res['message'];


	}

	public function hipaa_transfer_wpf_uregistered(){
	global $cf7key, $message;
		if( !empty($_GET['wpf']) &&  !empty($_GET['hipaaID']) ){
			$this->hipaa_transfer_wpf();

			if( $message == "Successful operation."){
				if(!empty($_GET['hipaaID'])) {
				$url  = HIPAATIZER_APP.'/api/v1/account/import/contact_from_7?wpformsId='.$cf7key.'&accountId='.sanitize_key($_GET['hipaaID']);
				$response = wp_remote_get( $url );
				$body    = wp_remote_retrieve_body( $response );
				$res     = json_decode($body, true);
				$message_import = $res['message'];
			if( $message_import == "Successful operation."){
				$url = admin_url( 'admin.php' )."?page=hipaatizer&import_wpf=success";

				} else {
					$url = admin_url( 'admin.php' )."?page=hipaatizer&import_wpf=error";
				}

				} else {
			$url = admin_url( 'admin.php' )."?page=hipaatizer&import_wpf=error";
				}
				wp_redirect( esc_url($url) );
				exit;
			}
		}
	}

	public function hipaa_transfer_gf(){
		global $cf7key, $message;
		$arr = array();

		foreach($_GET['gf'] as $val) {

			$form            = array();
			$gf_post        = get_post($val);
			$form['id']      = $val;
			$form['title']   = $gf_post->post_title;
			$form['content'] = $gf_post->post_content;
				array_push($arr, $form);
		}

		$data = json_encode($arr); wp_die($data);
		$url  = HIPAATIZER_APP.'/api/v1/sign_up/prepare_cf7_forms_for_import?gravityformsId='.$cf7key;


	$response = wp_remote_post( $url, array(
			'headers' => array(  'content-type' => 'application/json' ),
			'body'    => $data,
		) );
		$body    = wp_remote_retrieve_body( $response );
		$res     = json_decode($body, true);
		$message = $res['message'];


}

public function hipaa_transfer_gf_uregistered(){
global $cf7key, $message;
	if( !empty($_GET['gf']) &&  !empty($_GET['hipaaID']) ){
		$this->hipaa_transfer_gf();

		if( $message == "Successful operation."){
			if(!empty($_GET['hipaaID'])) {
			$url  = HIPAATIZER_APP.'/api/v1/account/import/contact_from_7?gravityformsId='.$cf7key.'&accountId='.sanitize_key($_GET['hipaaID']);
			$response = wp_remote_get( $url );
			$body    = wp_remote_retrieve_body( $response );
			$res     = json_decode($body, true);
			$message_import = $res['message'];
		if( $message_import == "Successful operation."){
			$url = admin_url( 'admin.php' )."?page=hipaatizer&import_gf=success";

			} else {
				$url = admin_url( 'admin.php' )."?page=hipaatizer&import_gf=error";
			}

			} else {
		$url = admin_url( 'admin.php' )."?page=hipaatizer&import_gf=error";
			}
			wp_redirect( esc_url($url) );
			exit;
		}
	}
}

	public function hipaa_get_forms(){
		global $wpdb, $hipaaID, $domain;

		if ( $hipaaID != '' && !isset($_GET['hipaa_account']) && !isset($_GET['change_account']) ){

			$curl     = HIPAATIZER_APP.'/api/v1/account/'.$hipaaID.'/published_workflows';
			$response = wp_remote_get( $curl);
			$body     = wp_remote_retrieve_body( $response );
			$res      = json_decode($body, true);
			$results  = $res['workflows'];

			$type_form   = (isset($_GET['type'])) ? trim($_GET['type']) : 'SimpleForm';
			$folder_form = (isset($_GET['folderId'])) ? trim($_GET['folderId']) : '';
			$status_form = (isset($_GET['status'])) ? trim($_GET['status']) : '';
			$forms       = array();
			foreach ($results as $item) :
				if( $folder_form != ''  && $folder_form != '-1') {
					if( in_array($folder_form, $item, true) && in_array($type_form, $item, true) ){
						array_push($forms, $item);
					}
					} elseif ( $status_form != '' && $folder_form == '' ) {
						if( in_array($status_form, $item, true) && in_array($type_form, $item, true) ){
							array_push($forms, $item);
					}
				} else {
					if( in_array($type_form, $item, true) ){
						array_push($forms, $item);
					}
				}
			endforeach; ?>

		<div class="hipaa-loader"></div>
		<div class="hipaa-table-content">
		<table>
			<tr>
			<th><?php esc_html_e('Title', 'hipaatizer'); ?></th>
			<th><?php esc_html_e('Status', 'hipaatizer'); ?></th>
			<th><?php esc_html_e('Shortcode', 'hipaatizer'); ?></th>
			<!--<th><?php esc_html_e('Type', 'hipaatizer'); ?></th>-->
			<th class="submissions"><?php esc_html_e('Submissions', 'hipaatizer'); ?></th>
			<th width="64"><?php esc_html_e('Actions', 'hipaatizer'); ?></th>
			</tr>
			<?php
			if( $forms ):
			$nb_elem_per_page = 10;
			$page = isset($_GET['paged'])?intval($_GET['paged']-1):0;
			$number_of_pages = intval(count($forms)/$nb_elem_per_page)+1;

			foreach(array_slice($forms, $page*$nb_elem_per_page, $nb_elem_per_page)  as $row ){
				$form_id    = $row['id'];
				$form_title = $row['name'];
				$form_status = $row['status'];
				$form_type = ( array_key_exists('type', $row)) ? $row['type'] : 'SimpleForm';
				if( array_key_exists('contactFrom7Id', $row)):
					$cf7id  = $row['contactFrom7Id'];
					if ( function_exists('has_blocks') ) {
						$str1 = strval('<!-- wp:contact-form-7/contact-form-selector {"id":'.$cf7id.',"title":"'.$form_title.'"} -->');
						$str2 = strval('<!-- wp:hipaatizer/hipaa-form {"formID":"'.$form_id.'"} -->');
						$str3 = '<div class="wp-block-contact-form-7-contact-form-selector">[contact-form-7 id="'.$cf7id.'" title="'.$form_title.'"]</div>';
						$str4 = '<div class="hipaa-form" id="'.$form_id.'">[hipaatizer id="'.$form_id.'"]</div>';
						$update1 = "UPDATE $wpdb->posts SET post_content = REPLACE(post_content, %s, %s)";
						$wpdb->query( $wpdb->prepare( $update1, $str1, $str2) );
						$update2 = "UPDATE $wpdb->posts SET post_content = REPLACE(post_content, %s, %s)";
						$wpdb->query( $wpdb->prepare( $update2, $str3, $str4) );
					} else {
						$str1 = '[contact-form-7 id="'.$cf7id.'" title="'.$form_title.'"]';
						$str2 = '[hipaatizer id="'.$form_id.'"]';
						$update3 = "UPDATE $wpdb->posts SET post_content = REPLACE(post_content, %s, %s)";
						$wpdb->query( $wpdb->prepare( $update3, $str1, $str2 ) );
					}
				endif;
				echo '<tr>';
				echo '<td>'.esc_html($form_title).'</td>';
				echo '<td>';
				switch ( $form_status ){
					case 'Draft' :  $formStatus = '<span class="ant-typography ant-typography-warning">'.esc_html__('Draft', 'hipaatizer').'</span>'; break;
					case 'Archived' :  $formStatus = '<span class="ant-typography ant-typography-archive">'.esc_html__('Archived', 'hipaatizer').'</span>'; break;
					case 'Pending' :  $formStatus = '<span class="ant-typography ant-typography-pending">'.esc_html__('Pending', 'hipaatizer').'</span>'; break;
					default: $formStatus = '<span class="ant-typography ant-typography-success">'.esc_html__('Published', 'hipaatizer').'</span>'; break;
				}
				echo wp_kses( $formStatus, array( 'span' => array( 'class' => array() ) ) );
				echo '</td>';
				echo '<td class="shortcode">';
				if( $form_status == 'Published') {
					echo '<div class="d-flex">';
					echo '<span>[hipaatizer id="'.esc_attr($form_id).'"]</span>';
					echo '<a href="#" class="copyShortcode"><span class="tooltip">'.esc_html__('Get Shortcode', 'hipaatizer') .'</span><span class="tooltip_copied">'.esc_html__('Сopied to clipboard', 'hipaatizer') .'</span><img src="'.HIPAATIZER_PATH.'/admin/img/link-icon.png" alt="'.esc_attr__('Get Shortcode', 'hipaatizer') .'"></a>';
					echo '<span class="textShortcode">[hipaatizer id="'.esc_attr($form_id).'"]</span>';
					echo '</div>';
				} else {
					echo '&mdash;';
				}
				echo '</td>';
				// echo '<td>'.esc_html($form_type).'</td>';
				switch($type_form){
					case 'SimpleForm': $query_sub = 'submissions'; break;
					case 'HipaaSign': $query_sub = 'envelopes'; break;
					case 'Workflow': $query_sub = 'submission-group'; break;
				}
				echo '<td class="submissions"><a href="'.esc_url($domain.'/'.esc_attr($query_sub).'/'.esc_attr($form_id)).'?utm_source=wppl" target="_blank">'.esc_html__('View Submissions', 'hipaatizer') .'</a></td>';
				echo '<td>';
				if( $form_type == 'Workflow') {
					echo '<a href="'.esc_url($domain.'/workflows?utm_source=wppl&search='.esc_attr($form_title)).'" class="btn" target="_blank"><span class="tooltip">'.esc_html__('View', 'hipaatizer') .'</span></a>';
				} else {
					echo '<a href="'.esc_url($domain.'/form-builder/edit-workflow/'.esc_attr($form_id)).'?utm_source=wppl" class="btn" target="_blank"><span class="tooltip">'.esc_html__('Edit', 'hipaatizer') .'</span></a>';
				}

				echo '</td>';
				echo '</tr>';
			}

		else:
			echo '<tr><td colspan="5" class="has-text-align-center">'.esc_html__('There are no forms in this folder', 'hipaatizer').'</td></tr>';
		endif; ?>
			</table>
			</div>
			<?php if( $forms ): ?>
			<ul class="pagination d-flex">
				<?php if( $page > 0):  ?>
					<li><a href='<?php echo esc_url( admin_url( 'admin.php' ) ); ?>?page=hipaatizer&paged=<?php echo esc_attr($page); ?>'> < </a></li>
				<?php endif;

				for($i=1;$i<=$number_of_pages;$i++){

					$li_class = ( $page === ($i-1) ) ? 'current' : ''; ?>

					<li class="<?php echo esc_attr($li_class); ?>"><a href='<?php echo esc_url( admin_url( 'admin.php' ) ); ?>?page=hipaatizer&paged=<?php echo esc_attr($i); ?>'><?php echo esc_html($i); ?></a></li>

				<?php } ?>

				<?php if( $page < ($number_of_pages - 1) && $number_of_pages > 1):
						$page_next = $page+2;
				?>
					<li><a href='<?php echo esc_url( admin_url( 'admin.php' ) ); ?>?page=hipaatizer&paged=<?php echo esc_attr($page_next); ?>'> > </a></li>
				<?php endif; ?>
			</ul>
			<?php
			endif;
			}
}

	public function hipaa_existing_forms() { ?>
		<form id="export-wpcf7" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>?page=hipaatizer" method="post" class="export-forms">
			<?php
			$args = array(
            'numberposts' => -1,
            'post_type'   => 'wpcf7_contact_form',
            );
            $forms = get_posts( $args );
			if( !empty( $forms ) ): ?>

			<div class="hipaa-title">
				<h2 class="has-text-align-center c-black"><?php esc_html_e('Hello! Let\'s start HIPAAtizing your forms!', 'hipaatizer'); ?></h2>
				<p class="has-text-align-center"><?php esc_html_e('We found the following Contact Form 7 forms.  Please select the form(s) you want to work with in HIPAAtizer.', 'hipaatizer'); ?></p>
			</div>

			<label><input id="select_all" type="checkbox"> <strong>Select All</strong></label>

                <?php foreach ( $forms as $form ){
							echo '<label><input type="checkbox" name="cf7[]" value="'.sanitize_key($form->ID).'"> '.esc_html($form->post_title).'</label>';
				} ?>
				<p class="has-text-align-center mt-20"><input type="submit" value="<?php esc_attr_e('Continue', 'hipaatizer'); ?>" class="btn"></p>

			<?php endif; ?>


        </form>
        <p class="has-text-align-center"><a href="?page=hipaatizer&hipaa_account=signup"><?php esc_attr_e('Go to Sign Up without import', 'hipaatizer'); ?></a><br>
        <?php esc_html_e('Already have an account?', 'hipaatizer'); ?> <a href="?page=hipaatizer&hipaa_account=login"><?php esc_html_e('Log in', 'hipaatizer'); ?></a></p>
	<?php }

	public function hipaa_import_cf7() {
			global $hipaaID;
	?>
		<form id="export-wpcf7" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get" class="export-forms">
			<input type="hidden" name="page" value="hipaatizer">
			<input type="hidden" name="hipaaID" value="<?php echo sanitize_key($hipaaID); ?>">
			<?php
			$args = array(
            'numberposts' => -1,
            'post_type'   => 'wpcf7_contact_form',
            );
            $forms = get_posts( $args );

			if( !empty( $forms ) ): ?>

			<div class="hipaa-title">
				<h2 class="has-text-align-center c-black"><?php esc_html_e('Select forms you want to import into HIPAAtizer:', 'hipaatizer'); ?></h2>
			</div>
			<label><input id="select_all" type="checkbox"> <strong>Select All</strong></label>
                <?php foreach ( $forms as $form ){
					echo '<label><input type="checkbox" name="cf7[]" value="'.sanitize_key($form->ID).'"> '.esc_html($form->post_title).'</label>';
				} ?>
				<p class="has-text-align-center mt-20"><a href="?page=hipaatizer" class="btnrev mr-20"><?php esc_attr_e('Cancel', 'hipaatizer'); ?></a> <input type="submit" value="<?php esc_attr_e('Continue', 'hipaatizer'); ?>" class="btn"></p>
		<?php else: ?>
			<p class="has-text-align-center mt-20"><?php echo esc_html('The list of the forms is empty', 'hipaatizer'); ?></p>
		<?php endif; ?>

        </form>
	<?php }

	public function hipaa_import_wpf() {
		global $hipaaID;
	?>
	<form id="export-wpf" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get" class="export-forms">
		<input type="hidden" name="page" value="hipaatizer">
		<input type="hidden" name="hipaaID" value="<?php echo sanitize_key($hipaaID); ?>">
		<?php
		$args = array(
		'numberposts' => -1,
		'post_type'   => 'wpforms',
		);
		$forms = get_posts( $args );
		if( !empty( $forms ) ): ?>
			<div class="hipaa-title">
				<h2 class="has-text-align-center c-black"><?php esc_html_e('Select forms you want to import into HIPAAtizer:', 'hipaatizer'); ?></h2>
			</div>

			<label><input id="select_all" type="checkbox"> <strong>Select All</strong></label>

			<?php foreach ( $forms as $form ){
				echo '<label><input type="checkbox" name="wpf[]" value="'.sanitize_key($form->ID).'"> '.esc_html($form->post_title).'</label>';
			} ?>
			<p class="has-text-align-center mt-20"><a href="?page=hipaatizer" class="btnrev mr-20"><?php esc_attr_e('Cancel', 'hipaatizer'); ?></a> <input type="submit" value="<?php esc_attr_e('Continue', 'hipaatizer'); ?>" class="btn"></p>

		<?php else: ?>
			<p class="has-text-align-center mt-20"><?php echo esc_html('The list of the forms is empty', 'hipaatizer'); ?></p>
		<?php endif; ?>

	</form>
<?php }

	public function hipaa_import_gf() {
		global $wpdb, $hipaaID;
		$dbprefix = ( is_multisite() ) ? $wpdb->get_blog_prefix(0) : $wpdb->prefix;
	?>
	<form id="export-gf" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get" class="export-forms">
		<input type="hidden" name="page" value="hipaatizer">
		<input type="hidden" name="hipaaID" value="<?php echo sanitize_key($hipaaID); ?>">
		<?php
		$forms = $wpdb->get_results( "SELECT id, title FROM `{$dbprefix}gf_form` WHERE is_active = 1 AND is_trash = 0", OBJECT );
		if( !empty( $forms ) ): ?>
			<div class="hipaa-title">
				<h2 class="has-text-align-center c-black"><?php esc_html_e('Select forms you want to import into HIPAAtizer:', 'hipaatizer'); ?></h2>
			</div>

			<label><input id="select_all" type="checkbox"> <strong>Select All</strong></label>

			<?php foreach ( $forms as $form ){
				echo '<label><input type="checkbox" name="gf[]" value="'.sanitize_key($form->id).'"> '.esc_html($form->title).'</label>';
			} ?>
			<p class="has-text-align-center mt-20"><a href="?page=hipaatizer" class="btnrev mr-20"><?php esc_attr_e('Cancel', 'hipaatizer'); ?></a> <input type="submit" value="<?php esc_attr_e('Continue', 'hipaatizer'); ?>" class="btn"></p>

		<?php else: ?>
			<p class="has-text-align-center mt-20"><?php echo esc_html('The list of the forms is empty', 'hipaatizer'); ?></p>
		<?php endif; ?>

	</form>
	<?php }

	public function hipaa_signup_header(){ ?>
		<div class="hipaa-wrapper full-width">
		<div class="hipaa-header d-flex justify-content-between">
			<img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/logo.svg'); ?>" alt="HIPAAtizer">
			<a href="<?php echo esc_url(HIPAATIZER_APP.'/my-forms?utm_source=wppl'); ?>" class="d-flex" target="_blank"><img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/login-icon.png'); ?>" alt=""> <?php esc_html_e('My Account', 'hipaatizer'); ?></a>
		</div>
		<div class="hipaa-loader"></div>
		<div class="hipaa-fcontent d-flex">
			<div class="hipaa-form-code d-flex">
	<?php
	}

	public function hipaa_signup_footer(){ ?>
		</div>
				<div class="hipaa-img">
					<img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/features-image.jpg'); ?>" alt="">
				</div>

			</div>

		</div>
	<?php
	}


    public function hipaa_admin_content() {
		global $hipaaID, $hipaa_message, $domain;

		if ( $hipaaID != '' && !isset($_GET['hipaa_account']) && !isset($_GET['change_account'])  ):

			$curl     = HIPAATIZER_APP.'/api/v1/account/'.$hipaaID.'/public_info';
			$response = wp_remote_get( $curl);
			$body     = wp_remote_retrieve_body( $response );
			$res      = json_decode($body, true);

			$hipaa_email 	 = ( isset($res['email']) ) ? $res['email'] : '';
			$businessName  = ( isset($res['businessName']) ) ? $res['businessName'] : '';
			$type_form 	 = ( isset($_GET['type'])) ? trim($_GET['type'] ) : 'SimpleForm';
			$domain 		 = ( isset($res['whiteLabelUrl']) ) ? $res['whiteLabelUrl'] : HIPAATIZER_APP;

		if(strrev($domain)[0]==='/') {
			$domain = rtrim($domain, '/');
		}

		switch($type_form){
			case 'HipaaSign': $titleForm = esc_html__('HIPAAsign forms', 'hipaatizer'); break;
			case 'Workflow': $titleForm = esc_html__('Workflows', 'hipaatizer'); break;
			default:  $titleForm = esc_html__('My Forms', 'hipaatizer');
		}
	?>

		<div class="hipaa-wrapper">
			<div class="hipaa-header d-flex">
				<img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/logo.svg'); ?>" alt="HIPAAtizer">
				<div class="hipaa-username"><?php echo esc_html($hipaa_email); ?></div>
				<button type="button" class="hipaaIconMenu"><svg viewBox="64 64 896 896" focusable="false" data-icon="menu-fold" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M408 442h480c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8H408c-4.4 0-8 3.6-8 8v56c0 4.4 3.6 8 8 8zm-8 204c0 4.4 3.6 8 8 8h480c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8H408c-4.4 0-8 3.6-8 8v56zm504-486H120c-4.4 0-8 3.6-8 8v56c0 4.4 3.6 8 8 8h784c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8zm0 632H120c-4.4 0-8 3.6-8 8v56c0 4.4 3.6 8 8 8h784c4.4 0 8-3.6 8-8v-56c0-4.4-3.6-8-8-8zM115.4 518.9L271.7 642c5.8 4.6 14.4.5 14.4-6.9V388.9c0-7.4-8.5-11.5-14.4-6.9L115.4 505.1a8.74 8.74 0 000 13.8z"></path></svg></button>
				<div class="nav-menu">
				<button type="button" aria-label="Close" class="hipaaIconClose"><svg viewBox="64 64 896 896" focusable="false" data-icon="close" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M563.8 512l262.5-312.9c4.4-5.2.7-13.1-6.1-13.1h-79.8c-4.7 0-9.2 2.1-12.3 5.7L511.6 449.8 295.1 191.7c-3-3.6-7.5-5.7-12.3-5.7H203c-6.8 0-10.5 7.9-6.1 13.1L459.4 512 196.9 824.9A7.95 7.95 0 00203 838h79.8c4.7 0 9.2-2.1 12.3-5.7l216.5-258.1 216.5 258.1c3 3.6 7.5 5.7 12.3 5.7h79.8c6.8 0 10.5-7.9 6.1-13.1L563.8 512z"></path></svg></button>
				<ul class="nav d-flex">
					<li <?php if( !isset($_GET['type']) ): ?>class="active"<?php endif; ?></li><a href="?page=hipaatizer"><?php esc_html_e('My Forms', 'hipaatizer'); ?></a></li>
					<!-- <li <?php if( !empty($_GET['type']) && $_GET['type'] == 'HipaaSign'): ?>class="active"<?php endif; ?>><a href="?page=hipaatizer&type=HipaaSign"><?php esc_html_e('HIPAAsign', 'hipaatizer'); ?></a></li>-->
					<li <?php if( !empty($_GET['type']) && $_GET['type'] == 'Workflow'): ?>class="active"<?php endif; ?>><a href="?page=hipaatizer&type=Workflow"><?php esc_html_e('Workflows', 'hipaatizer'); ?></a></li>
					<li><a href="<?php echo esc_url($domain.'?utm_source=wppl'); ?>" target="_blank"><?php esc_html_e('HIPAAtizer Dashboard', 'hipaatizer'); ?></a></li>

				<?php if( is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ): ?>
					<li class="align-end"><a href="?page=hipaatizer&import_cf7=1" class="btnrev mr-8px beta-label"><?php esc_html_e('Import CF7 Forms', 'hipaatizer'); ?></a></li>
					<li><a href="<?php echo esc_url($domain.'/create-workflow-wizard?isAdminCreateOwnForm=false&step=create-form'); ?>" class="btn" target="_blank"><?php esc_html_e('Create Form', 'hipaatizer'); ?></a></li>

                <?php else: ?>
					<li class="align-end"><a href="<?php echo esc_url($domain.'/create-workflow-wizard?utm_source=wppl&isAdminCreateOwnForm=false&step=create-form'); ?>" class="btn" target="_blank"><?php esc_html_e('Create Form', 'hipaatizer'); ?></a></li>
				<?php endif; ?>

				</ul>
			</div>
		</div>

		<?php if( !empty($_GET['import_cf7']) && $_GET['import_cf7'] == 1): ?>
			<div class="maxw-762 mx-auto"><?php echo $this->hipaa_import_cf7(); ?></div>

			<?php elseif( !empty($_GET['import_wpf']) && $_GET['import_wpf'] == 1): ?>
				<div class="maxw-762 mx-auto"><?php echo $this->hipaa_import_wpf(); ?></div>

			<?php elseif( !empty($_GET['import_gf']) && $_GET['import_gf'] == 1): ?>
				<div class="maxw-762 mx-auto"><?php echo $this->hipaa_import_gf(); ?></div>
			<?php else: ?>

				<?php if( !empty($businessName) ): ?>
				<div class="hipaa-info d-flex">
					<?php echo sprintf( esc_html__('You are viewing the forms belonging to %s', 'hipaatizer'), $businessName) ; ?>
				</div>
			<?php endif; ?>

			<div class="hipaa-title d-flex">
				<h2><?php echo esc_html($titleForm); ?> <span class="hipaa-refresh" title="<?php esc_attr_e('Refresh', 'hipaatizer'); ?>" data-type=<?php echo esc_attr($type_form); ?>><img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/refesh-icon.svg'); ?>" alt=""></span></h2>
				<div class="hipaa-userdata d-flex">
					<?php echo esc_html($hipaa_email); ?><a href="?page=hipaatizer&change_account=1" class="hipaa-changeAccount"><img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/logout-icon.svg'); ?>" alt=""> <?php esc_html_e('Link another HIPAAtizer account', 'hipaatizer'); ?></a>
				</div>
			</div>

			<div class="hippaFormsContent d-flex">

				<?php if( $titleForm != 'Workflows'): ?>
				<div class="hipaa-tabs">
					<ul>
						<li class="active"><a href="#" data-folderId="-1" data-type="<?php echo esc_attr($type_form); ?>"><svg viewBox="64 64 896 896" focusable="false" data-icon="container" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M832 64H192c-17.7 0-32 14.3-32 32v832c0 17.7 14.3 32 32 32h640c17.7 0 32-14.3 32-32V96c0-17.7-14.3-32-32-32zm-40 824H232V687h97.9c11.6 32.8 32 62.3 59.1 84.7 34.5 28.5 78.2 44.3 123 44.3s88.5-15.7 123-44.3c27.1-22.4 47.5-51.9 59.1-84.7H792v-63H643.6l-5.2 24.7C626.4 708.5 573.2 752 512 752s-114.4-43.5-126.5-103.3l-5.2-24.7H232V136h560v752zM320 341h384c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8H320c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8zm0 160h384c4.4 0 8-3.6 8-8v-48c0-4.4-3.6-8-8-8H320c-4.4 0-8 3.6-8 8v48c0 4.4 3.6 8 8 8z"></path></svg> <?php esc_html_e('All', 'hipaatizer'); ?></a></li>
						<?php
						$curl_f     = HIPAATIZER_APP.'/api/v1/account/'.$hipaaID.'/folders';
						$response_f = wp_remote_get( $curl_f );
						$body_f     = wp_remote_retrieve_body( $response_f );
						$folders     = json_decode($body_f, true);
						for ($i = 0; $i < count($folders); $i++) {
							$folderId      = $folders[$i]['id'];
							$folderName    = $folders[$i]['name'];
							$hipaaSignForm = $folders[$i]['isHipaaSignForm'];
							if( $type_form == 'SimpleForm' && $hipaaSignForm == 1 ) { continue; }
							if( $type_form == 'HipaaSign' && $hipaaSignForm != 1 ) { continue; }
							?>
							<li><a href="#" data-folderId="<?php echo esc_attr($folderId); ?>" data-type="<?php echo esc_attr($type_form); ?>"><svg viewBox="64 64 896 896" focusable="false" data-icon="folder-open" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M928 444H820V330.4c0-17.7-14.3-32-32-32H473L355.7 186.2a8.15 8.15 0 00-5.5-2.2H96c-17.7 0-32 14.3-32 32v592c0 17.7 14.3 32 32 32h698c13 0 24.8-7.9 29.7-20l134-332c1.5-3.8 2.3-7.9 2.3-12 0-17.7-14.3-32-32-32zM136 256h188.5l119.6 114.4H748V444H238c-13 0-24.8 7.9-29.7 20L136 643.2V256zm635.3 512H159l103.3-256h612.4L771.3 768z"></path></svg> <?php echo esc_html($folderName); ?></a></li>
						<?php } ?>
						<li><a href="#" data-status="Archived" data-type="<?php echo esc_attr($type_form); ?>"><svg viewBox="64 64 896 896" focusable="false" data-icon="history" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M536.1 273H488c-4.4 0-8 3.6-8 8v275.3c0 2.6 1.2 5 3.3 6.5l165.3 120.7c3.6 2.6 8.6 1.9 11.2-1.7l28.6-39c2.7-3.7 1.9-8.7-1.7-11.2L544.1 528.5V281c0-4.4-3.6-8-8-8zm219.8 75.2l156.8 38.3c5 1.2 9.9-2.6 9.9-7.7l.8-161.5c0-6.7-7.7-10.5-12.9-6.3L752.9 334.1a8 8 0 003 14.1zm167.7 301.1l-56.7-19.5a8 8 0 00-10.1 4.8c-1.9 5.1-3.9 10.1-6 15.1-17.8 42.1-43.3 80-75.9 112.5a353 353 0 01-112.5 75.9 352.18 352.18 0 01-137.7 27.8c-47.8 0-94.1-9.3-137.7-27.8a353 353 0 01-112.5-75.9c-32.5-32.5-58-70.4-75.9-112.5A353.44 353.44 0 01171 512c0-47.8 9.3-94.2 27.8-137.8 17.8-42.1 43.3-80 75.9-112.5a353 353 0 01112.5-75.9C430.6 167.3 477 158 524.8 158s94.1 9.3 137.7 27.8A353 353 0 01775 261.7c10.2 10.3 19.8 21 28.6 32.3l59.8-46.8C784.7 146.6 662.2 81.9 524.6 82 285 82.1 92.6 276.7 95 516.4 97.4 751.9 288.9 942 524.8 942c185.5 0 343.5-117.6 403.7-282.3 1.5-4.2-.7-8.9-4.9-10.4z"></path></svg> <?php esc_html_e('Archive', 'hipaatizer'); ?> </a></li>

					</ul>
				</div>
				<?php endif; ?>
				<div id="hipaa-list" class="hipaa-forms" <?php if( $titleForm == 'Workflows'): ?> style="width: 100%;" <?php endif; ?>>
					<?php  echo $this->hipaa_get_forms(); ?>
				</div>

			   </div><!-- //.hippaFormsContent -->
				<?php endif; ?>
			</div>
        <?php  elseif ( $hipaaID == '' && !isset($_GET['hipaa_account']) && !isset($_GET['change_account']) && is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && !isset($_POST['cf7'])  ):  ?>


		<div class="hipaa-wrapper">
			<div class="hipaa-header d-flex justify-content-between">
			<img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/logo.svg'); ?>" alt="HIPAAtizer">
			<a href="?page=hipaatizer&hipaa_account=login"><img src="<?php echo esc_url(HIPAATIZER_PATH.'/admin/img/logout-icon.svg'); ?>" alt=""> <?php esc_html_e('Log in', 'hipaatizer'); ?></a>
		</ul>
	</div>

	<div class="maxw-762 mx-auto"><?php echo $this->hipaa_existing_forms(); ?></div>

	</div>

	<?php  elseif (  !empty($_GET['hipaa_account']) && $_GET['hipaa_account'] != 'activation_code' ):
		echo '<div class="hipaa-iframe-container"></div>';
	elseif ( !empty($_GET['hipaa_account']) && $_GET['hipaa_account'] == 'activation_code' ):
			echo $this->hipaa_signup_header(); ?>
			<h2><?php esc_html_e('Activation Code', 'hipaatizer'); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="hipaatizer">
				<?php if( !empty($_GET['cf7'])): ?>
					<?php foreach($_GET['cf7'] as $val ): ?>
						<input type="hidden" name="cf7[]" value="<?php echo sanitize_key($val); ?>">
					<?php endforeach; ?>
				<?php endif; ?>

				<label>
					<span>*</span> <?php esc_html_e('Code', 'hipaatizer'); ?>
					<input type="text" name="code" required="required">
					<span class="error"><?php echo esc_html($hipaa_message); ?></span>
				</label>

				<button class="btn"><?php esc_html_e('Continue', 'hipaatizer'); ?></button>
			</form>
			<p><?php esc_html_e("Don't have an activation code?", 'hipaatizer'); ?> <a href="?page=hipaatizer&hipaa_account=signup"><?php esc_html_e('Sign Up', 'hipaatizer'); ?></a> <?php esc_html_e('or', 'hipaatizer'); ?> <a href="?page=hipaatizer&hipaa_account=login"><?php esc_html_e('Log in', 'hipaatizer'); ?></a></p>
			<?php echo $this->hipaa_signup_footer();
		else:
			echo $this->hipaa_signup_header(); ?>
				<h2 class="mb-0"><?php esc_html_e('Welcome to HIPAAtizer!', 'hipaatizer'); ?></h2>
				<h5><?php esc_html_e('Make Any Website HIPAA Compliant', 'hipaatizer'); ?></h5>
				<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="hipaatizer">
					<?php if( !empty($_POST['cf7'])): ?>
						<?php foreach($_POST['cf7'] as $val ): ?>
							<input type="hidden" name="cf7[]" value="<?php echo sanitize_key($val); ?>">
						<?php endforeach; ?>
					<?php endif; ?>
					<label><input type="radio" name="hipaa_account" value="signup" required="required"><?php esc_html_e("I want to Create an account", 'hipaatizer'); ?></label>
					<label><input type="radio" name="hipaa_account" value="login" required="required"><?php esc_html_e('I already have an account', 'hipaatizer'); ?></label>
					<label><input type="radio" name="hipaa_account" value="activation_code" required="required"><?php esc_html_e('I have an activation code', 'hipaatizer'); ?></label>

					<button class="btn"><?php esc_html_e('Continue', 'hipaatizer'); ?></button>
				</form>

				<?php echo $this->hipaa_signup_footer();
		endif;

	if (!empty($_GET['import_cf7']) && $_GET['import_cf7'] == 'success') :
		echo '<div class="hippa_message hippa_message_success">'.esc_html__('Forms have been successfully imported', 'hipaatizer') .'<span class="close_message">&times;</span></div>';
		elseif (!empty($_GET['import_cf7']) && $_GET['import_cf7'] == 'error') :
		echo '<div class="hippa_message hippa_message_error">'.esc_html__('Forms have not imported. Please try again.', 'hipaatizer') .'<span class="close_message">&times;</span></div>';
		endif;
    }

}
