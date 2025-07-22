<?php
/**
 * Cloud Storage Tools Template
 * 
 * This template provides cloud storage configuration
 * for demo file management
 *
 * Variables available in this template:
 * @var Bandfront\Core\Config $config Config instance
 * @var Bandfront\Storage\FileManager $fileManager FileManager instance
 * @var Bandfront\UI\Renderer $renderer Renderer instance
 * 
 * @package BandfrontPlayer
 * @subpackage Views
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get unified cloud storage settings
$cloud_storage = $config->getState('_bfp_cloud_storage', []);

// Extract settings
$active_provider = $cloud_storage['active_provider'] ?? 'none';
$cloud_dropbox = $cloud_storage['dropbox'] ?? [];
$cloud_s3 = $cloud_storage['s3'] ?? [];
$cloud_azure = $cloud_storage['azure'] ?? [];
$cloud_google = $cloud_storage['google-drive'] ?? [];

// Map active provider to tab name for UI
$tab_mapping = [
    'none' => 'google-drive',
    'google-drive' => 'google-drive',
    'dropbox' => 'dropbox',
    's3' => 'aws-s3',
    'azure' => 'azure'
];
$cloud_active_tab = $tab_mapping[$active_provider] ?? 'google-drive';

// Legacy options for backward compatibility
$bfp_cloud_settings = get_option('_bfp_cloud_drive_addon', array());
$bfp_drive_key = isset($bfp_cloud_settings['_bfp_drive_key']) ? $bfp_cloud_settings['_bfp_drive_key'] : '';
$bfp_drive_api_key = get_option('_bfp_drive_api_key', '');
?>

<!-- Cloud Storage Tab -->
<div id="cloud-storage-panel" class="bfp-tab-panel" style="display:none;">
    <h3>☁️ <?php esc_html_e('Cloud Storage', 'bandfront-player'); ?></h3>
    <p><?php esc_html_e( 'Automatically upload demo files to cloud storage to save server storage and bandwidth. Files are streamed directly from the cloud.', 'bandfront-player' ); ?></p>
    
    <input type="hidden" name="_bfp_cloud_storage[active_provider]" id="_bfp_cloud_active_provider" value="<?php echo esc_attr($active_provider); ?>" />
    
    <div class="bfp-cloud_tabs">
        <div class="bfp-cloud-tab-buttons">
            <button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'google-drive' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="google-drive">
                🗂️ <?php esc_html_e( 'Google Drive', 'bandfront-player' ); ?>
            </button>
            <button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'dropbox' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="dropbox">
                📦 <?php esc_html_e( 'Dropbox', 'bandfront-player' ); ?>
            </button>
            <button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'aws-s3' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="aws-s3">
                🛡️ <?php esc_html_e( 'AWS S3', 'bandfront-player' ); ?>
            </button>
            <button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'azure' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="azure">
                ☁️ <?php esc_html_e( 'Azure Blob', 'bandfront-player' ); ?>
            </button>
        </div>
        
        <div class="bfp-cloud-tab-content">
            <!-- Google Drive Tab -->
            <div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'google-drive' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="google-drive">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_google_drive_enabled"><?php esc_html_e( 'Enable Google Drive Storage', 'bandfront-player' ); ?></label></th>
                        <td>
                            <input aria-label="<?php esc_attr_e( 'Enable Google Drive Storage', 'bandfront-player' ); ?>" 
                                   type="checkbox" 
                                   id="_bfp_cloud_google_drive_enabled" 
                                   name="_bfp_cloud_google_drive_enabled" 
                                   value="1"
                                   <?php checked( $cloud_google['enabled'] ?? false ); ?> 
                                   class="bfp-cloud-provider-checkbox" />
                            <span class="description"><?php esc_html_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e( 'Import OAuth Client JSON File', 'bandfront-player' ); ?><br>
                            (<?php esc_html_e( 'Required to upload demo files to Google Drive', 'bandfront-player' ); ?>)
                        </th>
                        <td>
                            <input aria-label="<?php esc_attr_e( 'OAuth Client JSON file', 'bandfront-player' ); ?>" type="file" name="_bfp_drive_key" />
                            <?php
                            if ( ! empty( $bfp_drive_key ) ) {
                                echo '<span class="bfp-oauth-success">' . esc_html__( 'OAuth Client Available ✅', 'bandfront-player' ) . '</span>';
                            }
                            ?>
                            <br /><br />
                            <div class="bfp-cloud-instructions">
                                <h3><?php esc_html_e( 'To create an OAuth 2.0 client ID:', 'bandfront-player' ); ?></h3>
                                <p>
                                    <ol>
                                        <li><?php esc_html_e( 'Go to the', 'bandfront-player' ); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php esc_html_e( 'Google Cloud Platform Console', 'bandfront-player' ); ?></a>.</li>
                                        <li><?php esc_html_e( 'From the projects list, select a project or create a new one.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'If the APIs & services page isn\'t already open, open the console left side menu and select APIs & services.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'On the left, click Credentials.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Click + CREATE CREDENTIALS, then select OAuth client ID.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Select the application type Web application.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Enter BandFront Player in the Name field.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Enter the URL below as the Authorized redirect URIs:', 'bandfront-player' ); ?>
                                        <br><br><b><i><?php 
                                        $callback_url = get_home_url( get_current_blog_id() );
                                        $callback_url .= ( ( strpos( $callback_url, '?' ) === false ) ? '?' : '&' ) . 'bfp-drive-credential=1';
                                        print esc_html( $callback_url ); 
                                        ?></i></b><br><br></li>
                                        <li><?php esc_html_e( 'Press the Create button.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'In the OAuth client created dialog, press the DOWNLOAD JSON button and store it on your computer, and press the Ok button.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Finally, select the downloaded file through the Import OAuth Client JSON File field above.', 'bandfront-player' ); ?></li>
                                    </ol>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="_bfp_drive_api_key"><?php esc_html_e( 'API Key', 'bandfront-player' ); ?></label><br>
                        (<?php esc_html_e( 'Required to read audio files from players', 'bandfront-player' ); ?>)
                        </th>
                        <td>
                            <input aria-label="<?php esc_attr_e( 'API Key', 'bandfront-player' ); ?>" type="text" id="_bfp_drive_api_key" name="_bfp_drive_api_key" value="<?php print esc_attr( $bfp_drive_api_key ); ?>" class="bfp-input-full" />
                            <br /><br />
                            <div class="bfp-cloud-instructions">
                                <h3><?php esc_html_e( 'Get API Key:', 'bandfront-player' ); ?></h3>
                                <p>
                                    <ol>
                                        <li><?php esc_html_e( 'Go to the', 'bandfront-player' ); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php esc_html_e( 'Google Cloud Platform Console', 'bandfront-player' ); ?></a>.</li>
                                        <li><?php esc_html_e( 'From the projects list, select a project or create a new one.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'If the APIs & services page isn\'t already open, open the console left side menu and select APIs & services.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'On the left, click Credentials.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Click + CREATE CREDENTIALS, then select API Key.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Copy the API Key.', 'bandfront-player' ); ?></li>
                                        <li><?php esc_html_e( 'Finally, paste it in the API Key field above.', 'bandfront-player' ); ?></li>
                                    </ol>
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Dropbox Tab -->
            <div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'dropbox' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="dropbox">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_dropbox_enabled"><?php esc_html_e( 'Enable Dropbox Storage', 'bandfront-player' ); ?></label></th>
                        <td>
                            <input aria-label="<?php esc_attr_e( 'Enable Dropbox Storage', 'bandfront-player' ); ?>" 
                                   type="checkbox" 
                                   id="_bfp_cloud_dropbox_enabled" 
                                   name="_bfp_cloud_dropbox_enabled" 
                                   value="1"
                                   <?php checked( $cloud_dropbox['enabled'] ?? false ); ?> 
                                   class="bfp-cloud-provider-checkbox" />
                            <span class="description"><?php esc_html_e( 'Store demo files on Dropbox', 'bandfront-player' ); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_dropbox_token"><?php esc_html_e('Access Token', 'bandfront-player'); ?></label></th>
                        <td>
                            <input type="password" 
                                   id="_bfp_cloud_dropbox_token" 
                                   name="_bfp_cloud_dropbox_token" 
                                   value="<?php echo esc_attr($cloud_dropbox['access_token'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e('Dropbox access token for API authentication', 'bandfront-player'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_dropbox_folder"><?php esc_html_e('Folder Path', 'bandfront-player'); ?></label></th>
                        <td>
                            <input type="text" 
                                   id="_bfp_cloud_dropbox_folder" 
                                   name="_bfp_cloud_dropbox_folder" 
                                   value="<?php echo esc_attr($cloud_dropbox['folder_path'] ?? '/bandfront-demos'); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e('Dropbox folder path for demo files', 'bandfront-player'); ?></p>
                        </td>
                    </tr>
                </table>
                <div class="bfp-cloud-placeholder">
                    <h3>📦 <?php esc_html_e( 'Dropbox Integration', 'bandfront-player' ); ?></h3>
                    <p><?php esc_html_e( 'Coming soon! Enhanced Dropbox integration with automatic syncing and bandwidth optimization.', 'bandfront-player' ); ?></p>
                    <div class="bfp-cloud-features">
                        <h4><?php esc_html_e( 'Planned Features:', 'bandfront-player' ); ?></h4>
                        <ul>
                            <li>✨ <?php esc_html_e( 'Automatic file upload to Dropbox', 'bandfront-player' ); ?></li>
                            <li>🔄 <?php esc_html_e( 'Real-time synchronization', 'bandfront-player' ); ?></li>
                            <li>📊 <?php esc_html_e( 'Bandwidth usage analytics', 'bandfront-player' ); ?></li>
                            <li>🛡️ <?php esc_html_e( 'Advanced security controls', 'bandfront-player' ); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- AWS S3 Tab -->
            <div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'aws-s3' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="aws-s3">
                <div class="bfp-cloud-placeholder">
                    <h3>🛡️ <?php esc_html_e( 'Amazon S3 Storage', 'bandfront-player' ); ?></h3>
                    <p><?php esc_html_e( 'Enterprise-grade cloud storage with AWS S3. Perfect for high-traffic websites requiring maximum reliability and global CDN distribution.', 'bandfront-player' ); ?></p>
                    <div class="bfp-cloud-features">
                        <h4><?php esc_html_e( 'Planned Features:', 'bandfront-player' ); ?></h4>
                        <ul>
                            <li>🌍 <?php esc_html_e( 'Global CDN with CloudFront integration', 'bandfront-player' ); ?></li>
                            <li>⚡ <?php esc_html_e( 'Lightning-fast file delivery', 'bandfront-player' ); ?></li>
                            <li>💰 <?php esc_html_e( 'Cost-effective storage pricing', 'bandfront-player' ); ?></li>
                            <li>🔐 <?php esc_html_e( 'Enterprise security and encryption', 'bandfront-player' ); ?></li>
                        </ul>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_s3_enabled"><?php esc_html_e('Enable AWS S3 Storage', 'bandfront-player'); ?></label></th>
                            <td>
                                <input aria-label="<?php esc_attr_e( 'Enable AWS S3 Storage', 'bandfront-player' ); ?>" 
                                       type="checkbox" 
                                       id="_bfp_cloud_s3_enabled" 
                                       name="_bfp_cloud_s3_enabled" 
                                       value="1"
                                       <?php checked($cloud_s3['enabled'] ?? false); ?> 
                                       class="bfp-cloud-provider-checkbox" />
                                <span class="description"><?php esc_html_e('Use AWS S3 for demo file storage', 'bandfront-player'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_s3_access_key"><?php esc_html_e('Access Key ID', 'bandfront-player'); ?></label></th>
                            <td>
                                <input type="text" id="_bfp_cloud_s3_access_key" name="_bfp_cloud_s3_access_key" value="<?php echo esc_attr($cloud_s3['access_key'] ?? ''); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_s3_secret_key"><?php esc_html_e('Secret Access Key', 'bandfront-player'); ?></label></th>
                            <td>
                                <input type="password" id="_bfp_cloud_s3_secret_key" name="_bfp_cloud_s3_secret_key" value="<?php echo esc_attr($cloud_s3['secret_key'] ?? ''); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_s3_bucket"><?php esc_html_e('Bucket Name', 'bandfront-player'); ?></label></th>
                            <td>
                                <input type="text" id="_bfp_cloud_s3_bucket" name="_bfp_cloud_s3_bucket" value="<?php echo esc_attr($cloud_s3['bucket'] ?? ''); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_s3_region"><?php esc_html_e('Region', 'bandfront-player'); ?></label></th>
                            <td>
                                <select id="_bfp_cloud_s3_region" name="_bfp_cloud_s3_region">
                                    <option value="us-east-1" <?php selected($cloud_s3['region'] ?? 'us-east-1', 'us-east-1'); ?>>US East (N. Virginia)</option>
                                    <option value="us-west-2" <?php selected($cloud_s3['region'] ?? '', 'us-west-2'); ?>>US West (Oregon)</option>
                                    <option value="eu-west-1" <?php selected($cloud_s3['region'] ?? '', 'eu-west-1'); ?>>EU (Ireland)</option>
                                    <option value="eu-central-1" <?php selected($cloud_s3['region'] ?? '', 'eu-central-1'); ?>>EU (Frankfurt)</option>
                                    <option value="ap-southeast-1" <?php selected($cloud_s3['region'] ?? '', 'ap-southeast-1'); ?>>Asia Pacific (Singapore)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_s3_path"><?php esc_html_e('Path Prefix', 'bandfront-player'); ?></label></th>
                            <td>
                                <input type="text" id="_bfp_cloud_s3_path" name="_bfp_cloud_storage[s3][path_prefix]" value="<?php echo esc_attr($cloud_s3['path_prefix'] ?? 'bandfront-demos/'); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('S3 path prefix for demo files', 'bandfront-player'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Azure Tab -->
            <div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'azure' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="azure">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_azure_enabled"><?php esc_html_e('Enable Azure Storage', 'bandfront-player'); ?></label></th>
                        <td>
                            <input aria-label="<?php esc_attr_e( 'Enable Azure Storage', 'bandfront-player' ); ?>" 
                                   type="checkbox" 
                                   id="_bfp_cloud_azure_enabled" 
                                   name="_bfp_cloud_azure_enabled" 
                                   value="1"
                                   <?php checked($cloud_azure['enabled'] ?? false); ?> 
                                   class="bfp-cloud-provider-checkbox" />
                            <span class="description"><?php esc_html_e('Use Azure Storage for demo file storage', 'bandfront-player'); ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_azure_storage_account"><?php esc_html_e('Storage Account Name', 'bandfront-player'); ?></label></th>
                        <td>
                            <input type="text" id="_bfp_cloud_azure_storage_account" name="_bfp_cloud_azure_storage_account" value="<?php echo esc_attr($cloud_azure['storage_account'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_azure_access_key"><?php esc_html_e('Access Key', 'bandfront-player'); ?></label></th>
                        <td>
                            <input type="password" id="_bfp_cloud_azure_access_key" name="_bfp_cloud_azure_access_key" value="<?php echo esc_attr($cloud_azure['access_key'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="_bfp_cloud_azure_container"><?php esc_html_e('Container Name', 'bandfront-player'); ?></label></th>
                        <td>
                            <input type="text" id="_bfp_cloud_azure_container" name="_bfp_cloud_azure_container" value="<?php echo esc_attr($cloud_azure['container'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <div class="bfp-cloud-placeholder">
                    <h3>☁️ <?php esc_html_e( 'Microsoft Azure Blob Storage', 'bandfront-player' ); ?></h3>
                    <p><?php esc_html_e( 'Microsoft Azure Blob Storage integration for seamless file management and global distribution with enterprise-level security.', 'bandfront-player' ); ?></p>
                    <div class="bfp-cloud-features">
                        <h4><?php esc_html_e( 'Planned Features:', 'bandfront-player' ); ?></h4>
                        <ul>
                            <li>🏢 <?php esc_html_e( 'Enterprise Active Directory integration', 'bandfront-player' ); ?></li>
                            <li>🌐 <?php esc_html_e( 'Global edge locations', 'bandfront-player' ); ?></li>
                            <li>📈 <?php esc_html_e( 'Advanced analytics and monitoring', 'bandfront-player' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Compliance-ready security features', 'bandfront-player' ); ?></li>
                        </ul>
                    </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="_bfp_cloud_azure_path"><?php esc_html_e('Path Prefix', 'bandfront-player'); ?></label></th>
                            <td>
                                <input type="text" id="_bfp_cloud_azure_path" name="_bfp_cloud_storage[azure][path_prefix]" value="<?php echo esc_attr($cloud_azure['path_prefix'] ?? 'bandfront-demos/'); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Azure path prefix for demo files', 'bandfront-player'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>