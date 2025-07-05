<?php
if ( ! class_exists( 'BFP_CLOUD_DRIVE_ADDON' ) ) {
	class BFP_CLOUD_DRIVE_ADDON {

		private $_bfp;
		private $_cloud_drive_obj;
		private $_credential_file_name = 'credentials.json';
		private $_files_directory_credentials_path;
		private $_settings;

		public function __construct( $bfp ) {
			 $this->_createCredentialDir();

			$this->_bfp = $bfp;
			add_action( 'bfp_save_setting', array( $this, 'save_settings' ) );
			add_action( 'bfp_general_settings', array( $this, 'show_settings' ) );

			add_action( 'bfp_delete_file', array( $this, 'delete_file' ), 10, 2 );
			add_action( 'bfp_delete_post', array( $this, 'delete_post' ), 10 );

			add_action( 'bfp_play_file', array( $this, 'play_file' ), 10, 2 );
			add_action( 'bfp_truncated_file', array( $this, 'upload_file' ), 10, 3 );
			if ( isset( $_GET['bfp-drive-credential'] ) ) {
				$this->_createCloudDriveOBJ();
				exit;
			}
		} // End __construct

		public function play_file( $product_id, $url ) {
			if ( $this->_isConnected() ) {
				$files = get_post_meta( $product_id, '_bfp_drive_files', true );
				$key   = md5( $url );
				if (
					! empty( $files ) &&
					isset( $files[ $key ] )
				) {
					header( 'Access-Control-Allow-Origin: *' );
					header( 'location: ' . $files[ $key ]['url'] );
					exit;
				}
			}
		} // End play_file

		public function upload_file( $product_id, $url, $file_path ) {
			if ( $this->_isConnected() ) {
				$this->_createCloudDriveOBJ();
				if ( file_exists( $file_path ) ) {
					$file_content = file_get_contents( $file_path );
					if ( false !== $file_content ) {
						$file_name      = md5( $url );
						$file_extension = pathinfo( $url, PATHINFO_EXTENSION );
						$result         = $this->_cloud_drive_obj->uploadFile(
							$file_name . '.' . $file_extension,
							$file_content,
							array(
								'extension'   => $file_extension,
								'description' => $url,
							)
						);

						if ( false !== $result ) {
							$files = get_post_meta( $product_id, '_bfp_drive_files', true );
							if ( empty( $files ) ) {
								$files = array();
							}
							$key           = md5( $url );
							$files[ $key ] = $result;
							delete_post_meta( $product_id, '_bfp_drive_files' );
							add_post_meta( $product_id, '_bfp_drive_files', $files, true );
							@unlink( $file_path );
						}

						print $file_content; // phpcs:ignore WordPress.Security.EscapeOutput
						exit;
					}
				}

				$files = get_post_meta( $product_id, '_bfp_drive_files', true );
				$key   = md5( $url );
				if (
					! empty( $files ) &&
					isset( $files[ $key ] )
				) {
					header( 'location: ' . $files[ $key ] );
					exit;
				}
			}
		} // End upload_file

		public function show_settings() {
			$settings  = $this->_getSettings();
			$drive     = ( isset( $settings['_bfp_drive'] ) ) ? $settings['_bfp_drive'] : false;
			$drive_key = ( isset( $settings['_bfp_drive_key'] ) ) ? $settings['_bfp_drive_key'] : '';
			$drive_api_key = get_option( '_bfp_drive_api_key', '' );
			?>
			<tr>
				<td colspan="2"><hr /></td>
			</tr>
			<tr>
				<td width="30%"><label for="_bfp_drive"><?php esc_html_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?></label></td>
				<td><input aria-label="<?php esc_attr_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_drive" name="_bfp_drive" <?php print( ( $drive ) ? 'CHECKED' : '' ); ?> /></td>
			</tr>
			<tr>
				<td width="30%">
					<?php esc_html_e( 'Import OAuth Client JSON File', 'bandfront-player' ); ?><br>
					(<?php esc_html_e( 'Required to upload demo files to Google Drive', 'bandfront-player' ); ?>)
				</td>
				<td>
					<input aria-label="<?php esc_attr_e( 'OAuth Client JSON file', 'bandfront-player' ); ?>" type="file" name="_bfp_drive_key" />
					<?php
					if ( ! empty( $drive_key ) ) {
						echo '<span style="font-weight:bold; color: green;">' . esc_html__( 'There is an OAuth Client Available', 'bandfront-player' ) . '</span>';
					}
					?>
					<br /><br />
					<div style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
						<h3>To create an OAuth 2.0 client ID in the console:</h3>
						<p>
							<ol>
								<li>Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Platform Console</a>.</li>
								<li>From the projects list, select a project or create a new one.</li>
								<li>If the APIs & services page isn't already open, open the console left side menu and select <b>APIs & services</b>.</li>
								<li>On the left, click <b>Credentials</b>.</li>
								<li>Click <b>+ CREATE CREDENTIALS</b>, then select <b>OAuth client ID</b>.</li>
								<li>Select the application type <b>Web application</b>.</li>
								<li>Enter <b>WooCommerce Music Player</b> in the <b>Name</b> attribute.</li>
								<li>Enter the URL below as the <strong>Authorized redirect URIs</strong>:
								<br><br><b><i><?php print esc_html( $this->_getCallbackURL() ); ?></i></b><br><br></li>
								<li>Press the <strong>Create</strong> button.</li>
								<li>In the <b>OAuth client created</b> dialog, press the <b>DOWNLOAD JSON</b> button and store it on your computer, and press the <b>Ok</b> button.</li>
								<li>Finally, select the downloaded file through the <b>Import OAuth Client JSON File</b>.</li>
							</ol>
						</p>
					</div>
				</td>
			</tr>
			<tr>
				<td width="30%">
					<label for="_bfp_drive_api_key"><?php esc_html_e( 'API Key', 'bandfront-player' ); ?></label><br>
					(<?php esc_html_e( 'Required to read audio files from players', 'bandfront-player' ); ?>)
				</td>
				<td>
					<input aria-label="<?php esc_attr_e( 'API Key', 'bandfront-player' ); ?>" type="text" id="_bfp_drive_api_key" name="_bfp_drive_api_key" value="<?php print esc_attr( $drive_api_key ); ?>" style="width:100%;" />
					<br /><br />
					<div style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
						<h3>Get API Key</h3>
						<p>
							<ol>
								<li>Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Platform Console</a>.</li>
								<li>From the projects list, select a project or create a new one.</li>
								<li>If the APIs & services page isn't already open, open the console left side menu and select <b>APIs & services</b>.</li>
								<li>On the left, click <b>Credentials</b>.</li>
								<li>Click <b>+ CREATE CREDENTIALS</b>, then select <b>API Key</b>.</li>
								<li>Copy the API Key.</li>
								<li>Finally, paste it in the <b>API Key</b> attribute.</li>
							</ol>
						</p>
					</div>
				</td>
			</tr>
			<?php
		} // End show_settings

		public function save_settings() {
			$this->_settings = $this->_getSettings();
			$drive           = ( isset( $_REQUEST['_bfp_drive'] ) ) ? 1 : 0;
			$drive_key       = ( ! empty( $this->_settings['_bfp_drive_key'] ) ) ? $this->_settings['_bfp_drive_key'] : '';
			$drive_api_key   = ( ! empty( $_REQUEST['_bfp_drive_api_key'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['_bfp_drive_api_key'] ) ) : '';

			if ( ! empty( $_FILES['_bfp_drive_key'] ) && ! empty( $_FILES['_bfp_drive_key']['size'] ) && ! empty( $_FILES['_bfp_drive_key']['tmp_name'] ) ) {
				$key_file_content = @file_get_contents( sanitize_text_field( wp_unslash( $_FILES['_bfp_drive_key']['tmp_name'] ) ) );
				if ( false !== $key_file_content ) {
					$key_file_content = json_decode( $key_file_content, true );
					if ( ! is_null( $key_file_content ) ) {
						$drive_key = $key_file_content;
						$this->_deleteCredential();
					}
					@unlink( sanitize_text_field( wp_unslash( $_FILES['_bfp_drive_key']['tmp_name'] ) ) );
				}
			}

			$this->_settings = array(
				'_bfp_drive'     => $drive,
				'_bfp_drive_key' => $drive_key,
			);

			update_option(
				'_bfp_cloud_drive_addon',
				$this->_settings
			);
			update_option(
				'_bfp_drive_api_key',
				$drive_api_key
			);
			$this->_createCloudDriveOBJ();

		} // End save_settings

		public function delete_post( $product_id ) {
			delete_post_meta( $product_id, '_bfp_drive_files' );
		} // End delete_post

		public function delete_file( $product_id, $file ) {
			 $key  = md5( $file );
			$files = get_post_meta( $product_id, '_bfp_drive_files', true );
			if (
				! empty( $files ) &&
				isset( $files[ $key ] )
			) {
				if ( $this->_isConnected() ) {
					$this->_createCloudDriveOBJ();
					$this->_cloud_drive_obj->deleteFile( $files[ $key ]['id'] );
				}
				unset( $files[ $key ] );
				delete_post_meta( $product_id, '_bfp_drive_files' );
				add_post_meta( $product_id, '_bfp_drive_files', $files, true );
			}
		} // End delete_file

		// ******************** PRIVATE METHODS ************************
		private function _createCloudDriveOBJ() {
			if ( $this->_isActive() ) {
				$this->_cloud_drive_obj = new BFP_CLOUD_DRIVE(
					$this->_settings['_bfp_drive_key'],
					$this->_files_directory_credentials_path . $this->_credential_file_name,
					$this->_getCallbackURL(),
					$this->_getRedirectURL()
				);
				 $this->_cloud_drive_obj->connect();
			}
		} // _createCloudDriveOBJ

		private function _isActive() {
			$settings = $this->_getSettings();
			if ( ! empty( $settings['_bfp_drive'] ) && ! empty( $settings['_bfp_drive_key'] ) ) {
				require_once __DIR__ . '/google-drive/BFP_CLOUD_DRIVE.clss.php';
				return true;
			}
			return false;
		} // End _isActive

		private function _isConnected() {
			return $this->_isActive() &&
					! empty( $this->_files_directory_credentials_path ) &&
					file_exists( $this->_files_directory_credentials_path . $this->_credential_file_name );

		} // End _isConnected

		private function _getSettings() {
			if ( ! isset( $this->_settings ) ) {
				$this->_settings = get_option( '_bfp_cloud_drive_addon', array() );
			}
			return $this->_settings;
		} // End _getSettings

		private function _getCallbackURL() {
			$url  = get_home_url( get_current_blog_id() );
			$url .= ( ( strpos( $url, '?' ) === false ) ? '?' : '&' ) . 'bfp-drive-credential=1';
			return $url;
		} // End _getCallbackURL

		private function _getRedirectURL() {
			return $this->_bfp->settings_page_url();
		} // End _getRedirectURL

		private function _createCredentialDir() {
			// Generate upload dir
			$_files_directory                        = wp_upload_dir();
			$this->_files_directory_credentials_path = rtrim( $_files_directory['basedir'], '/' ) . '/bfp_credentials/';
			if ( ! file_exists( $this->_files_directory_credentials_path ) ) {
				@mkdir( $this->_files_directory_credentials_path, 0744 );

				// Create the .htaccess file
				if ( ! file_exists( $this->_files_directory_credentials_path . '.htaccess' ) ) {
					@file_put_contents( $this->_files_directory_credentials_path . '.htaccess', 'Deny from All' );
				}
			}
		} // End _createCredentialDir

		private function _deleteCredential() {
			if (
				! empty( $this->_files_directory_credentials_path ) &&
				file_exists( $this->_files_directory_credentials_path . $this->_credential_file_name )
			) {
				@unlink( $this->_files_directory_credentials_path . $this->_credential_file_name );
			}
		} // End _deleteCredential

	} // End BFP_CLOUD_DRIVE_ADDON
}

if ( version_compare( PHP_VERSION, '5.4.0' ) != -1 ) {
	new BFP_CLOUD_DRIVE_ADDON( $bfp );
}
