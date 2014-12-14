CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Usage
 * For More Information

 INTRODUCTION
 ------------

Guzzle is the PHP framework when it comes to dealing with HTTP requests. It
provides an API to create robust web service clients or HTTP clients of any
kind.

When doing web request as part of the normal user request response cycle, one
want to have a eye on the number and performance of those requests to maintain
acceptable performance of Drupal itself.

As Guzzle uses curl_multi_exec as a wrapper for all http requests, analyzer
tools like New Relic can not provide any insights on the individual requests.
This is where Past Guzzle comes in.

Past Guzzle provides adapters for the Guzzle Log Plugin allowing to log requests
events with the past framework.

 FEATURES
 --------

* Provides an adapter class for the Guzzle Log plugin.
* Provides a helper function to create and get a ready for usage configured
  Guzzle Log plugin singleton instance.
* Logs all requests headers and if present the request body.
* Logs all response headers and the response body.
* Logs statistics about the request.

 REQUIREMENTS
 ------------

"Past Guzzle" is submodule of "Past" and can only work if Past itself is
installed and correctly configured.

This module further relies on composer manager to get access to the
Guzzle library.

 INSTALLATION
 ------------

Installation is a simple as enabling this module. If "Past" was not installed
before hand, a past backend must be installed too. A good start would be the
"Past Database Backend"

 USAGE
 -----

$log_plugin = past_guzzle_plugin();
$client = new \Guzzle\Http\Client('http://example.com');
$client->addSubscriber($log_plugin);
$response = $client->get('api/foo')->send()

 FOR MORE INFORMATION
 --------------------

  * Project Page: http://drupal.org/project/past
  * Issue Queue: http://drupal.org/project/issues/past
