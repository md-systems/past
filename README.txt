CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * For More Information

 INTRODUCTION
 ------------

 Past is an extended logging framework, designed to log and later analyze
 complex data structures to a pluggable backend.

 The typical use case for this are logging communication between one or multiple
 services, where data is received, processed and forwarded.

 A single log message is called an event, which can have multiple attached
 arguments, each argument can be a scalar, an array, an object or an exception.
 Complex data structures like arrays and objects are stored separately so that
 they can be searched and displayed in a readable way, those rows are called
 "(event) data".


 FEATURES
 --------

 * Procedural and object oriented interface for logging events
 * Attach any number of arguments to a single event
 * Pluggable backends. The current default backend is a database/entity based
   backend, a simpletest backend is additionally provided that allows to display
   events as debug output
 * Each element in an array or object is stored separately to be search and filterable
 * Views and drush integration for the database backend
 * Expiration of old entries

 REQUIREMENTS
 ------------

 Past was built for D7. There will be no back port.

 INSTALLATION
 ------------

 Enable the "Past" and "Past Database Backend" modules, unless you want to use
 a different backend.

 USAGE
 -----

 See the past_event_create() and past_event_save() functions in past.module for
 an entry point to the API.

 The default, views based log overview page is at Administration > Reports >
 Past.

 OPTIONAL USAGE
 --------------

 To add optional integration with Past in a situation where you do not want
 a hard dependency (e.g. a contrib project), you can add the following wrapper
 function to your project/module.

 The downside of this approach is that it is not possible to use the object
 oriented interface which is more flexible and easier to use when logging
 multiple arguments.

/**
 * Wrapper for past_event_save to avoid dependency to past.
 */
function YOURMODULE_event_save($module, $machine_name, $message, array $arguments = array(), array $options = array()) {
  if (module_exists('past')) {
    past_event_save($module, $machine_name, $message, $arguments, $options);
  }
  else {
    $severity = isset($options['severity']) ? $options['severity'] : WATCHDOG_NOTICE;

    // Decode exceptions, as trying to print_r() them results in recursions.
    foreach ($arguments as &$argument) {
      if ($argument instanceof Exception) {
        $decoded = _drupal_decode_exception($argument);
        $argument = $decoded;
      }
    }

    watchdog((string) $module, '@name::@message <pre>@arguments</pre>', array(
      '@name' => $machine_name,
      '@message' => $message,
      '@arguments' => print_r($arguments, TRUE),
    ), $severity);
  }
}


 FOR MORE INFORMATION
 --------------------

  * Project Page: http://drupal.org/project/past
  * Issue Queue: http://drupal.org/project/issues/past
