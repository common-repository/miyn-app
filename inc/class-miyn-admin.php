<?php
/**
 * Fired during plugin core functions
 *
 * @link       https://github.com/Netmow-PTY-LTD
 * @since      1.2.0
 *
 * @package    miyn-app
 * @subpackage miyn-app/inc
 */


/**
 * Fired during plugin run.
 *
 * This class defines all code necessary to run during the plugin's features.
 *
 * @since      1.2.0
 * @package    miyn-app
 * @subpackage miyn-app/inc
 * @author     Netmow <dev@netmow.com>
 */

 class Miyn_app_admin{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      string    $plugin_version    The current version of this plugin.
	 */
	private $plugin_version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.2.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $plugin_version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_version ) {

		$this->plugin_name = $plugin_name;
		$this->plugin_version = $plugin_version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.2.0
	 */
	public function miynapp_enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Miynapp_loader_init as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Miynapp_loader_init will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __DIR__ ) . 'assets/css/main.css', array(), $this->plugin_version, 'all' );
		
		wp_enqueue_script( 'ckeditor', plugin_dir_url( __DIR__ ) . 'assets/js/ckeditor.js', array( 'jquery' ), $this->plugin_version, false );
		wp_enqueue_script('jscolor', plugin_dir_url( __DIR__ ) . 'assets/js/jscolor.js', array( 'jquery' ), $this->plugin_version, false);
		wp_enqueue_script('miyn-script', plugin_dir_url( __DIR__ ) . 'assets/js/miyn-script.js', array( 'jquery' ), $this->plugin_version, false);
		// Localize the script with new data

	    $siteurl = array(
	        'siteurl' => get_home_url()
	    );
	    wp_localize_script( 'miyn-script', 'object_miyn_app', $siteurl );
	    // Enqueued script with localized data.
	    wp_enqueue_script( 'miyn-script' );

		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}

	}

	public function miynapp_add_admin_pages() {
		$connections = $this->miynapp_get_connections_init();
		$err = $connections['message'];
		$status = $connections['status'];
        add_menu_page(__('MIYN App'), __('MIYN App'), 'manage_options', 'miyn-app', [$this, 'miynapp_connection_init'], plugin_dir_url( __DIR__ ).'/assets/img/favicon.png' );
        if($status === true):
	        add_submenu_page('miyn-app', __('Profile Settings'), __('Profile Settings'), 'manage_options', 'miyn-profile', [$this, 'miynapp_profile_settings_init']);
	        add_submenu_page('miyn-app', __('Widgets Settings'), __('Widgets Settings'), 'manage_options', 'miyn-widgets', [$this, 'miynapp_widgets_settings_init']);
	        add_submenu_page('miyn-app', __('Tools'), __('Tools'), 'manage_options', 'miyn-tools', [$this, 'miynapp_tools_settings_init']);
	        add_submenu_page('miyn-app', __('Shortcodes'), __('Shortcodes'), 'manage_options', 'miyn-shortcode', [$this, 'miynapp_widgets_shortcode_init']);
	        add_submenu_page('miyn-app', __('Embedded Frame'), __('Embedded Frame'), 'manage_options', 'miyn-embedded', [$this, 'miynapp_widgets_embedded_init']);
    	endif;
	}

	public function miynapp_rest_api_init() {

		//change banner image
	    register_rest_route( 'miyn-app-settings/v1', '/change-banner-image', array(
			'method' 	=> 'GET',
			'callback' 	=> function($request){
				$attachid = $request['attached-id'];

				if($attachid) {
					delete_option( 'miyn-banner-attachment' );
					$status = add_option('miyn-banner-attachment', $attachid);
				}

				if($status === true) {
			        $args = array(
				        'status'  => $status,
				        'attachid'=> $attachid
				    );
				} else {
			        $args = array(
				        'status'  => $status
				    );
				}
			    wp_send_json($args);
			    wp_die();
			}
		));

	}

	public function miynapp_get_connections_init() {
		$miynsecret = get_option('miyn-secret-key');
		if(!empty($miynsecret)) {
		    $apilink = 'https://app.miyn.app/api/12022019cq5hnwbrymsu1ld/'.$miynsecret;
			$content = wp_remote_get($apilink);
			$result  = json_decode($content['body'], true);
			$miynconnect = $result['message'];
			$status = ($miynconnect == 'success') ? true : false;
			$message = ($status === true) ? esc_html('Successfully connected with MIYN') : esc_html('Invalid secret key. Please create new key and update the key');
		} else {
			$status = false;
			$miynsecret = '';
			$apilink = '';
			$message = esc_html('Please connect with MIYN using secret key');
            $result = '';
		}
		$args = array(
			'status' 	=> $status,
			'message'	=> $message,
			'secret-key'=> $miynsecret,
			'apilink'	=> $apilink,
			'data'		=> $result,
		);
		return $args;
	}

	public function miynapp_get_business_connections_init() {
		$connections = $this->miynapp_get_connections_init();
		$status = $connections['status'];

		if($status === true) {
			$miynsecret = $connections['secret-key'];
			$apilink = $connections['apilink'];
			$message = $connections['message'];
			$result = $connections['data'];
		} else {
			$status = false;
			$message = esc_html('Please connect with MIYN using secret key');
		}

		$args = array(
			'status' 	=> $status,
			'message'	=> $message,
			'secret-key'=> $miynsecret,
			'apilink'	=> $apilink,
			'data'		=> $result,
		);
		return $args;
	}

	// UPLOAD IMAGE FROM MIYN 
	public function miynapp_upload_image_api($opnkey, $imglink){
		$imgslug = explode('/', $imglink);
		$imgslug = end($imgslug);
		$attid = get_option($opnkey);
		$attimg = wp_get_attachment_image_src($attid, 'full');
		$atturl = explode('/', $attimg[0]);
		// $atturl = end($atturl);
		if($imgslug != $atturl) {
			wp_delete_attachment($attid);
			$uploaddir = wp_upload_dir();
			$imagedata = wp_remote_get($imglink)['body'];
			$filename = basename($imglink);
			if ( wp_mkdir_p( $uploaddir['path'] ) ) {
			  $file = $uploaddir['path'] . '/' . $filename;
			}
			else {
			  $file = $uploaddir['basedir'] . '/' . $filename;
			}
			file_put_contents($file, $imagedata);
			$wp_filetype = wp_check_filetype( $filename, null );
			$attachment = array(
			  'post_mime_type' => $wp_filetype['type'],
			  'post_title' => sanitize_file_name( $filename ),
			  'post_content' => '',
			  'post_status' => 'inherit'
			);
			$attachid = wp_insert_attachment( $attachment, $file );
			require_once(ABSPATH . 'wp-admin' . '/includes/image.php');
			$attachdata = wp_generate_attachment_metadata( $attachid, $file );
			wp_update_attachment_metadata( $attachid, $attachdata );
			if($attachid) {
				delete_option($opnkey);
				$status = add_option($opnkey, $attachid);
			}
		} else {
			$attachid = false;
		}
		return $attachid;
	}

	// GET API DATA
	public function miynapp_get_api_data_init($apilink){
		$connections = $this->miynapp_get_connections_init();
		$status = $connections['status'];

		if($status === true) {
			$content = wp_remote_get($apilink);
			$result  = json_decode($content['body'], true);
			$message = esc_html('Successfully connected with MIYN');
		} else {
			$status = false;
			$message = esc_html('Please connect with MIYN using secret key');
			$result = '';
		}

		$args = array(
			'status' 	=> $status,
			'message'	=> $message,
			'data'		=> $result,
		);
		return $args;
	}

	// MIYN APP
	public function miynapp_connection_init() {

		$connections = $this->miynapp_get_connections_init();
		$err = $connections['message'];
		$status = $connections['status'];
		$key = $connections['secret-key'];
		$attachid =  get_option('miyn-banner-attachment');
		$logoid =  get_option('miyn-business-logo');
		
		if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['secret-key-submit'])) && wp_verify_nonce( $_POST['miyn_submit_api_nonce_field'], 'miyn_submit_api_action' ) && current_user_can( 'administrator' )){
			$miynkey = sanitize_text_field($_POST['miyn-secret-key']);
			delete_option('miyn-secret-key');
			add_option('miyn-secret-key', $miynkey);
		}

		if (($_SERVER['REQUEST_METHOD'] == 'POST') && (isset($_POST['disconnect-miyn-submit'])) && wp_verify_nonce( $_POST['miyn_submit_api_nonce_field'], 'miyn_submit_api_action' ) && current_user_can( 'administrator' )){
			delete_option('miyn-secret-key');
		}


		if($status === true) {
        	$data = $connections['data'];
        	$business = $data['business'];
        	$businesID = $business['uid'];
        	$bususer = $data['user'];
        	$busName = $business['business_name'];
        	$buslogo = $business['logo_image'];
        	$busDescription = $business['bussiness_short_description'];
        	$busemail = $business['b_email'];
        	$busphone = $bususer['phone_number'];
        	$busbanner = $business['photo_url'];
        	
		} else{
			$businesID = '';
		}

		$imglinks = 'https://app.miyn.app/api/background/image/'.$businesID;
		$imagelists = $this->miynapp_get_api_data_init($imglinks);
		$imgedata = $imagelists['data'];

		if(empty($logoid) && !empty($imgedata['logo_image'])):
			$attid = $this->miynapp_upload_image_api('miyn-business-logo', $imgedata['logo_image']);
			$logoid = get_option('miyn-business-logo');
		endif;

		if($attachid) {
			$imgurl = wp_get_attachment_image_src($attachid, 'full');
			$banner = $imgurl[0];
		} else {
			$banner = plugin_dir_url( __DIR__ ) . 'assets/img/plugin-banner.jpg';
		}
		if($logoid) {
			$logourl = wp_get_attachment_image_src($logoid, 'full');
			$logourl = $logourl[0];
		} else {
			$logourl = '';
		}
		//$logo = ($buslogo) ? 'https://app.miyn.app/images/business_logo/'.$buslogo : $logourl;

		?>
		<div class="miyn-app-wrapper">
			<div class="miyn-app-content-wrapper">
				<div class="miyn-app-content-area">
					<div class="miyn-app-plugin-banner" style="background-image: url(<?php echo esc_attr($banner); ?>);">
						<?php if($status === true): ?>
						<div class="edit-banner">
							<a href="#">
								<img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/edit-pencil.png'); ?>" alt="edit icon">
							</a>
							<input type="hidden" name="miyn-app-banner-image" id="miyn-app-banner-image" value="<?php echo ($attachid) ? esc_attr($attachid) : ''; ?>">
						</div>
						<?php endif; ?>
					</div>
					<?php if(!empty($err)): ?>
					<div class="notice-area <?php echo ($status !== true) ? esc_html('warning') : ''; ?>">
						<span class="notice-icon"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/speaker.png') ?>" alt="icon"></span><span><?php echo esc_attr($err); ?></span>'
					</div>
					<?php endif; ?>
					<?php if($status === true): ?>
					<div class="miyn-app-connected-business-info">
						<h4>Your business info :</h4>
						<?php if($buslogo): ?>
						<div class="business-logo">
							<img src="<?php echo esc_attr($logourl); ?>" alt="<?php echo esc_attr($busName); ?>">
						</div>
						<?php endif; ?>
						<div class="miyn-app-connected-business-details">
							<?php if(!empty($busName)): ?><h3><?php echo esc_attr($busName); ?></h3><?php endif; ?>
							<?php if(!empty($busDescription)): ?><p><?php echo esc_attr($busDescription); ?></p><?php endif; ?>
						</div>
						<div class="miyn-app-connected-business-contacts">
							<?php if(!empty($busemail)): ?>
								<a href="mailto:<?php echo esc_attr($busemail); ?>">
									<img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/envelope.png'); ?>" alt="envelope.png">
									<?php echo esc_attr($busemail); ?>
								</a>
							<?php endif; ?>
							<?php if(!empty($busphone)): ?>
								<a href="tel:<?php echo esc_attr($busphone); ?>">
									<img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/phone.png'); ?>" alt="phone.png">
									<?php echo esc_attr($busphone); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
					<div class="miyn-after-banner-title">
		        		<h1>LET'S CONNECT MIYN TO YOUR WEBSITE</h1>
		        	</div>
		        	<?php if($status !== true): ?>
		        	<div class="connection-intruction">
		        	    <p>1. Need to enable allow_url_fopen from your server or cpanel</p>
		        		<p>2. Go to <a href="https://miyn.app/signin/">MIYN</a> and login to your account. If you are not an existing user, please <a href="https://app.miyn.app/app/dashboard">register</a>.</p>
		        		<p>3. After Login to MIYN, Please click to Generate Secret Key From here: 
    	        			<a class="generate-key-button" target="_blank" href="https://app.miyn.app/app/settings/apikey">
    			        		<img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/key-icon.png'); ?>" alt="icon">
    			        		<span>Generate Secret Key</span>
    		        		</a>
		        		</p>
		        		<p style="padding-left: 25px;">a) Or Go to MIYN Dashboard >Settings >API KEY</p>
		        		<p style="padding-left: 25px;"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/api-generate.png'); ?>" alt=""/></p>
		        		<p style="padding-left: 25px;">b) Then Click on Generate API Key</p>
		        		<p>4. Put that API key Below and click on Connect with MIYN</p>
		        		<p>5. Now you are ready to go.</p>
		        	</div>
		        	<?php endif; ?>
		        	<form class="secret-key-form" id="submit_secret_key" name="secret-key" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=miyn-app' ) ); ?>">
		        		<input type="text" name="miyn-secret-key" id="miyn-secret-key" placeholder="Add secret key here..." value="<?php echo esc_attr($connections['secret-key']); ?>">
		        		<?php wp_nonce_field( 'miyn_submit_api_action', 'miyn_submit_api_nonce_field' ); ?>
		        		<?php if($status === true): ?>
		        		<button type="submit" class="submit-button disconnect-button" name="disconnect-miyn-submit" value="1"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/key-icon.png'); ?>" alt="key-icon"> <span>Disconnect with MIYN</span></button>
	        			<?php else: ?>
		        		<button type="submit" class="submit-button" name="secret-key-submit" value="1"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/key-icon.png'); ?>" alt="key-icon.png"> <span>Connect with MIYN</span></button>
		        		<?php endif; ?>
		        	</form>
				</div>
			</div>
			<div class="miyn-app-sidebar"></div>
		</div>
		<?php
	}

	//PROFILE SETTINGS
	public function miynapp_profile_settings_init() {
		$connections = $this->miynapp_get_business_connections_init();
		$countrylist = $this->country_lists();
		$key = $connections['secret-key'];
		$err = $connections['message'];
		$status = $connections['status'];

		
		if($status != true) {
			echo esc_html('<h3>You do not have permission to access this page.</h3>');
			return false;
		}
		$data = $connections['data'];
		$business = $data['business'];
		$businesID = $business['uid'];	
		$apilink = 'https://app.miyn.app/api/business/'.$business['uid'].'/'.$connections['secret-key'];
		$bname = $business['business_name'];	

		$imglinks = 'https://app.miyn.app/api/background/image/'.$businesID;
		$imagelists = $this->miynapp_get_api_data_init($imglinks);
		$imgedata = $imagelists['data'];

		$attachid =  get_option('miyn-banner-attachment');
		if($attachid) {
			$imgurl = wp_get_attachment_image_src($attachid, 'full');
			$banner = $imgurl[0];
		} else {
			$banner = plugin_dir_url( __DIR__ ) . 'assets/img/plugin-banner.jpg';
		}
		$bphone = $business['b_phone'];	
		$bemail = $business['b_email'];	
		$bdetails = $business['bussiness_short_description'];	
		$sendsms = $business['when_send_sms'];
		$sendname = $business['sender_name'];
		$bcategory = $business['business_category'];
		$bcountry = $business['b_country_id'];
		$baddress = $business['b_address'];
		$website = $business['b_website'];
		$multemail = $business['multiple_email'];

		if(!empty($imgedata['logo_image'])):
			$attid = $this->miynapp_upload_image_api('miyn-business-logo', $imgedata['logo_image']);
		endif;
		$blogo = wp_get_attachment_image_src(get_option('miyn-business-logo'), 'full');
		$blogo = $blogo[0];

		if(!empty($imgedata['photo_url'])):
			$attid = $this->miynapp_upload_image_api('photo_url', $imgedata['photo_url']);
		endif;
		$image_url = wp_get_attachment_image_src(get_option('photo_url'), 'full');
		$image_url = $image_url[0];

		$bgimage = $business['background_image'];
		if(!empty($imgedata['background_image'])):
			$attid = $this->miynapp_upload_image_api('background_image', $imgedata['background_image']);
		endif;
		$bgimage = wp_get_attachment_image_src(get_option('background_image'), 'full');
		$bgimage = $bgimage[0];

		$facebook = $business['facebook'];
		$twitter = $business['twitter'];
		$linkdin = $business['linkdin'];
		$instagram = $business['gmail'];

		$livesite = $data['livesitestyleinfo'];
		$actbg_color = $livesite['action_background_color'];
		$actbg_color = trim( $actbg_color );
		$actbg_color = str_replace('#', '', $actbg_color);
		// var_dump($livesite);
		?>
		<div class="miyn-app-wrapper">
			<div class="miyn-app-content-wrapper">
				<div class="miyn-app-content-area">
					<div class="miyn-app-plugin-banner" style="background-image: url(<?php echo esc_attr($banner); ?>);"></div>
					<?php if(!empty($err)): ?>
					<div class="notice-area <?php echo ($status !== true) ? esc_html('warning') : ''; ?>">
						<span class="notice-icon"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ ) . 'assets/img/speaker.png') ?>" alt="icon"></span><span><?php echo esc_attr($err); ?></span>
					</div>
					<?php endif; ?>
					<div class="business-info-area">
						<h2>Business Description</h2>
						<form class="business-defailt-form">
							<input type="hidden" name="miyn_app_secret_key" id="miyn_app_secret_key" value="<?php echo esc_attr($key); ?>">
							<input type="hidden" name="uid" id="uid" value="<?php echo esc_attr($businesID); ?>">
							<div class="business-details-area">
								<div class="miyn-app-profile-settings-field">
									<label for="business_names">Business Name <span class="required"> * </span></label>
									<input type="text" id="business_names" name="business_names" value="<?php echo ($bname) ? esc_attr($bname) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<div class="miyn-app-profile-settings-checkbox-field">
		                               <input type="checkbox" id="when_send_sms" name="when_send_sms" value="true" <?php if($sendsms == true): ?>checked=""<?php endif; ?>>
		                               <label for="when_send_sms">When sending Text Messages (SMS) use sender name</label>
		                           </div>
									<label for="sender_name">Sender Name</label>
									<input type="text" id="sender_name" name="sender_name" value="<?php echo ($sendname) ? esc_attr($sendname) : ''; ?>" class="form-control" maxlength="100" autocomplete="off">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="business_category">Business Category</label>
									<input type="text" id="business_category" name="business_category" value="<?php echo ($bcategory) ? esc_attr($bcategory) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="bussiness_short_description">Short description</label>
									<textarea id="bussiness_short_description" name="bussiness_short_description" class="form-control"><?php echo ($bdetails) ? esc_attr($bdetails) : ''; ?></textarea>
								</div>
							</div>
							<div class="miyn-app-business-assets-area">
								<div class="miyn-app-profile-settings-field miyn-app-profile-settings-file-field">
									<label>About us image <span> (Max. File Size 2Mb)</span></label>
									<p>Select the perfect brand imagery for your business</p>
									<p><span>(*recommended size 500px x 300px)</span></p>
									<div class="miyn-app-file-upload-field">
										<input type="file" id="photo_url" name="photo_url">
										<label for="photo_url"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>/assets/img/cloud-upload.svg" alt="cloud-upload.svg"/><span>Click to browse or drag an image here</span></label>
										<div class="preview-image">
											<?php if(!empty($image_url)): ?>
												<img src="<?php echo esc_attr($image_url); ?>" alt="about image"/>
					        					<a class="remove-preview" href="#">X</a>
				        					<?php endif; ?>
										</div>
									</div>
								</div>
								<div class="miyn-app-profile-settings-field miyn-app-profile-settings-file-field">
									<label>Logo & Brand Color <span> (Max. File Size 2Mb)</span></label>
									<p>Upload a logo (200px x 200px recommended) and choose a brand color for all your elements.</p>
									<div class="miyn-app-two-column-fields">
										<div class="miyn-app-file-upload-field miyn-app-profile-settings-field">
											<input type="file" id="logo_image" name="logo_image">
											<label for="logo_image"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>/assets/img/cloud-upload.svg" alt="cloud-upload.svg"/><span>Click to browse or drag an image here</span></label>
											<div class="preview-image">
												<?php if(!empty($blogo)): ?>
													<img src="<?php echo esc_attr($blogo); ?>" alt="brand logo"/>
						        					<a class="remove-preview" href="#">X</a>
					        					<?php endif; ?>
											</div>
										</div>
										<div class="miyn-app-profile-settings-field miyn-app-profile-brand-color">
											<input type="text" id="action_background_color" name="action_background_color" value="<?php echo ($actbg_color) ? esc_attr($actbg_color) : ''; ?>" class="widget-input color-input ng-pristine ng-valid ng-touched jscolor">
										</div>
									</div>
								</div>
							</div>
							<div class="miyn-app-client-portal-area">
								<div class="miyn-app-profile-settings-field miyn-app-profile-settings-file-field">
									<label>Background image for client portal <span> (Max. File Size 2Mb)</span></label>
									<p>Choose a cover image that introduces your brand / business to your potential client.</p>
									<p><span>(*recommended size 1920px x 325px)</span></p>
									<div class="miyn-app-file-upload-field">
										<input type="file" id="background_image" name="background_image">
										<label for="background_image"><img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>/assets/img/cloud-upload.svg" alt="cloud-upload.svg"/><span>Click to browse or drag an image here</span></label>
										<div class="preview-image">
											<?php if(!empty($bgimage)): ?>
												<img src="<?php echo esc_attr($bgimage); ?>" alt="client portal image"/>
					        					<a class="remove-preview" href="#">X</a>
				        					<?php endif; ?>
										</div>
									</div>
								</div>
							</div>
							<div class="miyn-app-profile-settings-client-info">
								<label>Contact Info</label>
								<div class="miyn-app-two-column-fields">
									<div class="miyn-app-profile-settings-field">
										<label for="b_country_id">Country</label>
										<select id="b_country_id" name="b_country_id"class="form-control">
											<?php 
											if(!empty($bcountry)): ?>
												<option value="<?php echo esc_attr($bcountry) ?>"><?php echo esc_attr($countrylist[$bcountry]); ?></option>
											<?php
											else:
											?>
												<option value="">Select Country...</option>
											<?php
											endif;
				
											unset($countrylist[$bcountry]);
											foreach ($countrylist as $key => $value) { ?>
												<option value="<?php echo esc_attr($key); ?>"><?php echo esc_attr($value); ?></option>
											<?php
											}
											?>
										</select>
									</div>
									<div class="miyn-app-profile-settings-field">
										<label for="b_phone">Phone</label>
										<input type="text" id="b_phone" name="b_phone" value="<?php echo ($bphone) ? esc_attr($bphone) : ''; ?>" class="form-control">
									</div>
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="b_address">Address</label>
									<input type="text" id="b_address" name="b_address" value="<?php echo ($baddress) ? esc_attr($baddress) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="b_email">Email</label>
									<input type="email" id="b_email" name="b_email" value="<?php echo ($bemail) ? esc_attr($bemail) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="multiple_email">Multiple Email Separate by (;)</label>
									<input type="text" id="multiple_email" name="multiple_email" value="<?php echo ($multemail) ? str_replace(',', '; ', esc_attr($multemail)) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="website_url">Website URL</label>
									<input type="text" id="website_url" name="website_url" value="<?php echo ($website) ? esc_attr($website) : ''; ?>" class="form-control">
								</div>
							</div>
							<div class="miyn-app-profile-settings-social-link">
								<label>Social Links</label>
								<div class="miyn-app-profile-settings-field">
									<label for="facebook">Facebook</label>
									<input type="text" id="facebook" name="facebook" value="<?php echo ($facebook) ? esc_attr($facebook) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="twitter">Twitter</label>
									<input type="text" id="twitter" name="twitter" value="<?php echo ($twitter) ? esc_attr($twitter) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="linkdin">Linkdin</label>
									<input type="text" id="linkdin" name="linkdin" value="<?php echo ($linkdin) ? esc_attr($linkdin) : ''; ?>" class="form-control">
								</div>
								<div class="miyn-app-profile-settings-field">
									<label for="instagram">Instagram</label>
									<input type="text" id="instagram" name="instagram" value="<?php echo ($instagram) ? esc_attr($instagram) : ''; ?>" class="form-control">
								</div>
							</div>
				            <div class="page-bar">
				                <button type="button" id="send-profile-settings" class="miyn-bt">Save</button>
				                <div class="error-message"></div>
						    </div>
						</form>
					</div>
				</div>
			</div>
			<div class="miyn-app-sidebar"></div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.miyn-app-file-upload-field input[type="file"]').change(function() {
				  	readFile(this);
				});
				$(document).on('click', '.miyn-app-file-upload-field .remove-preview', function(e) {
					e.preventDefault();
				  	$(this).closest('.preview-image').empty();
				  	$(this).closest('.miyn-app-file-upload-field').find('input[type="file"]').empty();
				});
			    $("#send-profile-settings").click(function(){
                 	$('.error-message').text('');
                 	$(this).attr('disabled', true);

                 	// console.log($('#action_background_color').val());

			        var form_data = new FormData($('.business-defailt-form')[0]);
			        var aboutimage = $('#photo_url')[0].files[0];
			        var business_logo = $('#logo_image')[0].files[0];
			        var portalbg = $('#background_image')[0].files[0];
			        var validImageTypes = ['image/gif', 'image/jpeg', 'image/jpg', 'image/png'];
			        // console.log(business_logo);
			        if($('#photo_url').val() != '') {
				        if(aboutimage.size > 2000000) {
		                 	$('.error-message').text('Please upload file less than 2MB. Thanks!');
                 			$("#send-profile-settings").attr('disabled', false);
		                 	return false;
				        }
						if (!validImageTypes.includes(aboutimage.type)) {
		                 	$('.error-message').text('Please upload valid file. Thanks!');
                 			$("#send-profile-settings").attr('disabled', false);
		                 	return false;
						}
			        }
			        if($('#logo_image').val() != '') {
				        if(business_logo.size > 2000000) {
		                 	$('.error-message').text('Please upload file less than 2MB. Thanks!');
                 			$("#send-profile-settings").attr('disabled', false);
		                 	return false;
				        }
						if (!validImageTypes.includes(business_logo.type)) {
		                 	$('.error-message').text('Please upload valid file. Thanks!');
                 			$("#send-profile-settings").attr('disabled', false);
		                 	return false;
						}
					}
			        if($('#background_image').val() != '') {
				        if(portalbg.size > 2000000) {
		                 	$('.error-message').text('Please upload file less than 2MB. Thanks!');
                 			$("#send-profile-settings").attr('disabled', false);
		                 	return false;
				        }
						if (!validImageTypes.includes(portalbg.type)) {
		                 	$('.error-message').text('Please upload valid file. Thanks!');
                 			$("#send-profile-settings").attr('disabled', false);
		                 	return false;
						}
			        }
			        // console.log($('.business-defailt-form').serialize());

			        $.ajax({
			            type: 'post',
			            processData: false,
                		contentType: false,
			            url: '<?php echo esc_attr($apilink); ?>',
			            data: form_data,
			            success: function (data) {
		                 	$('.error-message').text('Successfully saved data.');
		                 	window.setTimeout(function(){location.reload()},1000);
			            }
			        });
			    });
			});

			function readFile(input) {
			  if (input.files && input.files[0]) {
			  	// console.log(input.files);
			    var reader = new FileReader();

			    reader.onload = function(e) {
			    	var htmlPreview =
				        '<img src="' + e.target.result + '" />' +
				        '<a class="remove-preview" href="#">X</a>';
			      	// var wrapperZone = $(input).parent();
			      	// var previewZone = $(input).parent().parent().find('.preview-zone');
			      	var previewImage = jQuery(input).parent().find('.preview-image');

			      	// wrapperZone.removeClass('dragover');
			      	// previewZone.removeClass('hidden');
			      	previewImage.empty();
			      	previewImage.append(htmlPreview);
			       	// console.log(input.val());
			    };

			    reader.readAsDataURL(input.files[0]);
			  }
			}
		</script>
		<?php
	}

	//WIDGETS SETTINGS
	public function miynapp_widgets_settings_init() {
		$connections = $this->miynapp_get_connections_init();
		$status = $connections['status'];
		if($status != true) {
			echo esc_html('<h3>You do not have permission to access this page.</h3>');
			return false;
		}
		$err = $connections['message'];
		$data = $connections['data'];
		$business = $data['business'];

		$imglinks = 'https://app.miyn.app/api/background/image/'.$business['uid'];
		$imagelists = $this->miynapp_get_api_data_init($imglinks);
		$imgedata = $imagelists['data'];

		$uID = !empty($business['uid']) ? esc_html($business['uid']) : '';
		$bID = !empty($business['id']) ? esc_html($business['id']) : '';
		$key = !empty($business['secret_key']) ? esc_html($business['secret_key']) : '';
		$apilink = 'https://app.miyn.app/api/'.$business['uid'].'/'.$business['secret_key'];
		$livesitesinfo = $data['livesiteactioninfo'];
		$livesitestyle = $data['livesitestyleinfo'];
		?>
		<div class="miyn-widgets-settings-wrapper">
			<div class="miyn-widgets-settings-area">
				<form id="sendWidgetData">
					<input type="hidden" name="business-id" id="business-id" value="<?php echo esc_attr($bID); ?>">
	                <div class="widgets-toggle-area">
	                   <div class="widgets-section-titles">
	                       <span>Website Actions</span>
	                   </div>
	                   <div class="miyn-widgets-section-contents">
	                   		<?php 
	                   		foreach ($livesitesinfo as $info) {
	                   		?>
	                       	<div class="miyn-widgets-settings">
	                           <div class="miyn-widgets-settings-actions">
	                               <input type="checkbox" value="<?php echo esc_attr($info['view_status']); ?>" <?php echo ($info['view_status'] == 'true') ? esc_html('checked') : ''; ?> id="widget_checkbox_<?php echo esc_attr($info['id']); ?>" name="widget_checkbox_<?php echo esc_attr($info['id']); ?>" class="checkbox" onclick="call_after_while()">
	                               <label for="widget_checkbox_<?php echo esc_attr($info['id']); ?>"><?php echo esc_attr($info['text_field']); ?></label>
	                           </div>
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="calltoaction_text_<?php echo esc_attr($info['id']); ?>">Text</label>
	                            	<input type="text" value="<?php echo esc_attr($info['calltoaction_text']); ?>" name="calltoaction_text_<?php echo esc_attr($info['id']); ?>" id="calltoaction_text_<?php echo esc_attr($info['id']); ?>">
	                            </div>
	                        </div>
	                        <?php 
	                    	}
	                        ?>
	                   </div>	
	                </div>
	                <div class="widgets-toggle-area">
	                   	<div class="widgets-section-titles">
	                       <span>Website popup</span>
	                   	</div>
	                   	<div class="miyn-widgets-section-contents button-logo-area">
	                       	<div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="bottom_partial_button_<?php echo esc_attr($livesitestyle['business_id']); ?>">Label text</label>
	                            	<input type="text" value="<?php echo esc_attr($livesitestyle['bottom_partial_button']); ?>" name="bottom_partial_button_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="bottom_partial_button_<?php echo esc_attr($livesitestyle['business_id']); ?>">
	                            </div>
	                        </div>
                        	<?php 
                        	if(!empty($imgedata['label_partial_image'])):
								$attid = $this->miynapp_upload_image_api('change-widgets-image', $imgedata['label_partial_image']);
							endif;
							$parimag = wp_get_attachment_image_src(get_option('change-widgets-image'), 'full');
							$parimageurl = ($parimag[0]) ? $parimag[0] : '';
							$parimage = $livesitestyle['label_partial_image'];
                        	?>
	                       	<div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="label_partial_image_<?php echo esc_attr($livesitestyle['id']); ?>">Image</label>
	                                <div class="upload-business-log">
	                                    <div class="business-logo-upload">
	                                        <img id="blah" src="<?php echo ($parimage) ? esc_attr($parimageurl) : ''; ?>">
	                                		<input type="hidden" value="<?php echo ($parimage) ? esc_attr($parimageurl) : ''; ?>" name="label_partial_image_<?php echo esc_attr($livesitestyle['id']); ?>" id="label_partial_image_<?php echo esc_attr($livesitestyle['id']); ?>">											
										</div>
	                                    <div class="logo-upload">
											<label class="widgetimage-button" for="widgetimage">Upload Picture</label>
											<input type="file" id="widgetimage" name="widgetimage" >
	                                        <!-- <button type="button" imageonly="staff_image" class="miyn-bt">Upload Picture </button> -->
	                                        <p>Max. File Size 2Mb</p>
	                                    </div>
	                                </div>
	                            </div>
	                        </div>
	                       	<div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="title_partial_label_<?php echo esc_attr($livesitestyle['business_id']); ?>">Title</label>
	                            	<input type="text" type="text" value="<?php echo esc_attr($livesitestyle['title_partial_label']); ?>" name="title_partial_label_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="title_partial_label_<?php echo esc_attr($livesitestyle['business_id']); ?>">
	                            </div>
	                        </div>
	                       	<div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="partial_text_<?php echo esc_attr($livesitestyle['business_id']); ?>">Text</label>
	                            	<textarea id="ck_content" class="custom-miyn-app-editor" name="partial_text_<?php echo esc_attr($livesitestyle['business_id']); ?>" height="400"><?php echo esc_attr($livesitestyle['partial_text']); ?></textarea>
	                            </div>
	                        </div>
	                       	<div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="selected_button_text_<?php echo esc_attr($livesitestyle['business_id']); ?>">Button Text</label>
	                            	<input type="text" value="<?php echo esc_attr($livesitestyle['selected_button_text']); ?>" name="selected_button_text_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="selected_button_text_<?php echo esc_attr($livesitestyle['business_id']); ?>">
	                            </div>
	                        </div>
	                   </div>	
	                </div>
	                <div class="widgets-toggle-area">
	                   <div class="widgets-section-titles">
	                       <span>Widget Properties</span>
	                   </div>
	                   <div class="miyn-widgets-section-contents">
	                       <div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                        	 	<fieldset>
	  									<legend>Right button box</legend>
	  									<div class="miyn-widgets-settings-actions">
			                            	<input type="checkbox" value="<?php echo esc_attr($livesitestyle['rounded_button']); ?>" <?php echo ($livesitestyle['rounded_button'] == 'true') ? esc_html('checked') : ''; ?> id="rounded_bottom_<?php echo esc_attr($livesitestyle['business_id']); ?>" name="rounded_bottom_<?php echo esc_attr($livesitestyle['business_id']); ?>" class="checkbox" onclick="call_after_while()">
			                                <label for="rounded_bottom_<?php echo esc_attr($livesitestyle['business_id']); ?>">View Right Button</label>
		                            	</div>
	  								</fieldset>
	                            </div>
	                            <div class="miyn-widgets-settings-texts">
	                        	 	<fieldset>
	  									<legend>Bottom button box</legend>
	  									<div class="miyn-widgets-settings-actions">
			                            	<input type="checkbox" value="<?php echo esc_attr($livesitestyle['button_bottom']); ?>" <?php echo ($livesitestyle['button_bottom'] == 'true') ? esc_html('checked') : ''; ?> id="button_bottom_<?php echo esc_attr($livesitestyle['business_id']); ?>" name="button_bottom_<?php echo esc_attr($livesitestyle['business_id']); ?>" class="checkbox" onclick="call_after_while()">
			                                <label for="button_bottom_<?php echo esc_attr($livesitestyle['business_id']); ?>">View Bottom Button</label>
		                            	</div>
		                                <div class="miyn-widgets-layout-area">
		                                	<label class="layout-area-label">Open box</label>
		                                	<div class="miyn-widgets-layout-selects">
			                                	<div class="miyn-widgets-layout-view">
			                                        <input type="radio" value="box" name="popuptype" id="popuptype" onclick="auto_submit_form()" <?php if($livesitestyle['button_bottom_open_box'] == 'box') echo esc_html('checked'); ?>>
			                                        <label for="popuptype">
			                                            <span>Box</span>
			                                            <img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>assets/img/box-image.png" alt="box-image.png">
			                                        </label>
			                                	</div>
			                                	<div class="miyn-widgets-layout-view">
	                                                <input type="radio" value="popup" name="popuptype" id="popuptype-1" onclick="auto_submit_form()" <?php if($livesitestyle['button_bottom_open_box'] == 'popup') echo esc_html('checked'); ?>>
	                                                <label for="popuptype-1">
	                                                    <span>Popup</span>
	                                                    <img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>assets/img/popup-image.png" alt="popup-image.png">
	                                                </label>
			                                	</div>
		                                	</div>
		                                </div>
		                                <div class="miyn-widgets-layout-area">
		                                	<label class="layout-area-label">Link type</label>
		                                	<div class="miyn-widgets-layout-selects">
			                                	<div class="miyn-widgets-layout-view">
	                                                <input type="radio" value="gridbox" name="linktype" id="linktype-1" onclick="call_after_while()" <?php if($livesitestyle['button_bottom_design_type'] == 'gridbox') echo esc_html('checked'); ?>>
	                                                <label for="linktype-1">
	                                                    <span>Grid Box</span>
	                                                    <img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>assets/img/grid-box-image.png" alt="grid-box-image.png">
	                                                </label>
			                                	</div>
			                                	<div class="miyn-widgets-layout-view">
	                                                <input type="radio" value="rowbox" name="linktype" id="linktype-2" onclick="call_after_while()" <?php if($livesitestyle['button_bottom_design_type'] == 'rowbox') echo esc_html('checked'); ?>>
	                                                <label for="linktype-2">
	                                                    <span>Raw Box</span>
	                                                    <img src="<?php echo esc_url(plugin_dir_url( __DIR__ )); ?>assets/img/raw-box-image.png" alt="raw-box-image.png">
	                                                </label>
			                                	</div>
		                                	</div>
		                                </div>
	  								</fieldset>
	                            </div>
	                            <div class="miyn-widgets-settings-texts">
	                        	 	<fieldset>
	  									<legend>Chat box</legend>
	  									<div class="miyn-widgets-settings-actions">
			                            	<input type="checkbox" value="<?php echo esc_attr($livesitestyle['chat_box']); ?>" <?php echo ($livesitestyle['chat_box'] == 'true') ? esc_html('checked') : ''; ?> id="chat_box_<?php echo esc_attr($livesitestyle['business_id']); ?>" name="chat_box_<?php echo esc_attr($livesitestyle['business_id']); ?>" class="checkbox" onclick="call_after_while()">
			                                <label for="chat_box_<?php echo esc_attr($livesitestyle['business_id']); ?>">View Chat Box</label>
	  									</div>
	  								</fieldset>
	                            </div>
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="font_color_<?php echo esc_attr($livesitestyle['business_id']); ?>">Font Color</label>
	                            	<input type="text" name="font_color_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="font_color_<?php echo esc_attr($livesitestyle['business_id']); ?>" value="<?php echo esc_attr($livesitestyle['action_text_color']); ?>" autocomplete="off" class="widget-input color-input ng-pristine ng-valid ng-touched jscolor">
	                            </div>
	                            <div class="miyn-widgets-settings-texts">
	                                <label for="background_color_<?php echo esc_attr($livesitestyle['business_id']); ?>">Background Color</label>
	                            	<input type="text" name="background_color_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="background_color_<?php echo esc_attr($livesitestyle['business_id']); ?>" value="<?php echo esc_attr($livesitestyle['action_background_color']); ?>" autocomplete="off" class="widget-input color-input ng-pristine ng-valid ng-touched jscolor">
	                            </div>
	                        </div>
	                   </div>	
	                </div>
	                <div class="widgets-toggle-area">
	                   <div class="widgets-section-titles">
	                       <span>Form Properties</span>
	                   </div>
	                   <div class="miyn-widgets-section-contents miyn-form-properties-toggle-content">
	                       	<div class="miyn-widgets-settings">
	                            <div class="miyn-widgets-settings-texts">
	                        	 	<fieldset>
	  									<legend>Booking Form</legend>
	  									<div class="miyn-widgets-settings-actions">
			                                <label for="first_form_<?php echo esc_attr($livesitestyle['business_id']); ?>">First form title</label>
			                            	<input type="text" class="widget-input" name="first_form_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="first_form_<?php echo esc_attr($livesitestyle['business_id']); ?>" value="<?php echo esc_attr($livesitestyle['formOneTitle']); ?>">
	  									</div>
	  									<div class="miyn-widgets-settings-actions view-second-action">
			                            	<input type="checkbox" value="<?php echo esc_attr($livesitestyle['isFormTwo']); ?>" <?php echo ($livesitestyle['isFormTwo'] == 'true') ? esc_html('checked') : ''; ?> id="isFormTwo_<?php echo esc_attr($livesitestyle['business_id']); ?>" name="isFormTwo_<?php echo esc_attr($livesitestyle['business_id']); ?>" class="checkbox" onclick="call_after_while()">
			                                <label for="isFormTwo_<?php echo esc_attr($livesitestyle['business_id']); ?>">View  Second</label>
		                            	</div>
		                            	<div class="fieldset-fields">
			                                <label for="second_form_<?php echo esc_attr($livesitestyle['business_id']); ?>">Second form title</label>
			                            	<input type="text" class="widget-input" name="second_form_<?php echo esc_attr($livesitestyle['business_id']); ?>" id="second_form_<?php echo esc_attr($livesitestyle['business_id']); ?>" value="<?php echo esc_attr($livesitestyle['formTwoTitle']); ?>">
		                            	</div>
	  								</fieldset>
	                            </div>
	                        </div>
	                   </div>	
	                </div>
	                <input type="hidden" name="miyn_app_secret_key" id="miyn_app_secret_key" value="<?php echo esc_attr($key); ?>">
	            </form>
	            <div class="page-bar">
	                <button type="button" id="send_form" class="miyn-bt">Save</button>
			    	<div class="show-notice"></div>
			    </div>
			</div>   
		</div>
        <script>
            var path = '<?php echo esc_url(get_site_url()); ?>';
        </script>
		<script>
		    // CKEDITOR.replace( 'ck_content' );
			ClassicEditor
		    .create( document.querySelector( '#ck_content' ), {
		        toolbar: {
				    items: [
				        'heading', '|',
				        'alignment', '|',
				        'bold', 'italic', 'strikethrough', 'underline', 'subscript', 'superscript', '|',
				        'link', '|',
				        'bulletedList', 'numberedList', 'todoList',
				        //'-', // break point
				        'fontfamily', 'fontsize', 'fontColor', 'fontBackgroundColor', '|',
				        'code', 'codeBlock', '|',
				        'insertTable', '|',
				        'outdent', 'indent', '|',
				        'uploadImage', 'blockQuote', '|',
				        'undo', 'redo'
				    ],
				    shouldNotGroupWhenFull: true
				},
		        heading: {
		            options: [
		                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
		                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
		                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
		                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
		                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
		                { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
		                { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
		            ]
		        }
		    } )
		    .then( newEditor => {
		        editor = newEditor;
		    } )
		    .catch( error => {
		        console.log( error );
		    } );

		</script>
		<script>
		function call_after_while(){
		    setTimeout(function() { 
		    	auto_submit_form(); 
		    	// backdropoff(); 
		    }, 3000);
		}

		function auto_submit_form(){
	        var formdata = jQuery("#sendWidgetData").serialize();

	        jQuery("#sendWidgetData input:checkbox:not(:checked)").each(function(e){
	          	formdata += "&"+this.name+'=false';
	        });
	        
	        var popuptype = jQuery('#popuptype').val();
	       
	        jQuery.ajax({
	            type: 'post',
	            url:'<?php echo esc_attr($apilink); ?>',
	            data:formdata,
	            success: function (data) {
	            	jQuery('.show-notice').html('Successfully save');
	               window.location.reload();
	            }
	        });
	        
	    }

		jQuery(document).ready(function($) {
		    $(".checkbox").change(function() {
		        if(this.checked) {
		            //Do stuff
		            this.value = "true"
		        } else {
		             this.value = "false"
		        }
		    });
		    
		    $("#widgetimage").on('change', function(){
				var form_data = new FormData($('#sendWidgetData')[0]);

				var widgetimage = $('#widgetimage')[0].files[0];
		        form_data.append('widgetimage', widgetimage);
				// console.log(widgetimage);
		       
		        $.ajax({
		            type: 'post',
		            url: '<?php echo esc_attr($apilink); ?>',
		            data: form_data,
		            processData: false,
		            contentType: false,
		            success: function (data) {
	            		// uploadImageMediaLibrary(form_data, widgetimage);
	                 	console.log(data);
		            	jQuery('.show-notice').html('Successfully save');
		               	window.location.reload();
		            }
		        });
		    });

		    $("#send_form").click(function(){
				var form_data = new FormData($('#sendWidgetData')[0]);
				var ckpost = editor.getData();
		        form_data.append($('#ck_content').attr('name'), ckpost);
		        var popuptype = $('#popuptype').val();
		       
		        $.ajax({
		            type: 'post',
		            url: '<?php echo esc_attr($apilink); ?>',
		            data: form_data,
		            processData: false,
		            contentType: false,
		            success: function (data) {
		            	jQuery('.show-notice').html('Successfully save');
		               window.location.reload();
		            }
		        });
		    });

			function partialImagepreview(input) {
				if (input.files && input.files[0]) {
					var reader = new FileReader();
					reader.onload = function(e) {
						$('#blah').attr('src', e.target.result);
					}
					reader.readAsDataURL(input.files[0]); // convert to base64 string
				}
			}

			$("#widgetimage").change(function() {
				partialImagepreview(this);
			});
		});


		</script>
		<?php
	}
	
	// MIYN TOOLS SETTINGS METHOD
	public function miynapp_tools_settings_init() {
	    if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'administrator' ) && wp_verify_nonce( $_POST['miyn_exclude_pages_nonce_field'], 'miyn_exclude_pages_action' )):
	        $ids = sanitize_text_field($_POST['exclude-ids']);
			$key = get_option('miyn-exclude-pages');
			if(!empty($key)) {
				$status = update_option('miyn-exclude-pages', $ids);
			} else {
				$status = add_option('miyn-exclude-pages', $ids);
			}
        endif;
        $exids = get_option('miyn-exclude-pages');
	    ?>
		<div class="miyn-app-wrapper">
			<div class="business-info-area">
				<h2 class="miyn-app-page-title">Tools</h2>
				<div class="miyn-exclude-pages" style="margin-top: 20px;">
				    <form method="post" action="">
                       	<div class="miyn-widgets-settings">
                            <div class="miyn-app-profile-settings-field">
                                <label for="exclude-ids">Exclude Page ID's</label>
                                <p>Exclude MIYN on certain pages on your site (e.g. contact page, forms pages). Add the page ID to exclude MIYN from appearing and separate with commas (e.g. 10, 102, 8030)</p>
				                <textarea class="form-control" name="exclude-ids" id="exclude-ids" placeholder="Add page ID for exclude miyn app with comma (,)" height="100"><?php echo ($exids) ? esc_attr($exids) : ''; ?></textarea>
								<?php wp_nonce_field( 'miyn_exclude_pages_action', 'miyn_exclude_pages_nonce_field' ); ?>
                            </div>
                        </div>
                        <div class="page-bar" style="margin-top: 20px;">
				            <button type="submit" class="submit-button" name="submit-tools">Submit</button>
				        </div>
				    </form>
				</div>
			</div>
		</div>
	    <?php
	}

	public function miynapp_widgets_shortcode_init() {
		$connections = $this->miynapp_get_connections_init();
		$status = $connections['status'];
		if($status != true) {
			echo esc_html('<h3>You do not have permission to access this page.</h3>');
			return false;
		}
		?>
		<div class="miyn-app-wrapper">
			<div class="miyn-content-area-wrapper">
				<h2 class="miyn-app-page-title">Add Short Code in your website</h2>
				<div class="shortcode-details">
					<p>You can add in (any tag) class attribute value in your html code</p>
					<table>
						<thead>
							<tr>
								<th>Title</th>
								<th>Class</th>
								<th>URL Param</th>
								<th>Box Class</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>My account</td>
								<td>miynacc</td>
								<td>?action=miyn-acc</td>
								<td>openbox miynboxacc</td>
							</tr>
							<tr>
								<td>Shedule</td>
								<td>miynschedule</td>
								<td>?action=miyn-schedule</td>
								<td>openbox miynboxschedule</td>
							</tr>
							<tr>
								<td>Call Now</td>
								<td>miyncall</td>
								<td>----</td>
								<td>----</td>
							</tr>
							<tr>
								<td>Share your document</td>
								<td>miynfile</td>
								<td>?action=miyn-fileopenbox</td>
								<td>miynboxfile</td>
							</tr>
							<tr>
								<td>Leave your detail</td>
								<td>smiyngreeting</td>
								<td>?action=miyn-greeting</td>
								<td>openbox miynboxgreeting</td>
							</tr>
							<tr>
								<td>Get Direction</td>
								<td>miyngooglemap</td>
								<td>?action=miyn-googlemap</td>
								<td>openbox miynboxgooglemap</td>
							</tr>
							<tr>
								<td>Our Rules & Regulations</td>
								<td>miyntext</td>
								<td>?action=miyn-customform</td>
								<td>-----</td>
							</tr>
							<tr>
								<td>Get a Quote</td>
								<td>miynquote</td>
								<td>?action=miyn-quote</td>
								<td>openbox miynboxquote</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			<div class="miyn-app-sidebar"></div>
		</div>
		<?php
	}

	public function miynapp_widgets_embedded_init() {
		$connections = $this->miynapp_get_business_connections_init();
		$status = $connections['status'];
		if($status != true) {
			echo esc_html('<h3>You do not have permission to access this page.</h3>');
			return false;
		}
		$business = $connections['data']['business'];
		$buid = $business['uid'];
		?>
		<div class="miyn-app-wrapper">
			<div class="miyn-content-area-wrapper">
				<h2 class="miyn-app-page-title">Add frame in your website</h2>
				<div class="shortcode-details">
					<p>You can add to your html code</p>
					<div class="miyn-app-profile-settings-field">
						<label for="sender_name">Schedule Frame</label>
						<textarea class="form-control"><iframe id="iFrame" src="https://live.miyn.app/online_newschedule/<?php echo esc_attr($buid); ?>" style="display: block;"></iframe></textarea>
					</div>
					<div class="miyn-app-profile-settings-field">
						<label for="sender_name">Send File Frame</label>
						<textarea class="form-control"><iframe id="iFrame" src="https://live.miyn.app/new_send_file/<?php echo esc_attr($buid); ?>" style="display: block;"></iframe></textarea>
					</div>
					<div class="miyn-app-profile-settings-field">
						<label for="sender_name">Send A Quote</label>
						<textarea class="form-control"><iframe id="iFrame" src="https://live.miyn.app/new-quote/<?php echo esc_attr($buid); ?>" style="display: block;"></iframe></textarea>
					</div>
					<div class="miyn-app-profile-settings-field">
						<label for="sender_name">Send A Message</label>
						<textarea class="form-control"><iframe id="iFrame" src="https://live.miyn.app/new-greeting/<?php echo esc_attr($buid); ?>" style="display: block;"></iframe></textarea>
					</div>
					<div class="miyn-app-profile-settings-field">
						<label for="sender_name">Google Map</label>
						<textarea class="form-control"><iframe id="iFrame" src="https://live.miyn.app/new-google-map/<?php echo esc_attr($buid); ?>" style="display: block;"></iframe></textarea>
					</div>
					<div class="miyn-app-profile-settings-field">
						<label for="sender_name">Embedded Booking</label>
						<textarea class="form-control"><iframe id="iFrame" src="https://live.miyn.app/booking/<?php echo esc_attr($buid); ?>" style="display: block;"></iframe></textarea>
					</div>
				</div>
			</div>
			<div class="miyn-app-sidebar"></div>
		</div>
		<?php
	}

	public function country_lists() {
		$countrylist = array(
			193 => 'Afghanistan',
	        201 => 'Aland Islands',
	        202 => 'Albania',
	        40 => 'Algeria',
	        203 => 'American Samoa',
	        91 => 'Andorra',
	        14 => 'Angola',
	        204 => 'Anguilla',
	        205 => 'Antarctica',
	        206 => 'Antigua and Barbuda',
	        186 => 'Argentina',
	        15 => 'Armenia',
	        172 => 'Aruba',
	        207 => 'Australia',
	        179 => 'Austria',
	        208 => 'Azerbaijan',
	        107 => 'Bahamas',
	        116 => 'Bangladesh',
	        99 => 'Barbados',
	        209 => 'Belarus',
	        210 => 'Belgium',
	        211 => 'Belize',
	        123 => 'Benin',
	        100 => 'Bermuda',
	        137 => 'Bhutan',
	        163 => 'Bolivia',
	        212 => 'Bolivia, Plurinational State of',
	        213 => 'Bonaire, Sint Eustatius and Saba',
	        27 => 'Bosnia and Herzegovina',
	        214 => 'Botswana',
	        215 => 'Bouvet Island',
	        18 => 'Bouvet Island (Bouvetoya)',
	        81 => 'Brazil',
	        1 => 'British Indian Ocean Territory',
	        184 => 'British Virgin Islands',
	        189 => 'Brunei Darussalam',
	        126 => 'Bulgaria',
	        196 => 'Burkina Faso',
	        199 => 'Burundi',
	        197 => 'Cambodia',
	        98 => 'Cameroon',
	        67 => 'Canada',
	        43 => 'Cape Verde',
	        79 => 'Cayman Islands',
	        216 => 'Central African Republic',
	        217 => 'Chad',
	        77 => 'Chile',
	        101 => 'China',
	        164 => 'Christmas Island',
	        93 => 'Cocos (Keeling) Islands',
	        28 => 'Colombia',
	        90 => 'Comoros',
	        85 => 'Congo',
	        218 => 'Congo, The Democratic Republic of the',
	        111 => 'Cook Islands',
	        105 => 'Costa Rica',
	        106 => 'Cote d\'Ivoire',
	        135 => 'Croatia',
	        16 => 'Cuba',
	        219 => 'Curaçao',
	        220 => 'Cyprus',
	        88 => 'Czech Republic',
	        54 => 'Denmark',
	        13 => 'Djibouti',
	        143 => 'Dominica',
	        7 => 'Dominican Republic',
	        155 => 'Ecuador',
	        221 => 'Egypt',
	        24 => 'El Salvador',
	        175 => 'Equatorial Guinea',
	        47 => 'Eritrea',
	        222 => 'Estonia',
	        223 => 'Ethiopia',
	        198 => 'Falkland Islands (Malvinas)',
	        142 => 'Faroe Islands',
	        224 => 'Fiji',
	        109 => 'Finland',
	        160 => 'France',
	        166 => 'French Guiana',
	        225 => 'French Polynesia',
	        36 => 'French Southern Territories',
	        108 => 'Gabon',
	        84 => 'Gambia',
	        2 => 'Georgia',
	        32 => 'Germany',
	        112 => 'Ghana',
	        162 => 'Gibraltar',
	        82 => 'Greece',
	        57 => 'Greenland',
	        226 => 'Grenada',
	        191 => 'Guadeloupe',
	        161 => 'Guam',
	        46 => 'Guatemala',
	        80 => 'Guernsey',
	        33 => 'Guinea',
	        156 => 'Guinea-Bissau',
	        168 => 'Guyana',
	        150 => 'Haiti',
	        146 => 'Heard Island and McDonald Islands',
	        124 => 'Holy See (Vatican City State)',
	        4 => 'Honduras',
	        147 => 'Hong Kong',
	        152 => 'Hungary',
	        68 => 'Iceland',
	        5 => 'India',
	        10 => 'Indonesia',
	        86 => 'Iran',
	        227 => 'Iran, Islamic Republic of',
	        51 => 'Iraq',
	        228 => 'Ireland',
	        44 => 'Isle of Man',
	        12 => 'Israel',
	        195 => 'Italy',
	        83 => 'Jamaica',
	        180 => 'Japan',
	        136 => 'Jersey',
	        157 => 'Jordan',
	        187 => 'Kazakhstan',
	        167 => 'Kenya',
	        229 => 'Kiribati',
	        94 => 'Korea',
	        230 => 'Korea, Democratic People\'s Republic of',
	        231 => 'Korea, Republic of',
	        138 => 'Kuwait',
	        173 => 'Kyrgyz Republic',
	        232 => 'Kyrgyzstan',
	        233 => 'Lao People\'s Democratic Republic',
	        115 => 'Latvia',
	        58 => 'Lebanon',
	        39 => 'Lesotho',
	        66 => 'Liberia',
	        234 => 'Libya',
	        125 => 'Libyan Arab Jamahiriya',
	        34 => 'Liechtenstein',
	        127 => 'Lithuania',
	        132 => 'Luxembourg',
	        69 => 'Macao',
	        113 => 'Macedonia',
	        235 => 'Macedonia, Republic of Yugoslavia',
	        165 => 'Madagascar',
	        236 => 'Malawi',
	        37 => 'Malaysia',
	        55 => 'Maldives',
	        128 => 'Mali',
	        48 => 'Malta',
	        237 => 'Marshall Islands',
	        185 => 'Martinique',
	        181 => 'Mauritania',
	        3 => 'Mauritius',
	        87 => 'Mayotte',
	        38 => 'Mexico',
	        238 => 'Micronesia, Federated States of',
	        182 => 'Moldova',
	        239 => 'Moldova, Republic of',
	        11 => 'Monaco',
	        240 => 'Mongolia',
	        59 => 'Montenegro',
	        241 => 'Montserrat',
	        35 => 'Morocco',
	        41 => 'Mozambique',
	        190 => 'Myanmar',
	        192 => 'Namibia',
	        242 => 'Nauru',
	        243 => 'Nepal',
	        174 => 'Netherlands',
	        153 => 'Netherlands Antilles',
	        21 => 'New Caledonia',
	        23 => 'New Zealand',
	        49 => 'Nicaragua',
	        8 => ' Niger',
	        144 => 'Nigeria',
	        19 => 'Niue',
	        140 => 'Norfolk Island',
	        22 => 'Northern Mariana Islands',
	        244 => 'Norway',
	        178 => 'Oman',
	        9 => ' Pakistan',
	        50 => 'Palau',
	        245 => 'Palestine, State of',
	        119 => 'Palestinian Territories',
	        159 => 'Panama',
	        95 => 'Papua New Guinea',
	        63 => 'Paraguay',
	        188 => 'Peru',
	        148 => 'Philippines',
	        131 => 'Pitcairn Islands',
	        29 => 'Poland',
	        102 => 'Portugal',
	        89 => 'Puerto Rico',
	        96 => 'Qatar',
	        42 => 'Reunion',
	        145 => 'Romania',
	        56 => 'Russian Federation',
	        72 => 'Rwanda',
	        158 => 'Saint Barthelemy',
	        97 => 'Saint Helena',
	        246 => 'Saint Helena, Ascension and Tristan da Cunha',
	        194 => 'Saint Kitts and Nevis',
	        247 => 'Saint Lucia',
	        134 => 'Saint Martin',
	        248 => 'Saint Martin (French part)',
	        122 => 'Saint Pierre and Miquelon',
	        114 => 'Saint Vincent and the Grenadines',
	        118 => 'Samoa',
	        53 => 'San Marino',
	        249 => 'Sao Tome and Principe',
	        117 => 'Saudi Arabia',
	        70 => 'Senegal',
	        20 => 'Serbia',
	        52 => 'Seychelles',
	        62 => 'Sierra Leone',
	        170 => 'Singapore',
	        250 => 'Sint Maarten (Dutch part)',
	        251 => 'Slovakia',
	        45 => 'Slovenia',
	        200 => 'Solomon Islands',
	        141 => 'Somalia',
	        129 => 'South Africa',
	        30 => 'South Georgia and the South Sandwich Islands',
	        253 => 'South Sudan',
	        149 => 'Spain',
	        17 => 'Sri Lanka',
	        252 => 'Sudan',
	        31 => 'Suriname',
	        25 => 'Svalbard & Jan Mayen Islands',
	        254 => 'Svalbard and Jan Mayen',
	        169 => 'Swaziland',
	        176 => 'Sweden',
	        255 => 'Switzerland',
	        92 => 'Syrian Arab Republic',
	        78 => 'Taiwan',
	        139 => 'Tajikistan',
	        183 => 'Tanzania',
	        256 => 'Tanzania, United Republic of',
	        120 => 'Thailand',
	        110 => 'Timor-Leste',
	        74 => 'Togo',
	        257 => 'Tokelau',
	        76 => 'Tonga',
	        177 => 'Trinidad and Tobago',
	        73 => 'Tunisia',
	        258 => 'Turkey',
	        259 => 'Turkmenistan',
	        260 => 'Turks and Caicos Islands',
	        261 => 'Tuvalu',
	        61 => 'Uganda',
	        121 => 'Ukraine',
	        133 => 'United Arab Emirates',
	        103 => 'United Kingdom',
	        75 => 'United States Minor Outlying Islands',
	        154 => 'United States of America',
	        171 => 'United States Virgin Islands',
	        104 => 'Uruguay',
	        26 => 'Uzbekistan',
	        151 => 'Vanuatu',
	        6 => 'Venezuela',
	        262 => 'Venezuela, Bolivarian Republic of',
	        263 => 'Viet Nam',
	        60 => 'Vietnam',
	        264 => 'Virgin Islands, British',
	        265 => 'Virgin Islands, U.S.',
	        71 => 'Wallis and Futuna',
	        65 => 'Western Sahara',
	        130 => 'Yemen',
	        64 => 'Zambia',
	        266 => 'Zimbabwe',
		);
		return $countrylist;
	}

	public function miynapp_add_embeded_code() {
		$connections = $this->miynapp_get_connections_init();
        $status = $connections['status'];
        if($status === true) {
            $data = $connections['data'];
            $business = $data['business'];
            $uid = $business['uid'];
        } else {
        	return false;
        }
		global $wp_query;
		$excludes = explode(',', get_option('miyn-exclude-pages'));
		$cpageid = $wp_query->get_queried_object_id();
		if((!empty($uid) && (in_array($cpageid, $excludes) !== true))):
		?>
		<script type="text/javascript" charset="utf-8">
		    jQuery.noConflict();
			window.onload = function() {
			    MIYNLive.init({
					uid: '<?php echo esc_attr($uid); ?>',
					ui: false,
					buttonprefix: true,
			    	paramName: 'action'
				});
			};
			(function(d, s, id){
			    var js, fjs = d.getElementsByTagName(s)[0],
			        p = 'https://',
			        r = Math.floor((20000000 - 10) * Math.random());
			    if (d.getElementById(id)) {return;}
			    js = d.createElement(s); js.id = id; js.setAttribute('async','true');   js.setAttribute('crossorigin','anonymous');
			    js.src = p + "live.miyn.app/site/js/liveschedule.js?" + r;
			    fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'miynsite-jssdk'));
		    jQuery.noConflict();
		</script>
		<?php
		endif;
	}
	
	public function miynapp_jquery_library_check_init(){    
	    if ( ! wp_script_is( 'jquery', 'enqueued' )) {
            //Enqueue
            wp_enqueue_script( 'jquery' );
        }
	}

 }