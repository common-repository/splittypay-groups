=== Plugin Name ===
Contributors: splittypayplugins
Tags: payment, groups, sharing
Requires at least: 4.0.1
Tested up to: 5.7
Requires PHP: 5.6
Stable tag: 1.1.6
License: GPLv3 or later License
URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin allows customers to pay a woocommerce order as a group, splitting payments among different cards.

== Description ==

Group Pay allows customers to split the payment of a woocommerce order among friends. Customers will be redirected
to the Splittypay platform to complete the payment: from the platform customers can: 
*   Add the cards of the group members
*   Send payment invites to the friends that are not with you
*   Monitor the payment in real time

Merchants need to register to the Splittypay service to access the Groups functionality as the plugin requires an API key to work properly.
A merchant, to get an API key, needs to fulfill four simple steps:
*   Connect (or create from scratch) their Stripe account
*   Fill some basic information about the business
*   Upload a logo
*   Customize the cart duration on the Splittypay platform

The Group Pay plugin can work both in sandbox and live mode. Merchants can switch between the environments from the settings panel
(Different API keys are required for the sandbox and live environments).

== Frequently Asked Questions ==

= What happens if a friend is not with me when I have to pay? =

From the Splittypay platform you can send a payment invite through email.

= What happens if a payment invite is not completed? =

The group payment required at least a card to be completed: the missing payments are pre-authorized on the available cards.

= How long does it take to complete a payment? =

The merchant will have the order completed in his dashboard as soon as a card has been correctly pre-authorized.

== Screenshots ==

1. Owner page: the group owner must fulfill the information.
2. Checkout page: the group owner can add his friends credit cards or send them payment invites.
3. Monitor page: everyone with the link to this page can monitor the group payment state.

== Changelog ==

= 1.1.0 =
First plugin commit