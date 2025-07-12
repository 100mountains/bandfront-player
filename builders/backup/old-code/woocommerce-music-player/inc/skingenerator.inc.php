<?php
if ( !is_admin() ) {
    print 'Direct access not allowed.';
    exit;
}

if ( ! class_exists( 'WCMP_SKIN_GENERATOR' ) ) {
	class WCMP_SKIN_GENERATOR {

		static private $model 			= "models/gemini-2.0-flash-lite"; // models/gemini-2.0-flash-001
		static private $base_url		= "https://generativelanguage.googleapis.com/v1beta/";

		static public function model_inference( $skin_description, $api_key ) {

			$inference_url 	= self::$base_url . self::$model . ":generateContent?key=" . $api_key;

			// Base CSS.
			$base_url  		= includes_url() . 'js/mediaelement/';
			$base_css_url  	= $base_url . 'wp-mediaelement.css';
			$base_css_path 	= ABSPATH . WPINC . '/js/mediaelement/mediaelementplayer-legacy.css';
			$schema 		= file_exists( $base_css_path ) ? file_get_contents( $base_css_path ) : $base_css_url;
			$schema 		= str_ireplace( 'url(', 'url(' . $base_url, $schema );

			// Update the URLs of
			// Generate the prompt.
			$prompt = "Generate CSS styles for a MediaElementPlayer skin based on the CSS styles:\n\n$schema\n\n";
			$prompt .= "The output must be in CSS format. You must use the scoping class name .wcmp-custom-skin. Do NOT use Markdown or enclose the response in triple backticks. Do not include any description or extra information.\n";
			$prompt .= "Skin description: $skin_description\nReturn only the CSS code:";

			$data = [ 'contents' => [[ 'parts' => [[ 'text' => $prompt ]] ]], "generationConfig" => [ "temperature" => 0.0, "topP" => 0.9 ] ];

			// Inferring the model to generate the form.
			$body = json_encode( $data );

			$response = wp_remote_post( $inference_url, [
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body' => $body,
				'timeout' => 60,
			]);

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$data = json_decode(wp_remote_retrieve_body($response), true);

			$exception = new Exception( __( 'Empty AI model answer', 'music-player-for-woocommerce' ) );

			if( empty( $data ) ) throw $exception;
			if ( ! empty( $data[ 'error' ] ) ) throw new Exception( $data['error']['message'] );
			try {
				$output = $data['candidates'][0]['content']['parts'][0]['text'];
				// Remove markdown characters from the beginning and end:
				$output = preg_replace('/^```css\s*(.*?)\s*```$/s', '$1', $output);
				$output = str_replace( '.wcmp-custom-skin .mejs-container', '.wcmp-custom-skin.mejs-container', $output );
			} catch ( Exception $err ) {
				throw $exception;
			}

			return $output;

		} // End model_inference.

	} // End class WCMP_SKIN_GENERATOR.
}

// Main code

/** CALL THE AI SKIN GENERATOR **/
if (
	! empty( $_POST['wcmp_skin_generator_description'] ) &&
	! empty( $_POST['wcmp_skin_generator_api_key'] )
) {

	$output = [];

	remove_all_actions( 'shutdown' );
	check_admin_referer( 'wcmp-ai-skin-generator', '_wcmp_nonce' );

	$skin_description = sanitize_textarea_field( wp_unslash( $_POST['wcmp_skin_generator_description'] ) );
	$api_key		  = sanitize_text_field( wp_unslash( $_POST['wcmp_skin_generator_api_key'] ) );

	// Re-check they are not empty.
	if ( ! empty( $skin_description ) && ! empty( $api_key ) ) {
		try {
			$output['success'] 	= WCMP_SKIN_GENERATOR::model_inference( $skin_description, $api_key );
		} catch ( Exception $err ) {
			$output['error'] = $err->getMessage();
		}

	} else {
		$output['error'] = __( 'Empty API Key or skin description', 'music-player-for-woocommerce' );
	}
	print json_encode( $output );
	exit;
} elseif (
	! empty( $_POST['wcmp_custom_skin'] )
) {
	remove_all_actions( 'shutdown' );
	check_admin_referer( 'wcmp-custom-skin-preview', '_wcmp_nonce' );

	$custom_skin = sanitize_textarea_field( wp_unslash( $_POST['wcmp_custom_skin'] ) );
	// Re-check if custom_skin is not empty.
	if ( ! empty( $custom_skin ) ) {
		print '<link rel="stylesheet" href="'. esc_attr( includes_url( 'js/mediaelement/mediaelementplayer-legacy.min.css' ) ) .'">
		<link rel="stylesheet" href="' . esc_attr( includes_url( 'js/mediaelement/wp-mediaelement.min.css' ) ) .'">
		<link rel="stylesheet" href="' . esc_attr( plugin_dir_url( __FILE__ ) . '../vendors/mejs-skins/mejs-skins.min.css' ) . '">
		<style>
			body {margin: 0;padding: 20px;font-family: Arial, sans-serif;background: #ffffff;}
			.player-container {max-width: 600px;margin: 0 auto;}
			.mejs__container {margin: 0 auto;}' .
			$custom_skin .
		'</style>
		<script type="text/javascript" src="' . esc_attr( includes_url( 'js/jquery/jquery.js' ) ) . '"></script>
		<script src="' . esc_attr( includes_url( 'js/jquery/jquery-migrate.min.js' ) ) . '"></script>
		<script src="' . esc_attr( includes_url( 'js/mediaelement/mediaelement-and-player.min.js' ) ) . '"></script>
		<script src="' . esc_attr( includes_url( 'js/mediaelement/mediaelement-migrate.js' ) ) . '"></script>
		<script src="' . esc_attr( includes_url( 'js/mediaelement/wp-mediaelement.js' ) ) . '"></script>
		<script src="' . esc_attr( plugin_dir_url( __FILE__ ) . '../js/public.js' ) . '"></script>
		<div class="wcmp-player-container">
			<audio controlslist="nodownload" volume="1" preload="none" data-lazyloading="none" class="wcmp-player mejs-classic  wcmp-custom-skin" src="'. esc_attr( plugin_dir_url( __FILE__ ) . '../vendors/demo/demo.mp3' ) . '">
			<source src="' . esc_attr( plugin_dir_url( __FILE__ ) . '../vendors/demo/demo.mp3' ) . '" type="audio/mp3">
			</audio>
		</div>';
		exit;
	}
}