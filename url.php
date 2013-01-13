<?php
/*
 ******************************************************************************************************************
 *  Author:           Nam Tran, Grey Hat Apps
 *  Email Address:    nam@greyhatapps
 *  Date Created:     12/15/2010
 *
 ******************************************************************************************************************
 *  Class: Url
 *
 ******************************************************************************************************************
 */
  include_once("inc-settings.php");
  include_once("core/log.php");
  include_once("core/stringcommon.php");
  include_once("dataobjects/affiliate.php");

  class Url
  {
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  formatUrl
  // +
  // +  Formats a given URL
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function formatUrl($pUrl)
    {
      $array_url = explode("/", $pUrl);

      for($i=0; $i<count($array_url)-1; $i++)
      {
        if(trim($array_url[$i] == ""))
        {
          $host = $array_url[$i+1];
          break;
        }
      }
      if(trim($host) == "")
      {
        // If contains ':'
        if(stristr($array_url[0], ":"))
        {
          $host = $array_url[1];
          $pUrl = str_replace(":/", "://", $pUrl);
        }
      }

      return $pUrl;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  getUri
  // +
  // +  Extracts the URL from the given URL
  // +  ie. http://www.dealseeq.com/go/http://www.amazon.com yields
  // +      http://www.amazon.com
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function getUri($pPrefix="\/go\/")
    {
//      $url = urldecode($_SERVER['REQUEST_URI']);
      $url = $_SERVER['REQUEST_URI'];
      // Remove only first instance of prefix
      $url = preg_replace('/' . $pPrefix . '/', '', $url, 1);
      $url = Url::formatUrl($url);

      return $url;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  getHost
  // +
  // +  Extracts the host from a specified URL. (ie. amazon.com, shopping.hp.com, etc)
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function getHost($pUrl, &$pSubDomain="")
    {
      $host = "";

      if(trim($pUrl) != "")
      {
        $array_url = explode("/", $pUrl);
        // $array_url[0]: http:
        // $array_url[1]:
        // $array_url[2]: www.amazon.com
        // $array_url[3]: ...

        // Loop through and find a blank element ('://' explode) -- the next one is the hostname
        for($i=0; $i<count($array_url)-1; $i++)
        {
          if(trim($array_url[$i] == ""))
          {
            $host = $array_url[$i+1];
            break;
          }
        }

        // If no blank element was found, then the first element must be the hostname (ie. www.amazon.com/xxxxx)
        if(trim($host) == "")
        {
          $host = $array_url[0];
        }

        // Explode www.amazon.com
        $array_host = explode(".", $host);
        switch(count($array_host))
        {
          case 4:
            // www.shopping.hp.com
            // $array_host[0]: www
            // $array_host[1]: shopping
            // $array_host[2]: hp
            // $array_host[3]: com
            $host = $array_host[2] . "." . $array_host[3];
            $pSubDomain = $array_host[0] . "." . $array_host[1];
            break;
          case 3:
            // www.amazon.com
            // $array_host[0]: www
            // $array_host[1]: amazon
            // $array_host[2]: com
            $host = $array_host[1] . "." . $array_host[2];
            $pSubDomain = $array_host[0];
            break;
          case 2:
          default:
            // amazon.com
            // $array_host[0]: amazon
            // $array_host[1]: com
            break;
        }
      }

      return trim(strtolower($host));
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  affiliateUrl
  // +
  // +  Takes a given URL and creates a affiliate embedded URL
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function affiliateUrl($pUrl=_URL_BASE, $pSite="")
    {
      $url = Affiliate::convert($pUrl, $pSite);

      if(Format::forCompare($url) == "")
        $url = $pUrl;

      return $url;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  extractUrlsKeyword
  // +
  // +  Returns an array of URLs that match the keyword
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function extractUrlsKeyword($pUrlArray, $pKeyword, $pUrlPrefix)
    {
      $array_urls = array();

      foreach((array)$pUrlArray as $url)
      {
        if(stristr($url, $pKeyword))
        {
          $url = str_replace("../", "/", $url);
          if(substr($url, 0, 4) != "http")
          {
            if(substr($url, 0, 1) == "/")
              $url = $pUrlPrefix . $url;
            else
              $url = $pUrlPrefix . "/" . $url;
          }

          array_push($array_urls, $url);
        }
      }

      return $array_urls;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  getSelf
  // +
  // +  Gets the URL to the page that makes the call
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function getSelf()
    {
      $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
      $protocol = StringCommon::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/") . $s;
      $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);

      return $protocol."://" . $_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  shorten
  // +
  // +  Shortens a URL via external service
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function shorten($pUrl)
    {
      $url = _URL_URLSHORTENER . $pUrl;

      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_REFERER, $url);
      curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
      curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
      curl_setopt($ch, CURLOPT_URL, $url);
      $html = curl_exec($ch);

      if(substr($html, 0, strlen("http://")) != "http://")
      {
        Log::write(_LOG_ERRORS, "URL shortener failed for: $url");
        return "";
      }

      return $html;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  removeSlash
  // +
  // +  Remove slash if it is the first character
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function removeSlash($pStr)
    {
      $str = $pStr;

      if(strlen($str) > 0)
      {
        if(substr($str, 0, 1) == "/")
        {
          $str = substr($str, 1);
        }
      }

      return $str;
    }

  }
?>
