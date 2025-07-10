<?php
if ( ! class_exists( 'BFP_CLOUD_DRIVE' ) ) {
	class BFP_CLOUD_DRIVE {


		private $_key;
		private $_credential_file;
		private $_errors;
		private $_callback_uri;
		private $_redirect_uri;
		private $_drive_service;
		private $_mimes = '{"png":["image\/png","image\/x-png"],"bmp":["image\/bmp","image\/x-bmp","image\/x-bitmap","image\/x-xbitmap","image\/x-win-bitmap","image\/x-windows-bmp","image\/ms-bmp","image\/x-ms-bmp","application\/bmp","application\/x-bmp","application\/x-win-bitmap"],"gif":["image\/gif"],"jpeg":["image\/jpeg","image\/pjpeg"],"xspf":["application\/xspf+xml"],"vlc":["application\/videolan"],"wmv":["video\/x-ms-wmv","video\/x-ms-asf"],"au":["audio\/x-au"],"ac3":["audio\/ac3"],"flac":["audio\/x-flac"],"ogg":["audio\/ogg","video\/ogg","application\/ogg"],"kmz":["application\/vnd.google-earth.kmz"],"kml":["application\/vnd.google-earth.kml+xml"],"rtx":["text\/richtext"],"rtf":["text\/rtf"],"jar":["application\/java-archive","application\/x-java-application","application\/x-jar"],"zip":["application\/x-zip","application\/zip","application\/x-zip-compressed","application\/s-compressed","multipart\/x-zip"],"7zip":["application\/x-compressed"],"xml":["application\/xml","text\/xml"],"svg":["image\/svg+xml"],"3g2":["video\/3gpp2"],"3gp":["video\/3gp","video\/3gpp"],"mp4":["video\/mp4"],"m4a":["audio\/x-m4a"],"f4v":["video\/x-f4v"],"flv":["video\/x-flv"],"webm":["video\/webm"],"aac":["audio\/x-acc"],"m4u":["application\/vnd.mpegurl"],"pdf":["application\/pdf","application\/octet-stream"],"pptx":["application\/vnd.openxmlformats-officedocument.presentationml.presentation"],"ppt":["application\/powerpoint","application\/vnd.ms-powerpoint","application\/vnd.ms-office","application\/msword"],"docx":["application\/vnd.openxmlformats-officedocument.wordprocessingml.document"],"xlsx":["application\/vnd.openxmlformats-officedocument.spreadsheetml.sheet","application\/vnd.ms-excel"],"xl":["application\/excel"],"xls":["application\/msexcel","application\/x-msexcel","application\/x-ms-excel","application\/x-excel","application\/x-dos_ms_excel","application\/xls","application\/x-xls"],"xsl":["text\/xsl"],"mpeg":["video\/mpeg"],"mov":["video\/quicktime"],"avi":["video\/x-msvideo","video\/msvideo","video\/avi","application\/x-troff-msvideo"],"movie":["video\/x-sgi-movie"],"log":["text\/x-log"],"txt":["text\/plain"],"css":["text\/css"],"html":["text\/html"],"wav":["audio\/x-wav","audio\/wave","audio\/wav"],"xhtml":["application\/xhtml+xml"],"tar":["application\/x-tar"],"tgz":["application\/x-gzip-compressed"],"psd":["application\/x-photoshop","image\/vnd.adobe.photoshop"],"exe":["application\/x-msdownload"],"js":["application\/x-javascript"],"mp3":["audio\/mpeg","audio\/mpg","audio\/mpeg3","audio\/mp3"],"rar":["application\/x-rar","application\/rar","application\/x-rar-compressed"],"gzip":["application\/x-gzip"],"hqx":["application\/mac-binhex40","application\/mac-binhex","application\/x-binhex40","application\/x-mac-binhex40"],"cpt":["application\/mac-compactpro"],"bin":["application\/macbinary","application\/mac-binary","application\/x-binary","application\/x-macbinary"],"oda":["application\/oda"],"ai":["application\/postscript"],"smil":["application\/smil"],"mif":["application\/vnd.mif"],"wbxml":["application\/wbxml"],"wmlc":["application\/wmlc"],"dcr":["application\/x-director"],"dvi":["application\/x-dvi"],"gtar":["application\/x-gtar"],"php":["application\/x-httpd-php","application\/php","application\/x-php","text\/php","text\/x-php","application\/x-httpd-php-source"],"swf":["application\/x-shockwave-flash"],"sit":["application\/x-stuffit"],"z":["application\/x-compress"],"mid":["audio\/midi"],"aif":["audio\/x-aiff","audio\/aiff"],"ram":["audio\/x-pn-realaudio"],"rpm":["audio\/x-pn-realaudio-plugin"],"ra":["audio\/x-realaudio"],"rv":["video\/vnd.rn-realvideo"],"jp2":["image\/jp2","video\/mj2","image\/jpx","image\/jpm"],"tiff":["image\/tiff"],"eml":["message\/rfc822"],"pem":["application\/x-x509-user-cert","application\/x-pem-file"],"p10":["application\/x-pkcs10","application\/pkcs10"],"p12":["application\/x-pkcs12"],"p7a":["application\/x-pkcs7-signature"],"p7c":["application\/pkcs7-mime","application\/x-pkcs7-mime"],"p7r":["application\/x-pkcs7-certreqresp"],"p7s":["application\/pkcs7-signature"],"crt":["application\/x-x509-ca-cert","application\/pkix-cert"],"crl":["application\/pkix-crl","application\/pkcs-crl"],"pgp":["application\/pgp"],"gpg":["application\/gpg-keys"],"rsa":["application\/x-pkcs7"],"ics":["text\/calendar"],"zsh":["text\/x-scriptzsh"],"cdr":["application\/cdr","application\/coreldraw","application\/x-cdr","application\/x-coreldraw","image\/cdr","image\/x-cdr","zz-application\/zz-winassoc-cdr"],"wma":["audio\/x-ms-wma"],"vcf":["text\/x-vcard"],"srt":["text\/srt"],"vtt":["text\/vtt"],"ico":["image\/x-icon","image\/x-ico","image\/vnd.microsoft.icon"],"csv":["text\/x-comma-separated-values","text\/comma-separated-values","application\/vnd.msexcel"],"json":["application\/json","text\/json"]}';

		public function __construct( $key, $credentials_path, $callback_uri, $redirect_uri ) {
			if ( ! class_exists( 'Google_Client' ) ) {
				require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';
			}
			$this->_errors          = array();
			$this->_key             = $key;
			$credentials_path       = str_replace( '\\', '/', $credentials_path );
			$this->_credential_file = $credentials_path;
			$this->_callback_uri    = $callback_uri;
			$this->_redirect_uri    = $redirect_uri;
		} // End __construct

		public function init() {
			error_reporting( E_ERROR | E_PARSE );
			if ( $this->connect() ) {
				try {
					if ( ( $folder_id = $this->exists( 'bfp', 'application/vnd.google-apps.folder' ) ) == 0 ) {
						$folder_id = $this->createFolder( 'bfp' );
					}
					return ( $folder_id ) ? $folder_id : false;
				} catch ( Exception $e ) {
					$this->_errors[] = $e->getMessage();
				}
			}
			return false;
		} // End init

		public function connect() {
			try {
				$client = new Google_Client();
				$client->setAuthConfig( $this->_key );
				if ( ! class_exists( 'Google_Service_Drive' ) ) {
					require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';
				}
				$client->addScope( Google_Service_Drive::DRIVE );

				if ( file_exists( $this->_credential_file ) && ! isset( $_GET['bfp-drive-credential'] ) ) {
					$access_token = file_get_contents( $this->_credential_file );
					$client->setAccessToken( $access_token );

					// Refresh the token if it's expired.
					if ( $client->isAccessTokenExpired() ) {
						$refreshTokenSaved = $client->getRefreshToken();
						$client->fetchAccessTokenWithRefreshToken( $refreshTokenSaved );
						file_put_contents( $this->_credential_file, json_encode( $client->getAccessToken() ) );
					}

					$this->_drive_service = new Google_Service_Drive( $client );
				} else {
					$client->setRedirectUri( $this->_callback_uri );
					$client->addScope( Google_Service_Drive::DRIVE );
					$client->setAccessType( 'offline' );
					$client->setApprovalPrompt( 'force' );
					if ( ! isset( $_GET['code'] ) ) {
						$auth_url           = $client->createAuthUrl();
						$auth_url_sanitized = filter_var( $auth_url, FILTER_SANITIZE_URL );
						if ( ! headers_sent() ) {
							header( 'Location: ' . $auth_url_sanitized );
						} else {
							print '<script>document.location.href="' . esc_url( $auth_url_sanitized ) . '"</script>';
							exit;
						}
					}

					$client->authenticate( $_GET['code'] );
					$access_token = $client->getAccessToken();
					file_put_contents( $this->_credential_file, json_encode( $access_token ) );
					$uri_sanitized = filter_var( $this->_redirect_uri, FILTER_SANITIZE_URL );
					if ( headers_sent() ) {
						header( 'Location: ' . $uri_sanitized );
					} else {
						print '<script>document.location.href="' . $uri_sanitized . '"</script>';
						exit;
					}
				}
			} catch ( Exception $e ) {
				$this->_errors[] = $e->getMessage();
				return false;
			}
			return true;
		} // End connect

		// Check if the file or folder exists and return its ID or 0
		public function exists( $name, $mime = 'application/vnd.google-apps.folder' ) {
			try {
				$item = $this->_drive_service->files->listFiles( array( 'q' => 'name="' . urlencode( $name ) . '" and mimeType="' . urlencode( $mime ) . '"' ) )->getFiles();

				return ( count( $item ) ) ? $item[0]->id : false;
			} catch ( Exception $e ) {
				$this->_errors[] = $e->getMessage();
			}
			return false;
		} // End exits

		// Creates a directory into, $args['parent'] would include the parent's id
		public function createFolder( $name, $args = array() ) {
			try {
				// Create the directory
				$metadata = new Google_Service_Drive_DriveFile(
					array(
						'name'     => $name,
						'mimeType' => 'application/vnd.google-apps.folder',
					)
				);

				// Set parent
				if ( ! empty( $args ) && ! empty( $args['parent'] ) ) {
					$metadata->setParents( array( $args['parent'] ) );
				}

				$folder = $this->_drive_service->files->create( $metadata, array( 'fields' => 'id' ) );
				return ( $folder ) ? $folder->id : false;
			} catch ( Exception $e ) {
				$this->_errors[] = $e->getMessage();
			}
			return false;
		} // End createFolder

		public function uploadFile( $name, $content, $args = array() ) {
			if ( ( $folder_id = $this->init() ) != false ) {
				try {
					if ( ! empty( $args['mime'] ) ) {
						$mime_type = $args['mime'];
					} elseif ( ! empty( $args['extension'] ) ) {
						$extension = strtolower( $args['extension'] );
						$mime_type = $this->_extensionToMime( $extension );
					}

					$metadata = new Google_Service_Drive_DriveFile(
						array(
							'name'        => $name,
							'description' => ( ! empty( $args['description'] ) ) ? $args['description'] : '',
							'mimeType'    => ( ! empty( $mime_type ) && $mime_type !== false ) ? $mime_type : 'audio/mpeg',
						)
					);

					// Set the parent folder.
					$metadata->setParents(
						array(
							( ! empty( $args['parent'] ) ) ? $args['parent'] : $folder_id,
						)
					);

					$file = $this->_drive_service->files->create(
						$metadata,
						array(
							'data'       => $content,
							'uploadType' => 'media',
							'fields'     => 'id,mimeType,webContentLink',
						)
					);

					if ( $file ) {
						$file_id     = $file->getId();
						$permissions = new Google_Service_Drive_Permission(
							array(
								'type' => 'anyone',
								'role' => 'reader',
							)
						);

						$this->_drive_service->permissions->create(
							$file_id,
							$permissions,
							array( 'fields' => 'id' )
						);

						$extension = $this->_mimeToExtension( $file->getMimeType() );

						return array(
							'id'  => $file_id,
							'url' => 'https://www.googleapis.com/drive/v3/files/' . urlencode( $file_id ) . '?alt=media&key=' . urlencode( get_option( '_bfp_drive_api_key', '' ) ) . '&ext=.' . ( ( $extension ) ? $extension : 'mp3' ),
						);
					}
				} catch ( Exception $e ) {
					$this->_errors[] = $e->getMessage();
				}
			}
			return false;
		} // End uploadFile

		public function deleteFile( $file_id ) {
			try {
				if ( $this->init() ) {
					$this->_drive_service->files->delete( $file_id );
					return true;
				}
			} catch ( Exception $e ) {
				$this->_errors[] = $e->getMessage();
			}
			return false;
		} // End deleteFile

		public function getFile( $file_id ) {
			try {
				if ( ( $folder_id = $this->init() ) != false ) {
					$file = $this->_drive_service->files->get( $file_id, array( 'fields' => 'id,mimeType,webContentLink' ) );

					$extension = $this->_mimeToExtension( $file->getMimeType() );

					if ( $file ) {
						return array(
							'id'  => $file_id,
							'url' => 'https://www.googleapis.com/drive/v3/files/' . urlencode( $file_id ) . '?alt=media&key=' . urlencode( get_option( '_bfp_drive_api_key', '' ) ) . '&ext=.' . ( ( $extension ) ? $extension : 'mp3' ),
						);
					}
				}
			} catch ( Exception $e ) {
				$this->_errors[] = $e->getMessage();
			}
			return false;
		} // End getFile

		public function getErrors() {
			return $this->_errors;
		} // End getErrors

		private function _extensionToMime( $extension ) {
			$all_mimes = json_decode( $this->_mimes, true );
			if ( $all_mimes && $all_mimes[ $extension ] ) {
				return $all_mimes[ $extension ][0];
			}
			return false;
		} // End _extensionToMime

		private function _mimeToExtension( $mime ) {
			$all_mimes = json_decode( $this->_mimes, true );
			if ( $all_mimes ) {
				foreach ( $all_mimes as $key => $value ) {
					if ( array_search( $mime, $value ) !== false ) {
						return $key;
					}
				}
			}

			return false;
		} // End _mimeToExtension

	} // End BFP_CLOUD_DRIVE
}
