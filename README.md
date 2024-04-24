# OTP Login & Registration WordPress Plugin

The **OTP Login & Registration** plugin provides a simple solution for implementing OTP-based login and registration functionality in WordPress websites. It utilizes the Fast2SMS OTP Route API to send one-time passwords (OTPs) via SMS to users' mobile numbers for verification during login and registration processes.

## Features

- OTP-based login: Users receive an OTP via SMS for verification during login.
- OTP-based registration: Users receive an OTP via SMS for verification during registration.
- Seamless integration: Easily add OTP login and registration forms to any page or post using shortcodes.

## Installation

1. Download the plugin ZIP file from the [GitHub repository](#).
2. Log in to your WordPress admin panel.
3. Navigate to **Plugins** > **Add New**.
4. Click on the **Upload Plugin** button.
5. Choose the ZIP file you downloaded and click **Install Now**.
6. After installation, click **Activate Plugin**.

## Shortcodes

- **OTP Login Form:** `[otp_login_form]` - Display the OTP login form.
- **OTP Registration Form:** `[otp_registration_form]` - Display the OTP registration form.

## Usage

1. Add the desired shortcode(s) to any page or post where you want to display the OTP login or registration form.
2. Users will be prompted to enter their mobile numbers.
3. Upon submission, users will receive an OTP via SMS.
4. Users enter the OTP to verify their identity and proceed with the login or registration process.

## Configuration

To use the plugin, you need to obtain an API key from Fast2SMS. Follow these steps to configure the plugin:

1. Sign up or log in to your Fast2SMS account.
2. Obtain your API key from the Fast2SMS dashboard.
3. Navigate to **Settings** > **OTP Login & Registration** in your WordPress admin panel.
4. Enter your Fast2SMS API key in the provided field.
5. Save the changes.

## Requirements

- WordPress 4.7 or higher.
- PHP 5.6 or higher.
- Fast2SMS account with OTP Route API access.

## Notes

- This plugin utilizes the Fast2SMS OTP Route API for sending OTPs via SMS. Make sure you have sufficient SMS credits and comply with Fast2SMS's terms of service.
- Ensure that users provide valid mobile numbers for OTP verification.
- Customization options, such as styling the OTP forms, can be implemented by modifying the plugin's code or using additional CSS.

## Credits

- This plugin utilizes the [Fast2SMS](https://www.fast2sms.com/) OTP Route API for OTP delivery.

## License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
