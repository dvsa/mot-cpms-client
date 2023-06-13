# CHANGELOG

## develop branch

## v1.25.2 - Wed Apr 27 2016

### Fixes

* Make sure cpms-client does not send an `Authorization` header when requesting a new access token

## v1.25.1 - Thu Apr 7 2016

### Fixes

* Delegate decoding multipart messages to the queue adapter
  - Fixed in `CpmsClient\Client\NotificationsClient`

## v1.25.0 - Tue Apr 5 2016

### New

* Added support for the `entity_version` field in notifications.
* Added support for encrypted Notifications.

## v1.24.2 - Wed Mar 23 2016

### Fixes

* Bumped minimal version of `cpms-notifications` to fix a bug in a third-party dependency

## v1.24.1 - Thu Mar 17 2016

### Other

* Bumped `cpms-notifications` dependency to v3.0.
  - `cpms-notifications-3.0` has no b/c breaks for `cpms-client` users

## v1.24.0 - Fri Jan 22 2016

### New

* Added support for `parent_reference` field in `PaymentNotificationV1` notifications

## v1.23.2 - Fri Jan 8 2016

### Fixes

* Fix typo in `use` statement

### Infrastructure Migration

* Removed composer.phar executable
* Updated composer.json to use AWS git repositories for dependencies

### Testing

* Moved PHPUnit configuration file to root
* Added extra logging options to PHPUnit configuration

## v1.23.1 - Tue Dec 15 2015

### Fixes

* Complete functional testing for the Notifications API
  * `ApiService::acknowledgeNotification()` now works against a real `cpms/payment-service`
* Make the CpmsClient internal error contain something useful
  * `ApiService::returnErrorMessage()` now returns the error message from the exception (if present) to the caller

## v1.23.0 - Thu Dec 10 2015

### New

* Added support for retrieving notifications from CPMS:
  * `NotificationsClient` added
  * `NotificationsClientFactory` added
  * `CpmsNotificationAcknowledgementFailed` exception added
  * example config added to `client-config.global.php.dist`
  * `module.config.php` updated with new factory `cpms\client\notifications`
  * now relies on `cpms/cpms-notifications` v2.x

### Fixes

* Stopped the `ApiService` from accidentally echoing to the screen / output channel when it cannot get an access token for a user.
  * `ApiService::getTokenForScope()` fixed

### Testing

* Added support for testing `cpms-client` against an actual `cpms/payment-service` instance.
