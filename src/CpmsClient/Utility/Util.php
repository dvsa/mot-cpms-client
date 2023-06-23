<?php
namespace CpmsClient\Utility;

/**
 * Class Util
 *
 * @package CpmsClient\Utility
 */
class Util
{
    /**
     * Method to append any additional data to the clientUrl
     *
     * @param $url
     * @param $requiredParams
     *
     * @return string
     */
    public static function appendQueryString($url, array $requiredParams = null)
    {
        if (!empty($url) and stripos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        if (empty($requiredParams)) {
            return $url;
        }

        if (strpos($url, '?')) {
            return $url . '&' . http_build_query($requiredParams);
        } else {
            return $url . '?' . http_build_query($requiredParams);
        }
    }

    /**
     * Format exception
     *
     * @param \Exception $e
     *
     * @return string
     */
    public static function processException(\Exception $e)
    {
        $trace = $e->getTraceAsString();
        $i     = 1;
        do {
            $messages[] = $i++ . ": " . $e->getMessage();
        } while ($e = $e->getPrevious());

        $log = "Exception:\n" . implode("\n", $messages);
        $log .= "\nTrace:\n" . $trace . "\n\n";

        return $log;
    }
}
