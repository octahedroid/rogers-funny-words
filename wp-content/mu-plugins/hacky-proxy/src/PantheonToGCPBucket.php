<?php

namespace Stevector\HackyProxy;

use Proxy\Proxy;
use Proxy\Adapter\Guzzle\GuzzleAdapter;
use Proxy\Filter\RemoveEncodingFilter;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SapiStreamEmitter;

class PantheonToGCPBucket {

  protected $skipUrls = [
      '.php',
      '/wp/',
      'wp-admin.php',
  ];
  protected $environment = '';
  protected $site = '';
  protected $forwards = [];
  protected $prefix = '';
  protected $url = '';
  protected $uri = '';

  public function setForwards(Array $forwards)
  {
    $this->forwards = $forwards;

    return $this;
  }

  public function setSkipUrls(Array $skipUrls)
  {
    $this->skipUrls = $skipUrls;

    return $this;
  }

  public function addSkipUrls(Array $skipUrls)
  {
    $this->skipUrls = array_merge($this->skipUrls ,$skipUrls);

    return $this;
  }

  public function setEnvironment(String $environment)
  {
    $this->environment = $environment;

    return $this;
  }

  public function setSite(String $site)
  {
    $this->site = $site;

    return $this;
  }

  private function calculateSite()
  {
    if (!empty($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] !== 'lando') {
      $this->site = $_ENV['PANTHEON_SITE_NAME'];
    }
  }

  private function calculateEnvironment()
  {
    if (!empty($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] !== 'lando') {
      $this->environment = $_ENV['PANTHEON_ENVIRONMENT'];
    };
  }

  private function calculateForward()
  {
    foreach ($this->forwards as $forward) {
      if (strpos($_SERVER['REQUEST_URI'], $forward['path']) !== FALSE) {
        $this->prefix = str_replace(
          [
            '{site}',
            '{environment}',
          ],
          [
            $this->site,
            $this->environment,
          ],
          $forward['prefix']
        );
        $this->url = str_replace(
          [
            '{site}',
            '{environment}',
          ],
          [
            $this->site,
            $this->environment,
          ],
          $forward['url']
        );

        return;
      }
    }
  }

  private function calculateUri()
  {
    if ($_SERVER['REQUEST_URI'] === '/') {
      $this->uri = '/' . $this->prefix;
    }

    $this->uri = '/' . $this->prefix . $_SERVER['REQUEST_URI'];
  }

  private function isBackendPath()
  {
    $isBackendPath = array_filter($this->skipUrls, function($url) {
      return strpos($_SERVER['REQUEST_URI'], $url) !== FALSE;
    });

    return (count($isBackendPath) > 0);
  }

  private function isValidPath($guzzle) {
    try {
      $guzzle->head($this->url . $this->uri);

      return true;
    } catch (\Exception $e) {

      return false;
    }
  }

  function forward()
  {
      if (empty($_SERVER['REQUEST_URI'])) {
        return;
      }

      if ($this->isBackendPath()) {
        return;
      }

      // Calculate variables
      $this->calculateEnvironment();
      $this->calculateSite();
      $this->calculateForward();
      $this->calculateUri();

      $server = array_merge(
        $_SERVER,
        [
          'REQUEST_URI' => $this->uri,
        ]
      );

      // Create request object
      $request = ServerRequestFactory::fromGlobals($server);

      // Create a guzzle client
      $guzzle = new \GuzzleHttp\Client([
        'curl' => [
          CURLOPT_TCP_KEEPALIVE => 45,
          CURLOPT_TCP_KEEPIDLE => 45,
        ]
      ]);

      // Create the proxy instance
      $proxy = new Proxy(new GuzzleAdapter($guzzle));

      // Add a response filter that removes the encoding headers.
      $proxy->filter(new RemoveEncodingFilter());

      if (!$this->isValidPath($guzzle)) {

        return;
      }

      // Forward the request and get the response.
      $response = $proxy->forward($request)->to($this->url);

      // @TODO dependency to laminas-httphandlerrunner
      $emiter = new SapiStreamEmitter();
      $emiter->emit($response);
      exit();
    }
}
