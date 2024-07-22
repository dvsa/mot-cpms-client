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
     * @param string $url
     * @param ?array $requiredParams
     *
     * @return string
     */
    public static function appendQueryString($url, array $requiredParams = null)
    {
        if (!empty($url) and stripos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        if ($requiredParams === null || $requiredParams === []) {
            return $url;
        }

        if (str_contains($url, '?')) {
            return $url . '&' . http_build_query($requiredParams);
        } else {
            return $url . '?' . http_build_query($requiredParams);
        }
    }

    /**
     * Format exception
     *
     * @return string
     */
    public static function processException(\Exception $e)
    {
        $trace = $e->getTraceAsString();
        $i     = 1;
        $messages = [];
        do {
            $messages[] = $i++ . ": " . $e->getMessage();
        } while ($e = $e->getPrevious());

        $log = "Exception:\n" . implode("\n", $messages);
        $log .= "\nTrace:\n" . $trace . "\n\n";

        return $log;
    }
}
