<?php

declare(strict_types=1);

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
     * @param array<string, mixed>|null $requiredParams
     *
     * @return string
     */
    public static function appendQueryString(string $url, array $requiredParams = null): string
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
    public static function processException(\Exception $e): string
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
