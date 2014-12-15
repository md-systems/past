<?php
/**
 * Contains PastGuzzleLogAdapter.
 */

use Guzzle\Log\LogAdapterInterface;

/**
 * Abstract Guzzle log adapter.
 *
 * Takes care of stuff common to SimplePastGuzzleLogAdapter and
 * CompactPastGuzzleLogAdapter.
 */
abstract class PastGuzzleLogAdapter implements LogAdapterInterface {

  /**
   * The Past event module string.
   *
   * @var string
   */
  const PAST_MODULE = 'past_guzzle';

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

  protected function prepareArgument(array $extras){
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
        'freshness' => $response->getFreshness() ? $response->getFreshness() : '',
        'headers' => $response->getHeaderLines(),
        'info' => $response->getInfo(),
        'protocol' => $response->getProtocol() . '/' . $response->getProtocolVersion(),
        'reason_phrase' => $response->getReasonPhrase(),
        'redirect_count' => $response->getRedirectCount(),
        'status_code' => $response->getStatusCode(),
        'body' => (string) $response->getBody(),
      );
    }

    return $arguments;
  }
}

/**
 * A simple Guzzle log adapter for Past.
 *
 * Writes a Past record for every single log event.
 */
class SimplePastGuzzleLogAdapter extends PastGuzzleLogAdapter {

  /**
   * The Past event machine name.
   *
   * @var string
   */
  const PAST_MACHINE_NAME = 'request_log';

  /**
   * {@inheritdoc}
   */
  public function log($message, $priority = LOG_INFO, $extras = array()) {
    past_event_save(self::PAST_MODULE, self::PAST_MACHINE_NAME, $message, $this->prepareArgument($extras), array(
      'severity' => self::$severityMap[$priority],
    ));
  }
}

/**
 * A compact Guzzle log adapter for Past.
 *
 * Aggregates all log events into one single Past event.
 */
class CompactPastGuzzleLogAdapter extends PastGuzzleLogAdapter {

  /**
   * The Past event machine name.
   *
   * @var string
   */
  const PAST_MACHINE_NAME = 'aggregated_request_log';

  /**
   * @var array
   */
  protected $events;

  /**
   * The resulting event severity.
   *
   * @var int
   */
  protected $severity;

  /**
   * Aggregated request statistics.
   *
   * @var int[]
   */
  protected $stats;

  /**
   * Constructor.
   */
  function __construct() {
    $this->reset();
  }

  /**
   * {@inheritdoc}
   */
  public function log($message, $priority = LOG_INFO, $extras = array()) {
    $event = $this->prepareArgument($extras);
    $event['message'] = $message;
    $severities = past_event_severities();
    $event['severity'] = $severities[self::$severityMap[$priority]];
    $this->events[] = $event;
    $this->updateSeverity($priority);
    $this->updateStats($event['response']['info']);
  }

  /**
   * Updates the event severity.
   *
   * Compares the current severity with the new one. The severity gets updated
   * if the new one has a higher severity than the current.
   *
   * @param int $priority
   *   Priority of message (use the \LOG_* constants of 0 - 7)
   */
  protected function updateSeverity($priority) {
    $severity = self::$severityMap[$priority];
    if ($this->severity > $severity) {
      $this->severity = $severity;
    }
  }

  /**
   * Updates the aggregated request statistics.
   *
   * @param array $info
   *   The response info array.
   */
  protected function updateStats(array $info) {
    foreach (array_keys($this->stats) as $stat) {
      if (isset($info[$stat])) {
        $this->stats[$stat] += $info[$stat];
      }
    }
  }

  /**
   * Commits the Past event.
   *
   * Checks if there is a Past event present and triggers a save. Create a new
   * event afterwards.
   */
  public function commit() {
    if (!empty($this->events)) {
      $this->stats['speed_download'] = $this->stats['speed_download'] / count($this->events);
      $this->stats['speed_upload'] = $this->stats['speed_upload'] / count($this->events);
      $arguments = array(
        'requests' => $this->events,
        'stats' => $this->stats,
      );
      $severities = past_event_severities();
      $message = format_plural(
        count($this->events),
        'Tracked one Guzzle request with a severity of @severity',
        'Tracked @count Guzzle requests with the highest severity of @severity',
        array('@severity' => $severities[$this->severity])
      );
      past_event_save(self::PAST_MODULE, self::PAST_MACHINE_NAME, $message, $arguments, array(
        'severity' => $this->severity,
      ));
    }
    $this->reset();
  }

  protected function reset() {
    // Reset the internal state.
    $this->events = array();
    $this->severity = PAST_SEVERITY_DEBUG;
    $this->stats = array(
      'total_time' => 0,
      'namelookup_time' => 0,
      'connect_time' => 0,
      'pretransfer_time' => 0,
      'size_upload' => 0,
      'size_download' => 0,
      'speed_download' => 0,
      'speed_upload' => 0,
      'download_content_length' => 0,
      'upload_content_length' => 0,
      'starttransfer_time' => 0,
      'redirect_time' => 0,
    );
  }
}
