<?php
/**
 * Contains PastGuzzleLogAdapter.
 */

use Guzzle\Log\LogAdapterInterface;

/**
 * A Guzzle log adapter for past.
 *
 * Writes a past record for every single log event.
 */
class PastGuzzleLogAdapter implements LogAdapterInterface {

  /**
   * The past event module string.
   *
   * @var string
   */
  const PAST_MODULE = 'past_guzzle';

  /**
   * The past event machine name.
   *
   * @var string
   */
  const PAST_MACHINE_NAME = 'request_log';

  /**
   * Format string for the log message.
   *
   * @var string
   * @see \Guzzle\Log\MessageFormatter
   */
  const MESSAGE_FORMAT = '{method} {url} {code} {phrase} {total_time}s';

  /**
   * Mapping of log severities.
   *
   * @var int[]
   *
   * @todo Check necessity for this mapping
   *   PHPs LOG_* and pasts PAST_SEVERITY_* has identical values. We can simply
   *   omit this mapping. This would risk unpredictable behaviour in the event
   *   either PHP or Past changes the values behind its severity variables.
   */
  protected static $severityMap = array(
    LOG_EMERG => PAST_SEVERITY_EMERGENCY,
    LOG_ALERT => PAST_SEVERITY_ALERT,
    LOG_CRIT => PAST_SEVERITY_CRITICAL,
    LOG_ERR => PAST_SEVERITY_ERROR,
    LOG_WARNING => PAST_SEVERITY_WARNING,
    LOG_NOTICE => PAST_SEVERITY_NOTICE,
    LOG_INFO => PAST_SEVERITY_INFO,
    LOG_DEBUG => PAST_SEVERITY_DEBUG,
  );

  /**
   * {@inheritdoc}
   */
  public function log($message, $priority = LOG_INFO, $extras = array()) {
    $arguments = array('request' => NULL, 'response' => NULL);

    // Extract the request details.
    /* @var \Guzzle\Http\Message\Request $request */
    if (isset($extras['request']) && ($request = $extras['request']) && $request instanceof \Guzzle\Http\Message\Request) {
      $request = $extras['request'];
      $arguments['request'] = array(
        'curl_options' => $request->getCurlOptions(),
        'headers' => $request->getHeaderLines(),
        'method' => $request->getMethod(),
        'params' => $request->getParams(),
        'port' => $request->getPort(),
        'protocol' => $request->getScheme() . '/' . $request->getProtocolVersion(),
        'url' => $request->getUrl(),
      );
      // Get the request body if there is one.
      if ($request instanceof \Guzzle\Http\Message\EntityEnclosingRequest) {
        /* @var \Guzzle\Http\Message\EntityEnclosingRequest $request */
        $arguments['request']['body'] = (string) $request->getBody();
      }
    }
    // Extract the details of the response.
    /* @var \Guzzle\Http\Message\Response $response */
    if (isset($extras['response']) && ($response = $extras['response']) && $response instanceof \Guzzle\Http\Message\Response) {
      $arguments['response'] = array(
        'effective_url' => $response->getEffectiveUrl(),
        'freshness' => $response->getFreshness(),
        'headers' => $response->getHeaderLines(),
        'info' => $response->getInfo(),
        'protocol' => $response->getProtocol() . '/' . $response->getProtocolVersion(),
        'reason_phrase' => $response->getReasonPhrase(),
        'redirect_count' => $response->getRedirectCount(),
        'status_code' => $response->getStatusCode(),
        'body' => (string) $response->getBody(),
      );
    }

    past_event_save(self::PAST_MODULE, self::PAST_MACHINE_NAME, $message, $arguments, array(
      'severity' => self::$severityMap[$priority],
    ));
  }
}
