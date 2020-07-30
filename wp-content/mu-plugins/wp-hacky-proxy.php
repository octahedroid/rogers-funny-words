<?php

require $_ENV['HOME'] . '/code/wp-content/mu-plugins/wp-hacky-proxy/vendor/autoload.php';

// Create new PantheonToGCPBucket instance
$hackyproxy = new \Stevector\HackyProxy\PantheonToGCPBucket();

// Set Forward paths
$hackyproxy
  ->setSite('pantheon-rogers-funny-words') // pantheon site
  ->setEnvironment($_ENV['PANTHEON_ENVIRONMENT']) // pantheon environment
  ->setFramework('wordpress') // pantheon framework
  ->setForwards(
    [
      [
        'path' => '/',
        'url' => 'https://{environment}---{site}-ffqp7tmlta-uc.a.run.app',
      ],
    ]
  )
  ->forward();
