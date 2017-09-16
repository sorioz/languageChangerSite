<?php

/**
 * Generic language selection logic for websites. Feel free to use.
 *
 * @author Janos Pasztor <janos@pasztor.at>
 * @license http://opensource.org/licenses/BSD-3-Clause
 *
 * Copyright (c) 2014, Janos Pasztor
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list
 *    of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice, this
 *    list of conditions and the following disclaimer in the documentation and/or other
 *    materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors may be
 *    used to endorse or promote products derived from this software without specific
 *    prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * Abstract:
 *
 * - Look for an already selected language in the URL. If it is present, don't to
 *   anything.
 * - If a bot is detected from the User-Agent, redirect to default language to avoid
 *   indexing confusion.
 * - If a language cookie is detected, use that language.
 * - Sort the Accept-Language header (browser language) and try to look for a non-default
 *   language there. If present, use it.
 * - Lookup GeoIP and try to assert a non-default language from there.
 * - Fallback to default language.
 *
 * Note on default language: in a lot of cases non-English people use English browsers,
 * therefore the algorithm tries to detect that.
 */
/**
 * Tells if a suitable language has already been found.
 *
 * @var bool $foundLanguage
 */
$foundLanguage = false;
/**
 * Skips any further checks.
 *
 * @var bool $finished
 */
$finished = false;
/**
 * Lists all supported languages.
 *
 * @var string[] $languages
 */
$languages = array(
    'hu',
    'en',
    'de'
);
/**
 * @var string the language found in the URL
 */
$language = '';
/**
 * Maps countries to languages.
 *
 * @var string[] $countryMap
 */
$countryMap = array(
    'hu' => 'hu',
    'de' => 'de',
    'ch' => 'de',
    'at' => 'de',
    'en' => 'en',
);
/**
 * The default language for this project.
 * It is used as a fallback and any language settings to this language are disregarded.
 * The reason for this is that a lot of non-English users use English browsers, so
 * other checks are more appropriate.
 *
 * @var string $defaultLang
 */
$defaultLang = 'hu';
$ip = $_SERVER['REMOTE_ADDR'];
/**
 * If the request is not GET, let it through so any POST, AJAX, etc keep working.
 *
 */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $finished = true;
}
/**
 * Check URL. If the first part contains a valid language, the process is finished.
 */
$url = explode('/', $_SERVER['REQUEST_URI']);
if (isset($url[1]) && in_array($url[1], $languages)) {
    setcookie('lang', $url[1], time() + 365 * 24 * 3600, '/');
    $finished = true;
    $language = $url[1];
}
/**
 * Check bots. Bots always get the default language in order to avoid language confusion.
 */
if (!$finished) {
    if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot/i', $_SERVER['HTTP_USER_AGENT'])) {
        $finished = true;
        $foundLanguage = $defaultLang;
    }
}
/**
 * Check previously set cookie. If the user has a cookie, give him that language.
 */
if (!$finished) {
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $languages)) {
        $foundLanguage = $_COOKIE['lang'];
        $finished = true;
    }
}
/**
 * Check accept-language header. This tells the language of the browser.
 * If it's not the default language, give him the browser language.
 * If it's the default, continue the checks.
 */
if (!$finished) {
    $acceptLanguage = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $acceptLangs = array();
    foreach ($acceptLanguage as $key => $lang) {
        $lang = trim($lang);
        $lang = explode(';', $lang);
        $langCode = $lang[0];
        if (isset($lang[1])) {
            $langWeight = explode('=', $lang[1]);
            if (isset($langWeight[1])) {
                $langWeight = (float)$langWeight[1];
            }
        }

        if (!isset($langWeight)) {
            $langWeight = 1.0;
        }

        $acceptLangs[$langCode] = $langWeight;
    }
    asort($acceptLangs);
    foreach ($acceptLangs as $acceptLang => $weight) {
        if (in_array($acceptLang, $languages) && $acceptLang !== $defaultLang) {
            $foundLanguage = $acceptLang;
            $finished = true;
        }
    }
}
/**
 * Check GeoIP. If there is a country match for the language, give that language.
 */
if (!$finished) {
    if (function_exists('geoip_country_code_by_name')) {
        $country = geoip_country_code_by_name($ip);
        if ($country &&
            isset($countryMap[$country]) &&
            in_array($countryMap[$country], $languages)
        ) {
            if ($countryMap[$country] !== $defaultLang) {
                $foundLanguage = $countryMap[$country];
                $finished = true;
            }
        }
    }
}
/**
 * Use default language if all else fails.
 */
if (!$finished) {
    $foundLanguage = $defaultLang;
    $finished = true;
}
/**
 * If no language was found, serve the page. Otherwise redirect to the language and save a cookie.
 */
if (!$foundLanguage) {
    return $language;
} else {
    header('HTTP/1.1 301 Moved Permanently', true, 301);
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/' .
        $foundLanguage . $_SERVER['REQUEST_URI']);
    setcookie('lang', $foundLanguage, time() + 365 * 24 * 3600, '/');
    exit;
}
