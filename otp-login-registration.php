<?php
/*
Plugin Name: OTP Login & Registration
Description: A WordPress plugin for OTP-based login and registration using Fast2SMS API.
Version: 1.3
Author: Piedev Tech Solutions
*/

// Define constants
define('OTP_LOGIN_REGISTRATION_VERSION', '1.3');
define('OTP_LOGIN_REGISTRATION_PLUGIN_SLUG', 'otp-login-registration');

// Activation hook
register_activation_hook(__FILE__, 'otp_login_registration_activate');

// Deactivation hook
register_deactivation_hook(__FILE__, 'otp_login_registration_deactivate');

// Function to run upon plugin activation
function otp_login_registration_activate() {
    // Add activation code here
}

// Function to run upon plugin deactivation
function otp_login_registration_deactivate() {
    // Add deactivation code here
}

// Enqueue necessary scripts and styles
function otp_login_registration_enqueue_scripts() {
    // Enqueue scripts and styles here
    wp_enqueue_style('otp-login-registration-style', plugin_dir_url(__FILE__) . 'style.css', array(), OTP_LOGIN_REGISTRATION_VERSION);
}
add_action('wp_enqueue_scripts', 'otp_login_registration_enqueue_scripts');

// Add admin menu
function otp_login_registration_admin_menu() {
    add_menu_page(
        'OTP Login & Registration Settings',
        'OTP Settings',
        'manage_options',
        'otp-login-registration-settings',
        'otp_login_registration_settings_page'
    );
}
add_action('admin_menu', 'otp_login_registration_admin_menu');

/// Settings page callback function
function otp_login_registration_settings_page() {
    ?>
    <div class="wrap">
        <h2>OTP Login & Registration Settings</h2>
        <p>Current Plugin Version: <?php echo OTP_LOGIN_REGISTRATION_VERSION; ?></p>
        <form method="post" action="options.php">
            <?php settings_fields('otp_login_registration_settings_group'); ?>
            <?php do_settings_sections('otp_login_registration_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Fast2SMS API Key:</th>
                    <td><input type="text" name="otp_login_registration_api_key" value="<?php echo esc_attr(get_option('otp_login_registration_api_key')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <form method="post" action="">
            <input type="hidden" name="check_update_nonce" value="<?php echo wp_create_nonce('check_update_nonce'); ?>">
            <button type="submit" name="check_update">Check for Updates</button>
        </form>
    </div>
    <?php
}

// Handle check update action
add_action('admin_init', 'otp_login_registration_check_update_action');
function otp_login_registration_check_update_action() {
    if (isset($_POST['check_update']) && isset($_POST['check_update_nonce']) && wp_verify_nonce($_POST['check_update_nonce'], 'check_update_nonce')) {
        $plugin_slug = OTP_LOGIN_REGISTRATION_PLUGIN_SLUG;
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . $plugin_slug . '.php');

        // Fetch plugin info from GitHub
        $url = 'https://api.github.com/repos/baradatipu/WP-OTP-Login-Fast2SMS-API/releases/latest';
        $response = wp_remote_get($url);
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (!empty($data['tag_name']) && version_compare($plugin_data['Version'], $data['tag_name'], '<')) {
                echo '<div class="updated"><p>New version available: ' . $data['tag_name'] . '</p></div>';
            } else {
                echo '<div class="updated"><p>You have the latest version.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Error checking for updates. Please try again later.</p></div>';
        }
    }
}


// Register and initialize settings
function otp_login_registration_register_settings() {
    register_setting('otp_login_registration_settings_group', 'otp_login_registration_api_key');
}
add_action('admin_init', 'otp_login_registration_register_settings');

// Shortcode for OTP login form
function otp_login_form_shortcode() {
    ob_start();
    
    if (is_user_logged_in()) {
        echo 'You are already logged in.';
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_login'])) {
        $mobile_number = $_POST['mobile_number'];
        $otp = mt_rand(100000, 999999); // Generate a 6-digit OTP
        
        send_otp_via_sms($mobile_number, $otp);

        $_SESSION['otp_login_mobile_number'] = $mobile_number;
        $_SESSION['otp_login_otp'] = $otp;

        echo '<div class="otp-form">
                <h2>OTP Login</h2>
                <form method="post" action="">
                    <input type="text" name="otp" placeholder="Enter OTP">
                    <button type="submit" name="otp_login_submit">Submit OTP</button>
                </form>
            </div>';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_login_submit'])) {
        $otp_entered = $_POST['otp'];
        $mobile_number = $_SESSION['otp_login_mobile_number'];
        $otp_sent = $_SESSION['otp_login_otp'];

        if ($otp_entered == $otp_sent) {
            echo 'OTP verified. You are now logged in.';
            // Log the user in
            $user = get_user_by('phone', $mobile_number);
            if ($user) {
                wp_set_current_user($user->ID, $user->user_login);
                wp_set_auth_cookie($user->ID);
                do_action('wp_login', $user->user_login);
            } else {
                echo 'No user found with this mobile number.';
            }
        } else {
            echo 'Invalid OTP. Please try again.';
        }
    } else {
        echo '<div class="otp-form">
                <h2>OTP Login</h2>
                <form method="post" action="">
                    <input type="tel" name="mobile_number" placeholder="Enter Mobile Number">
                    <button type="submit" name="otp_login">Send OTP</button>
                </form>
            </div>';
    }

    return ob_get_clean();
}
add_shortcode('otp_login_form', 'otp_login_form_shortcode');

