=== OpenOTP Two-Factor Authentication ===
Contributors: RCDevs
Tags: strong authentication, two-factor, authenticator, login, username, password, rcdevs, security
Donate link: http://rcdevs.com/
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OpenOTP plugin Enable two-factor authentication for your admins and/or users

== Description ==

It is versatile, device-independent and based on opened security standards. 
OpenOTP provides fine-grained user identity and access management (IAM), one-time passwords authentication technologies (OTP) and extensive authentication policies for your AD / LDAP users. 
It is enterprise-ready with strong support for high-availability, load-balancing, multi-tenancy, cloud-readiness, geolocalization, delegated administration and much more.

== Installation ==

Integrating OpenOTP authentication with WordPress is easy. Follow these quick installation steps:

1. Upload `openotp-authentification` to the `/wp-content/plugins/` directory

2. Activate the plugin through the 'Plugins' menu in WordPress

3.If your PHP installation does not have the soap extension, install the php-soap package for your Linux distribution.
  With RedHat, do it with 'yum install php-soap'.

4.Edit the Server URL field and change the OpenOTP server URL to your OpenOTP server URL. 
  Note: You can get your server URL in WebADM, under the Application menu. 

== Frequently Asked Questions ==

= How do I get started with OpenOTP? =

Before installing the plugin, you'll need to download and configure OpenOTP server [http://www.rcdevs.com](http://www.rcdevs.com).

= Is OpenOTP's two-factor service really free? =

Yes, OpenOTP is free up to 35 users, for more details please contact us.

== Screenshots ==

1. OpenOTP administration page with per role activation settings. 

2. The OpenOTP plugin displays an overlay on Challenge-Response session.

== Changelog ==

= 1.1.0 =
Initial release!
- The plugin displays an overlay on Challenge-Response session
- Global, per role and per user settings configuration
- The plugin will transparently support any OpenOTP Login Mode including:
	> LDAP only
	> OTP only
	> LDAP+OTP with user challenge-response

== Upgrade Notice ==

= 1.1.0 =
Initial release!
* The plugin displays an overlay on Challenge-Response session
* Global and per role settings configuration
* The plugin will transparently support any OpenOTP Login Mode including:
	> LDAP only
	> OTP only
	> LDAP+OTP with user challenge-response
