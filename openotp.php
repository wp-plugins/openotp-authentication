<?php
/**
 * Plugin Name: OpenOTP Two Factor Authentication
 * Plugin URI: http://www.rcdevs.com/downloads/index.php?id=eb1fe95690e94c38fb5356f83ad9aecc
 * Description: Add <a href="http://www.rcdevs.com/">OpenOTP</a> two-factor authentication to WordPress.
 * Author: RCDevs Inc
 * Version: 1.2.0
 * Author URI: https://www.rcdevs.com
 * License: GPL2+
 * Text Domain: openotp

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//require_once 'openotp.class.php';

class OpenOTP_auth {
    /**
     * Class variables
     */
    // singleton
    private static $__instance = null;

    // Some plugin info
    protected $name = 'OpenOTP Two-Factor Authentication';
	
	// OpenOTP API
	protected $openotp_auth = null;
    private $state = null;
    private $message = null;
    private $timeout = null;
    private $domain = null;
    private $username = null;
    private $password = null;
    private $u2f = null;
    private $u2fChallenge = null;
    private $otpChallenge = null;
    private $rememberme = null;
	private $show_openotp_challenge = false;
	
	// Plugin ready?
    protected $ready = false;

    // Parsed settings
    private $settings = null;

    // Interface keys
    protected $settings_page = 'openotp';
    protected $users_page = 'openotp-user';

    // Data storage keys
    protected $settings_key = 'openotp';
    protected $users_key = 'openotp_user';

    // Settings field placeholders
    protected $settings_fields = array();

    protected $settings_field_defaults = array(
        'label'    => null,
        'type'     => 'text',
        'sanitizer' => 'sanitize_text_field',
        'section'  => 'default',
        'class'    => null,
    );

    // Default OpenOTP data
    protected $user_defaults = array(
        'enable_openotp' => 'false',
    );

    /**
     * Singleton implementation
     */
    public static function instance() {
        if( ! is_a( self::$__instance, 'OpenOTP_auth' ) ) {
            self::$__instance = new OpenOTP_auth;
            self::$__instance->setup();
        }
        return self::$__instance;
    }

    private function __construct() {}

    /**************************************************
     * START WORDPRESS METHODS
     **************************************************/

    /**
     * Plugin setup
     */
    private function setup() {
        require( 'openotp.class.php' );

        $this->register_settings_fields();
        $this->prepare_api();

        // Plugin settings
        add_action( 'admin_init', array( $this, 'action_admin_init' ) );
        add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

        add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 2 );
		
		
        // Anything other than plugin configuration belongs in here.
        if ( $this->ready ) {
            // User settings
            add_action( 'show_user_profile', array( $this, 'action_show_user_profile' ) ); // Show on own profile
            add_action( 'edit_user_profile', array( $this, 'action_show_user_profile' ) ); // Show fields on another profile

            add_action( 'personal_options_update', array( $this, 'action_edit_user_profile_update' ) ); // Save custom fields of own profile
            add_action( 'edit_user_profile_update', array( $this, 'action_edit_user_profile_update' ) ); // Save custom fields of another profile

            // Authentication
            add_filter( 'authenticate', array( $this, 'authenticate_user' ), 99999, 3 );

            // Display notices
            add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );

			// add Login Form Overlay
			add_action('login_enqueue_scripts', array( $this, 'openotp_AddJSToLogin' ));
			
        }
    }


    /**
     * Add settings fields for main plugin page
     */
    protected function register_settings_fields() {
        $this->settings_fields = array(
            array(
                'name'      => 'openotp_server_url',
                'label'     => __( 'OpenOTP server URL', 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_client_id',
                'label'     => __( 'OpenOTP client id', 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_default_domain',
                'label'     => __( 'Default Domain', 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_user_settings',
                'label'     => __( 'User Settings', 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_proxy_host',
                'label'     => __( "Proxy Host", 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_proxy_port',
                'label'     => __( "Proxy Port", 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_proxy_login',
                'label'     => __( "Proxy Login", 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            ),
            array(
                'name'      => 'openotp_proxy_password',
                'label'     => __( "Proxy Password", 'openotp' ),
                'type'      => 'text',
                'sanitizer' => null,
            )		
			
        );
    }

    protected function prepare_api() {
		$params = array( 
					"openotp_server_url" => $this->get_setting( 'openotp_server_url' ),
					"openotp_client_id" => $this->get_setting( 'openotp_client_id' ),
					"openotp_default_domain" => $this->get_setting( 'openotp_default_domain' ),
					"openotp_user_settings" => $this->get_setting( 'openotp_user_settings' ),
					"openotp_proxy_host" => $this->get_setting( 'openotp_proxy_host' ),
					"openotp_proxy_port" => $this->get_setting( 'openotp_proxy_port' ),
					"openotp_proxy_login" => $this->get_setting( 'openotp_proxy_login' ),
					"openotp_proxy_password" => $this->get_setting( 'openotp_proxy_password' )
					);

        if ( $this->get_setting( 'openotp_server_url' ) ) {

			$path = plugin_dir_path( __FILE__ );
			$this->openotp_auth = new openotp( $this, $params, $path );
			
			// check OpenOTP WSDL file
			if (!$this->openotp_auth->checkFile('openotp.wsdl','Could not load OpenOTP WSDL file')){
            	error_log("Could not find OpenOTP WSDL file.");
			}
			// Check SOAP extension is loaded
			if (!$this->openotp_auth->checkSOAPext()){
				error_log('Your PHP installation is missing the SOAP extension');
			}

            $this->ready = true;
			
        }
    }

    /**
     * Register plugin's setting and validation callback
     */
    public function action_admin_init() {
        register_setting( $this->settings_page, $this->settings_key, array( $this, 'validate_plugin_settings' ) );
        register_setting( $this->settings_page, 'openotp_roles', array( $this, 'select_only_system_roles' ) );
    }

    /**
     * Register plugin settings page and page's sections
     */
    public function action_admin_menu() {
        $show_settings = false;
        $can_admin_network = is_plugin_active_for_network( 'openotp-authentication/openotp.php' ) && current_user_can( 'network_admin' );

        if ( $can_admin_network || current_user_can( 'edit_plugins' ) ) {
            $show_settings = true;
        }

        if ( $show_settings ) {
            add_options_page( $this->name, 'OpenOTP', 'manage_options', $this->settings_page, array( $this, 'plugin_settings_page' ) );
            add_settings_section( 'default', '', array( $this, 'register_settings_page_sections' ), $this->settings_page );
        }
    }

    /**
     * Add settings link to plugin row actions
     */
    public function filter_plugin_action_links( $links, $plugin_file ) {
        if ( strpos( $plugin_file, pathinfo( __FILE__, PATHINFO_FILENAME ) ) !== false ) {
            $links['settings'] = '<a href="options-general.php?page=' . $this->settings_page . '">' . __( 'Settings', 'openotp' ) . '</a>';
        }

        return $links;
    }

    /**
    * Display an admin notice when the server doesn't installed a cert bundle.
    */
    public function action_admin_notices($message) {
        $response = $this->openotp_auth->message;
        if ( is_string( $response ) ) {
            ?><div id="message" class="error"><p><strong>Error: </strong><?php echo $response; ?></p></div><?php
        }

    }

    /**
     * Retrieve plugin setting
     */
    public function get_setting( $key ) {
        $value = false;

        if ( is_null( $this->settings ) || ! is_array( $this->settings ) ) {
            $this->settings = get_option( $this->settings_key );
            $this->settings = wp_parse_args( $this->settings, array(
                'openotp_server_url'  => '',
                'openotp_client_id'  => '',
                'openotp_default_domain'  => '',
                'openotp_user_settings'  => '',
                'openotp_proxy_host'  => '',
                'openotp_proxy_port'  => '',
                'openotp_proxy_login'  => '',
                'openotp_proxy_password'  => ''
            ) );
        }

        if ( isset( $this->settings[ $key ] ) ) {
            $value = $this->settings[ $key ];
        }

        return $value;
    }


    /**************************************************
     * START OPENOTP PLUGIN METHODS
     **************************************************/

    /**
    * Check if Two factor authentication is available for role
    */
    public function available_openotp_for_role( $user ) {
        global $wp_roles;
        $wordpress_roles = $wp_roles->get_names();
        $openotp_roles = get_option( 'openotp_roles', $wordpress_roles );

        foreach ( $user->roles as $role ) {
            if ( array_key_exists( $role, $openotp_roles ) ) {
                return true;
            }
        }
        return false;
    }


    /**
     * Populate settings page's sections
     */
    public function register_settings_page_sections() {
        add_settings_field( 'openotp_roles', __( 'Allow OpenOTP for the following roles', 'openotp' ), array( $this, 'add_settings_for_roles' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_server_url', __( 'OpenOTP server URL', 'openotp' ), array( $this, 'add_settings_openotp_server_url' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_client_id', __( 'OpenOTP client id', 'openotp' ), array( $this, 'add_settings_openotp_client_id' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_default_domain', __( 'Default domain', 'openotp' ), array( $this, 'add_settings_openotp_default_domain' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_user_settings', __( 'User settings', 'openotp' ), array( $this, 'add_settings_openotp_user_settings' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_proxy_host', __( 'Proxy Host', 'openotp' ), array( $this, 'add_settings_openotp_proxy_host' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_proxy_port', __( 'Proxy Port', 'openotp' ), array( $this, 'add_settings_openotp_proxy_port' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_proxy_login', __( 'Proxy Login', 'openotp' ), array( $this, 'add_settings_openotp_proxy_login' ), $this->settings_page, 'default' );
        add_settings_field( 'openotp_proxy_password', __( 'Proxy Password', 'openotp' ), array( $this, 'add_settings_openotp_proxy_password' ), $this->settings_page, 'default' );
    }

    /**
     * Render settings 
     */
    public function add_settings_openotp_server_url() {
        $value = $this->get_setting( 'openotp_server_url' );
		$style = $value == NULL ? "style='border:1px solid red;'" : "";
        ?>
            <input <?php echo $style; ?> placeholder="http://myserver:8080/openotp/" type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_server_url]"
              class="regular-text" id="field-openotp_server_url" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }
    public function add_settings_openotp_client_id() {
        $value = $this->get_setting( 'openotp_client_id' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_client_id]"
              class="regular-text" id="field-openotp_client_id" value="<?php echo $value != NULL ? esc_attr( $value ) : "Wordpress"; ?>" />
        <?php
    }
    public function add_settings_openotp_default_domain() {
        $value = $this->get_setting( 'openotp_default_domain' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_default_domain]"
              class="regular-text" id="field-openotp_default_domain" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }
    public function add_settings_openotp_user_settings() {
        $value = $this->get_setting( 'openotp_user_settings' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_user_settings]"
              class="regular-text" id="field-openotp_user_settingsl" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }
    public function add_settings_openotp_proxy_host() {
        $value = $this->get_setting( 'openotp_proxy_host' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_proxy_host]"
              class="regular-text" id="field-openotp_proxy_host" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }
    public function add_settings_openotp_proxy_port() {
        $value = $this->get_setting( 'openotp_proxy_port' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_proxy_port]"
              class="regular-text" id="field-openotp_proxy_port" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }
    public function add_settings_openotp_proxy_login() {
        $value = $this->get_setting( 'openotp_proxy_login' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_proxy_login]"
              class="regular-text" id="field-openotp_proxy_login" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }
    public function add_settings_openotp_proxy_password() {
        $value = $this->get_setting( 'openotp_proxy_password' );
        ?>
            <input type="text" name="<?php echo esc_attr( $this->settings_key ); ?>[openotp_proxy_password]"
              class="regular-text" id="field-openotp_proxy_password" value="<?php echo esc_attr( $value ); ?>" />
        <?php
    }

    /**
    * Render settings roles
    */
    public function add_settings_for_roles() {
        global $wp_roles;

        $roles = $wp_roles->get_names();
        $roles_to_list = array();

        foreach ( $roles as $key => $role ) {
            $roles_to_list[before_last_bar( $key )] = before_last_bar( $role );
        }

        $selected = get_option( 'openotp_roles', $roles_to_list );

        foreach ( $wp_roles->get_names() as $role ) {
            $checked = in_array( before_last_bar( $role ), $selected );
            $role_name = before_last_bar( $role );
            // html block
            ?>
                <input style="vertical-align:baseline;"  name='openotp_roles[<?php echo esc_attr( strtolower( $role_name ) ); ?>]' type='checkbox'
                  value='<?php echo esc_attr( $role_name ); ?>'<?php if ( $checked ) echo 'checked="checked"'; ?> />&nbsp;<?php echo esc_attr( $role_name ); ?></br>
            <?php
        }
    }


    /**
     * Render settings page
     */

    public function plugin_settings_page() {
        $plugin_name = esc_html( get_admin_page_title() );
        ?>
            <div class="wrap">
              <?php screen_icon(); ?>
              <h2><?php echo esc_attr( $plugin_name ); ?></h2>

              <?php if ( $this->ready ) :
                  //$details = $this->api->application_details();
              ?>
              <p><?php _e( 'Enter your OpenOTP server settings in the fields below. You can select which users can enable OpenOTP by their WordPress role. Users can then enable OpenOTP on their individual accounts by visting their user profile pages.', 'openotp' ); ?></p>
              <p><?php _e( 'You can also enable and force Two-Factor Authentication by editing the user on the Users page, and then clicking "Enable OpenOTP" button on their settings.', 'openotp' ); ?></p>

              <?php else :  ?>
                  <p><?php printf( __( 'To use the OpenOTP plugin on Wordpress, you must doownload and configure OpenOTP server <a href="%1$s"><strong>%1$s</strong></a>.', 'opneotp' ), 'http://www.rcdevs.com' ); ?></p>
                  <p><?php _e( "Once you've configured your server, enter at least your server url in the fields below.", 'openotp' ); ?></p>
                  <p><?php printf( __( 'Until your server URL is entered, the %s plugin cannot function.', 'openotp' ), $plugin_name ); ?></p>
              <?php endif; ?>

              <form action="options.php" method="post">
                  <?php settings_fields( $this->settings_page ); ?>
                  <?php do_settings_sections( $this->settings_page ); ?>

                  <p class="submit">
                      <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' );?>" class="button-primary">
                  </p>
              </form>
            </div>
        <?php
    }

    /**
     * Validate plugin settings
     */
    public function validate_plugin_settings( $settings ) {
        check_admin_referer( $this->settings_page . '-options' );

        $settings_validated = array();

        foreach ( $this->settings_fields as $field ) {
            $field = wp_parse_args( $field, $this->settings_field_defaults );

            if ( ! isset( $settings[ $field['name'] ] ) ) {
                continue;
            }
            if ( $field['type'] === "text" && $field['sanitizer'] === 'alphanumeric' ) {
                $value = preg_replace( '#[^a-z0-9]#i', '', $settings[ $field['name' ] ] );
            } else {
                $value = sanitize_text_field( $settings[ $field['name'] ] );
				
            }
            if ( isset( $value ) && ! empty( $value ) ) {
                $settings_validated[ $field['name'] ] = $value;
            }
        }

        return $settings_validated;
    }

    /**
    * Select the system roles present in $roles
    */
    public function select_only_system_roles( $roles ) {
        if ( !is_array( $roles ) || empty( $roles ) ) {
            return array();
        }

        global $wp_roles;
        $system_roles = $wp_roles->get_names();

        foreach ( $roles as $role ) {
            if ( !in_array( $roles, $system_roles ) ) {
                unset( $roles[$role] );
            }
        }
        return $roles;
    }

    /**
    * USER SETTINGS PAGES
    */

    public function action_show_user_profile( $user ) {
        $meta = $this->get_openotp_data( $user->ID );
		
		//if ( $this->available_openotp_for_role( $user ) ) {
            echo $this->action_edit_user_profile( $user);
        //}
    }
	
    public function action_edit_user_profile( $user ) {
        if ( !current_user_can( 'create_users' ) ) {
            return;
        }
        ?>
            <h3>OpenOTP Two-factor Authentication</h3>

            <table class="form-table">
                <?php
                    $openotp_data = $this->get_openotp_data( $user->ID );
                    $this->render_admin_form_enable_openotp( $this->users_key, $openotp_data );
                ?>
            </table>
        <?php
    }
	
    public function action_edit_user_profile_update( $user_id ) {
	
        $is_disabling_user = false;
        if ( isset( $_POST["_{$this->users_key}_wpnonce"] ) && wp_verify_nonce( $_POST["_{$this->users_key}_wpnonce"], $this->users_key . '_disable' )) {
            $is_disabling_user = true;
        }
        if ( $is_disabling_user && !isset($_POST[ $this->users_key ]) ) {
            $this->clear_openotp_data( $user_id );
            return;
        }

        $openotp_user_info = $_POST['openotp_user'];
						
		if ( !empty( $openotp_user_info['force_enable_openotp'] ) && $openotp_user_info['force_enable_openotp'] == 'true' )
        {
            update_user_meta( $user_id, $this->users_key,  array( 'enable_openotp' => 'true' ) );
        }
        elseif ( empty( $openotp_user_info['force_enable_openotp'] ) )
        {
            update_user_meta( $user_id, $this->users_key,  array( 'enable_openotp' => 'false' ) );
        }		
		
		
    }

    /**
     * USER INFORMATION FUNCTIONS
     */

    protected function clear_openotp_data( $user_id ) {
        delete_user_meta( $user_id, $this->users_key );
    }

    protected function get_openotp_data( $user_id ) {
        if ( ! $user_id ) {
            return $this->user_defaults;
        }

        $data = get_user_meta( $user_id, $this->users_key, true );
        if ( ! is_array( $data ) ) {
            $data = array();
        }

        return $data;
    }

    protected function is_openotp_enable_for_user( $user_id ) {
        $data = $this->get_openotp_data( $user_id );

        if ( $data['enable_openotp'] == 'true' ) {
            return true;
        }

        return false;
    }

	public function render_admin_form_enable_openotp( $users_key, $openotp_data ) { ?>
	  <tr>
		  <th><?php _e( 'OpenOTP authentication', 'openotp' ); ?></th>
		  <td> <?php wp_nonce_field( $users_key . '_edit', "_{$users_key}_wpnonce" ); ?>
			  <label for="force-enable">
				  <input style="vertical-align:baseline;" name="<?php echo esc_attr( $users_key ); ?>[force_enable_openotp]" type="checkbox" value="true" <?php if ($openotp_data['enable_openotp'] == 'true') echo 'checked="checked"'; ?> />
				  <?php _e( 'Enable OpenOTP Two-Factor Authentication.', 'openotp' ); ?>
			  </label>
		  </td>
	  </tr>
	<?php }

	
	public function js_inside_body() {
		$c =  "<script src=\"chrome-extension://pfboblefjcgdjicmnffhdgionmgcdmne/u2f-api.js\" type=\"text/javascript\"></script>";
		echo $c;
	}
		
	public function openotp_AddJSToLogin(){
		if($this->show_openotp_challenge){
			$this->js_inside_body();
			//wp_enqueue_script( 'u2f_api', '//chrome-extension://pfboblefjcgdjicmnffhdgionmgcdmne/u2f-api.js', array(), '3', true);
			
			wp_enqueue_script( 'openotp_overlay', plugin_dir_url( __FILE__ ) . 'openotp.js',null,'',true);
			wp_localize_script( 'openotp_overlay', 'otp_settings', array(
				'openotp_message' => $this->message,
				'openotp_username' => $this->username,
				'openotp_session' => $this->state,
				'openotp_timeout' => $this->timeout,
				'openotp_ldappw' => $this->password,
				'openotp_u2fChallenge' => $this->u2fChallenge,
				'openotp_otpChallenge' => $this->otpChallenge,
				'openotp_path' => plugin_dir_url( __FILE__ ),
				'openotp_domain' => $this->domain,
				'openotp_rememberme' => $this->rememberme			
			));
		}
	}

    /**
     * AUTHENTICATION CHANGES
     */

    public function authenticate_user( $user = '', $username = '', $password = '' ) {
		// Form not send
				
       if( !isset( $_POST['wp-submit']) && !isset( $_POST['form_send']) ) { 
            return $user;
        }

		$this->username = isset($_POST['openotp_username']) && $_POST['openotp_username'] != NULL ? $_POST['openotp_username'] : $username;
		$this->password = isset($_POST['openotp_password']) && $_POST['openotp_password'] != NULL ? $_POST['openotp_password'] : $password;
		$this->u2f = isset($_POST['openotp_u2f']) ? stripslashes($_POST['openotp_u2f']) : "";
		
		$state = isset($_POST['openotp_state']) ? $_POST['openotp_state'] : "";
		$this->rememberme = isset($_POST['rememberme']) ? $_POST['rememberme'] : "";

		// forbid blank username & passwords
		if (empty($this->username) ) {
            return new WP_Error( 'authentication_failed', __( '<strong>ERROR: missing credentials</strong>' ) );
		}
				
		$t_domain = $this->openotp_auth->getDomain($this->username);
		if (is_array($t_domain)){
			$username = $t_domain['username'];
			$this->username = $t_domain['username'];
			$this->domain = $t_domain['domain'];
		}elseif(isset($_POST['openotp_domain']) && $_POST['openotp_domain'] != NULL) $this->domain = $_POST['openotp_domain'];
		else $this->domain = $t_domain;

		// OpenOTP not enable for user role 
        $user = get_user_by( 'login', $this->username );
		
		if(!is_object( $user)) return new WP_Error( 'authentication_failed', __( '<strong>invalid Username or Password</strong>' ) );
		
		if( !$this->is_openotp_enable_for_user($user->ID) && !$this->available_openotp_for_role( $user ) ){
			if (empty($this->password) ) {
				return new WP_Error( 'authentication_failed', __( '<strong>ERROR: missing password</strong>' ) );
			}
			// from here we take care of the authentication.
			remove_action( 'authenticate', 'wp_authenticate_username_password', 20 );
			
			$ret = wp_authenticate_username_password( null, $this->username, $this->password );

			if ( is_wp_error( $ret ) ) {
				return $ret; // there was an error
			}
			$user = $ret;
			wp_set_auth_cookie( $user->ID );
			
			return $user;
		}
				
		if ($state != NULL) {
			// OpenOTP Challenge
			//echo $this->u2f; die;
			$resp = $this->openotp_auth->openOTPChallenge($this->username, $this->domain, $state, $this->password, $this->u2f);
		} else {
			// OpenOTP Login
			$resp = $this->openotp_auth->openOTPSimpleLogin($this->username, $this->domain, utf8_encode($this->password));
		}

		if (!$resp || !isset($resp['code'])) {
			error_log("Invalid OpenOTP response code ".$resp['code']." for user ".$this->username);
			return new WP_Error( 'authentication_failed', __( $this->message ) );				
		}

		switch ($resp['code']) {
			 case 0:
				if ($resp['message']) $this->message = $resp['message'];
				else $this->message = "<strong>ERROR: missing credentials</strong>";
	            return new WP_Error( 'authentication_failed', __( $this->message ) );
				break;
			 case 1:
	            $remember_me = ($this->rememberme == 'forever') ? true : false;
                wp_set_auth_cookie( $user->ID, $remember_me );
				wp_safe_redirect( admin_url() );
				break;
			 case 2:
				// TODO : hide the default wordpress login failure message 
				$this->message = $resp['message'];
				$this->state = $resp['session'];
				$this->timeout = $resp['timeout'];

				$resp['domain'] = $this->domain;
				$this->u2fChallenge = $resp['u2fChallenge'];
				$this->otpChallenge = $resp['otpChallenge'];
				$this->show_openotp_challenge = true;
				break;
			 default:
				error_log("Invalid OpenOTP response code ".$resp['code']." for user ".$this->username);
	            return new WP_Error( 'authentication_failed', __( $resp['message'] ) );				
				break;
		}
		
    }
}

OpenOTP_auth::instance();
