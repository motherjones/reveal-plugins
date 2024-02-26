<?php
/**
* Plugin Name:  Jetpack Filter: Remove Login Form
* Plugin URI: https://www.jetpack.com/
* Description: Implements a Jetpack filter that completely removes the default login form, and forces users to log in with WordPress.com with two-factor authentication enabled.
* Version: 1.0
* Author: Automattic
* Author URI: https://automattic.com/
**/

// Completely disables and hides the default login form, and forces users to log in via WordPress.com with 2FA

add_filter( 'jetpack_remove_login_form', '__return_true' );

