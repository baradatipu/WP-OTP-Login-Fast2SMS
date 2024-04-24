<?php
/*
Plugin Name: FAST2SMS OTP Login & Registration
Description: A WordPress plugin for OTP-based login and registration.
Version: 1.0
Author: Piedev Tech Solutions
*/

// Activation hook
register_activation_hook(__FILE__, 'otp_login_registration_activate');

// Deactivation hook
register_deactivation_hook(__FILE__, 'otp_login_registration_deactivate');

// Uninstallation hook
register_uninstall_hook(__FILE__, 'otp_login_registration_uninstall');

// Function to run upon plugin activation
function otp_login_registration_activate() {
    // Add activation code here
}

// Function to run upon plugin deactivation
function otp_login_registration_deactivate() {
    // Add deactivation code here
}

// Function to run upon plugin uninstallation
function otp_login_registration_uninstall() {
    // Add uninstallation code here
}

// Enqueue necessary scripts and styles
function otp_login_registration_enqueue_scripts() {
    // Enqueue scripts and styles here
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

// Settings page callback function
function otp_login_registration_settings_page() {
    ?>
    <div class="wrap">
        <h2>OTP Login & Registration Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('otp_login_registration_settings_group'); ?>
            <?php do_settings_sections('otp_login_registration_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key:</th>
                    <td><input type="text" name="otp_login_registration_api_key" value="<?php echo esc_attr(get_option('otp_login_registration_api_key')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
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

        echo '<form method="post" action="">
                <input type="text" name="otp" placeholder="Enter OTP">
                <button type="submit" name="otp_login_submit">Submit OTP</button>
            </form>';
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
        echo '<form method="post" action="">
                <input type="tel" name="mobile_number" placeholder="Enter Mobile Number">
                <button type="submit" name="otp_login">Send OTP</button>
            </form>';
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

        echo '<form method="post" action="">
                <input type="text" name="otp" placeholder="Enter OTP">
                <button type="submit" name="otp_register_submit">Submit OTP</button>
            </form>';
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
        echo '<form method="post" action="">
                <input type="text" name="username" placeholder="Enter Username">
                <input type="tel" name="mobile_number" placeholder="Enter Mobile Number">
                <button type="submit" name="otp_register">Send OTP</button>
            </form>';
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
