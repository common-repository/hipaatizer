<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    HIPAAtizer
 * @subpackage HIPAAtizer/public
 * @author     HIPAAtizer
 */
class HIPAAtizer_Public {

	private $hipaatizer;
	private $version;

	public function __construct( $hipaatizer, $version ) {
		$this->hipaatizer = $hipaatizer;
		$this->version = $version;

		add_shortcode( 'hipaatizer', array($this, 'hipaa_shortcode') );

	}

	public function enqueue_styles() {

		wp_enqueue_style( $this->hipaatizer, plugin_dir_url( __FILE__ ) . 'css/hipaatizer-public.css', array(), $this->version, 'all' );

	}

	public function hipaa_shortcode ( $atts ){
		global $hipaaID, $whiteLabelUrl;
		$formID = $atts['id'];
		$curl     = HIPAATIZER_APP.'/api/v1/account/'.$hipaaID.'/published_workflows';
        $response = wp_remote_get( $curl);
        $body     = wp_remote_retrieve_body( $response );
        $res      = json_decode($body, true);
        $results  = $res['workflows'];
		$form     = array();

		if( !empty( $results ) ) {
			foreach ($results as $item) :
				if( in_array($formID, $item, true) ){
					array_push($form, $item);
				}
			endforeach;
		}
		
        if( !empty($whiteLabelUrl) ){
			return '<script id="'. $formID.'-script" src="'.$whiteLabelUrl.'/shared/hipaatizer-form-renderer.js"></script>
					<script>
					new Hipaatizer("'. $formID .'", false, "'. $whiteLabelUrl .'" ).render();	  
					</script>';
		} else {
            $param = ( !empty($form) && $form[0]['type'] == 'Workflow' ) ? 'true' : '';
			return '<script id="'. $formID.'-script" src="'.HIPAATIZER_APP.'/shared/hipaatizer-form-renderer.js"></script>
					<script>
					new Hipaatizer("'. $formID .'", '.esc_attr($param).').render();	  
					</script>';
		}
		

	}

}