// Shortcode for OTP registration form
function otp_registration_form_shortcode() {
    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_register'])) {
        $username = $_POST['username'];
        $mobile_number = $_POST['mobile_number'];
        $otp = mt_rand(100000, 999999); // Generate a 6-digit OTP

        // Check if the mobile number is already registered
        $user_exists = get_user_by('phone', $mobile_number);
        if ($user_exists) {
            echo 'Mobile number already registered.';
            return;
        }

        send_otp_via_sms($mobile_number, $otp);

        $_SESSION['otp_register_mobile_number'] = $mobile_number;
        $_SESSION['otp_register_username'] = $username;
        $_SESSION['otp_register_otp'] = $otp;

        echo '<div class="otp-form">
                <h2>OTP Registration</h2>
                <form method="post" action="">
                    <input type="text" name="otp" placeholder="Enter OTP">
                    <button type="submit" name="otp_register_submit">Submit OTP</button>
                </form>
            </div>';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp_register_submit'])) {
        $otp_entered = $_POST['otp'];
        $mobile_number = $_SESSION['otp_register_mobile_number'];
        $username = $_SESSION['otp_register_username'];
        $otp_sent = $_SESSION['otp_register_otp'];

        if ($otp_entered == $otp_sent) {
            // Register the user
            $userdata = array(
                'user_login'  =>  $username,
                'user_phone'  =>  $mobile_number,
                'user_pass'   =>  NULL,
            );
            $user_id = wp_insert_user($userdata);
            if (!is_wp_error($user_id)) {
                echo 'User registered successfully.';
            } else {
                echo 'Error registering user.';
            }
        } else {
            echo 'Invalid OTP. Please try again.';
        }
    } else {
        echo '<div class="otp-form">
                <h2>OTP Registration</h2>
                <form method="post" action="">
                    <input type="text" name="username" placeholder="Enter Username">
                    <input type="tel" name="mobile_number" placeholder="Enter Mobile Number">
                    <button type="submit" name="otp_register">Send OTP</button>
                </form>
            </div>';
    }

    return ob_get_clean();
}
add_shortcode('otp_registration_form', 'otp_registration_form_shortcode');

// Function to send OTP via SMS
function send_otp_via_sms($mobile_number, $otp) {
    $api_key = get_option('otp_login_registration_api_key');
    if (empty($api_key)) {
        echo 'API key not set. Please set the API key in plugin settings.';
        return;
    }

    $api_url = 'https://www.fast2sms.com/dev/bulkV2';
    $url = $api_url . '?authorization=' . $api_key . '&route=otp&variables_values=' . $otp . '&flash=0&numbers=' . $mobile_number;

    // Make HTTP request to send OTP via SMS
    $response = wp_remote_get($url);

    // Handle response
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // SMS sent successfully
    } else {
        // Error sending SMS
    }
}

// Function to verify OTP
function verify_otp($mobile_number, $otp) {
    // Add logic to verify OTP
}

// Automatic updates
add_filter('plugins_api', 'otp_login_registration_plugin_api', 20, 3);
function otp_login_registration_plugin_api($res, $action, $args) {
    if ('plugin_information' !== $action || !isset($args->slug) || OTP_LOGIN_REGISTRATION_PLUGIN_SLUG !== $args->slug) {
        return false;
    }

    // Get the current version
    $plugin_data = get_plugin_data(__FILE__);
    $current_version = $plugin_data['Version'];

    // Fetch plugin info from GitHub
    $url = 'https://api.github.com/repos/baradatipu/WP-OTP-Login-Fast2SMS-API/releases/latest';
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!empty($data['tag_name']) && version_compare($current_version, $data['tag_name'], '<')) {
        $res = new stdClass();
        $res->slug = OTP_LOGIN_REGISTRATION_PLUGIN_SLUG;
        $res->plugin_name = 'OTP Login & Registration';
        $res->new_version = $data['tag_name'];
        $res->url = 'https://github.com/baradatipu/WP-OTP-Login-Fast2SMS-API';
        $res->package = $data['zipball_url'];
        return $res;
    }

    return $res;
}
