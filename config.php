<?php
/**
 * SMTP Configuration for ClubCash
 * 
 * Fill in your SMTP server details below
 */

// SMTP Server Settings
define('SMTP_HOST', 'smtp.example.com');        // Your SMTP server
define('SMTP_PORT', 587);                        // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USER', 'your-email@example.com');   // SMTP username
define('SMTP_PASS', 'your-password');            // SMTP password

// Email Settings
define('SMTP_FROM_EMAIL', 'your-email@example.com');  // From email address
define('SMTP_FROM_NAME', 'ClubCash');                  // From name
define('REPLY_TO_EMAIL', 'your-email@example.com');    // Reply-to email address
