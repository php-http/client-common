<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Exception\TransferException;
use Http\Message\Cookie;
use Http\Message\CookieJar;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Handle request cookies.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class CookiePlugin implements Plugin
{
    /**
     * Cookie storage.
     *
     * @var CookieJar
     */
    private $cookieJar;

    /**
     * @param CookieJar $cookieJar
     */
    public function __construct(CookieJar $cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first)
    {
        foreach ($this->cookieJar->getCookies() as $cookie) {
            if ($cookie->isExpired()) {
                continue;
            }

            if (!$cookie->matchDomain($request->getUri()->getHost())) {
                continue;
            }

            if (!$cookie->matchPath($request->getUri()->getPath())) {
                continue;
            }

            if ($cookie->isSecure() && ($request->getUri()->getScheme() !== 'https')) {
                continue;
            }

            $request = $request->withAddedHeader('Cookie', sprintf('%s=%s', $cookie->getName(), $cookie->getValue()));
        }

        return $next($request)->then(function (ResponseInterface $response) use ($request) {
            if ($response->hasHeader('Set-Cookie')) {
                $setCookies = $response->getHeader('Set-Cookie');

                foreach ($setCookies as $setCookie) {
                    $cookie = $this->createCookie($request, $setCookie);

                    // Cookie invalid do not use it
                    if (null === $cookie) {
                        continue;
                    }

                    // Restrict setting cookie from another domain
                    if (!preg_match("/\.{$cookie->getDomain()}$/", '.'.$request->getUri()->getHost())) {
                        continue;
                    }

                    $this->cookieJar->addCookie($cookie);
                }
            }

            return $response;
        });
    }

    /**
     * Creates a cookie from a string.
     *
     * @param RequestInterface $request
     * @param $setCookie
     *
     * @return Cookie|null
     *
     * @throws TransferException
     */
    private function createCookie(RequestInterface $request, $setCookie)
    {
        $parts = array_map('trim', explode(';', $setCookie));

        if (empty($parts) || !strpos($parts[0], '=')) {
            return;
        }

        list($name, $cookieValue) = $this->createValueKey(array_shift($parts));

        $maxAge = null;
        $expires = null;
        $domain = $request->getUri()->getHost();
        $path = $request->getUri()->getPath();
        $secure = false;
        $httpOnly = false;

        // Add the cookie pieces into the parsed data array
        foreach ($parts as $part) {
            list($key, $value) = $this->createValueKey($part);

            switch (strtolower($key)) {
                case 'expires':
                    $expires = $this->parseExpires($value);

                    if (true !== ($expires instanceof \DateTime)) {
                        throw new TransferException(
                            sprintf(
                                'Cookie header `%s` expires value `%s` could not be converted to date',
                                $name,
                                $value
                            )
                        );
                    }

                    break;

                case 'max-age':
                    $maxAge = (int) $value;

                    break;

                case 'domain':
                    $domain = $value;

                    break;

                case 'path':
                    $path = $value;

                    break;

                case 'secure':
                    $secure = true;

                    break;

                case 'httponly':
                    $httpOnly = true;

                    break;
            }
        }

        return new Cookie($name, $cookieValue, $maxAge, $domain, $path, $secure, $httpOnly, $expires);
    }

    /**
     * Separates key/value pair from cookie.
     *
     * @param $part
     *
     * @return array
     */
    private function createValueKey($part)
    {
        $parts = explode('=', $part, 2);
        $key = trim($parts[0]);
        $value = isset($parts[1]) ? trim($parts[1]) : true;

        return [$key, $value];
    }

    /**
     * Parses cookie "expires" value.
     *
     * @param string $expires
     *
     * @return \DateTime|false
     *
     * @see https://tools.ietf.org/html/rfc6265#section-5.1.1
     * @see https://github.com/salesforce/tough-cookie/blob/master/lib/cookie.js
     */
    private function parseExpires($expires)
    {
        /*
         * RFC6265 5.1.1:
         * 2. Process each date-token sequentially in the order the date-tokens
         * appear in the cookie-date
         */
        $tokens = preg_split('/[\x09\x20-\x2F\x3B-\x40\x5B-\x60\x7B-\x7E]/', $expires);
        if (!is_array($tokens)) {
            return false;
        }

        $hour = null;
        $minutes = null;
        $seconds = null;
        $day = null;
        $month = null;
        $year = null;

        foreach ($tokens as $token) {
            $token = trim($token);
            if ('' === $token) {
                continue;
            }

            /*
             * 2.1. If the found-time flag is not set and the token matches the time
             * production, set the found-time flag and set the hour- value,
             * minute-value, and second-value to the numbers denoted by the digits in
             * the date-token, respectively.  Skip the remaining sub-steps and continue
             * to the next date-token.
             */
            if (null === $seconds) {
                preg_match('/^(\d{1,2})\D*:(\d{1,2})\D*:(\d{1,2})\D*$/', $token, $match);
                if (count($match) > 0) {
                    $hour = (int) $match[1];
                    $minutes = (int) $match[2];
                    $seconds = (int) $match[3];
                    /*
                     * [fail if]
                     * - the hour-value is greater than 23,
                     * - the minute-value is greater than 59, or
                     * - the second-value is greater than 59.
                     */
                    if ($hour > 23 || $minutes > 59 || $seconds > 59) {
                        return false;
                    }

                    continue;
                }
            }

            /*
             * 2.2. If the found-day-of-month flag is not set and the date-token matches
             * the day-of-month production, set the found-day-of- month flag and set
             * the day-of-month-value to the number denoted by the date-token.  Skip
             * the remaining sub-steps and continue to the next date-token.
             */
            if (null === $day) {
                preg_match('/^(\d{1,2})\D*$/', $token, $match);
                if (count($match) > 0) {
                    $day = (int) $match[1];
                    /*
                     * [fail if] the day-of-month-value is less than 1 or greater than 31
                     */
                    if ($day < 1 || $day > 31) {
                        return false;
                    }
                    continue;
                }
            }

            /*
             * 2.3. If the found-month flag is not set and the date-token matches the
             * month production, set the found-month flag and set the month-value to
             * the month denoted by the date-token.  Skip the remaining sub-steps and
             * continue to the next date-token.
             */
            if (null === $month) {
                preg_match('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i', $token, $match);
                if (count($match) > 0) {
                    $month = array_search(
                        strtolower($match[1]),
                        [
                            'jan',
                            'feb',
                            'mar',
                            'apr',
                            'may',
                            'jun',
                            'jul',
                            'aug',
                            'sep',
                            'oct',
                            'nov',
                            'dec'
                        ],
                        true
                    );
                    continue;
                }
            }

            /*
             * 2.4. If the found-year flag is not set and the date-token matches the year
             * production, set the found-year flag and set the year-value to the number
             * denoted by the date-token.  Skip the remaining sub-steps and continue to
             * the next date-token.
             */
            if (null === $year) {
                preg_match(' /^(\d{2}|\d{4})$/', $token, $match);
                if (count($match) > 0) {
                    $year = (int) $match[1];
                    /*
                     * 3.  If the year-value is greater than or equal to 70 and less
                     * than or equal to 99, increment the year-value by 1900.
                     * 4.  If the year-value is greater than or equal to 0 and less
                     * than or equal to 69, increment the year-value by 2000.
                     */
                    if (70 <= $year && $year <= 99) {
                        $year += 1900;
                    } elseif (0 <= $year && $year <= 69) {
                        $year += 2000;
                    }

                    if ($year < 1601) {
                        return false; // 5. ... the year-value is less than 1601
                    }
                }
            }
        }

        if (null === $seconds || null === $day || null === $month || null === $year) {
            /*
             * 5. ... at least one of the found-day-of-month, found-month, found-
             * year, or found-time flags is not set,
             */
            return false;
        }

        // UTC/GMT format required by cookies.
        $time = new \DateTime('now', new \DateTimeZone('UTC'));
        $time->setDate($year, $month, $day);
        $time->setTime($hour, $minutes, $seconds);

        return $time;
    }
}
