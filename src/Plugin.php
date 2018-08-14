<?php

namespace BigfishDesign\SushiComposerPlugin;

use Aws\S3\S3Client;
use Composer\Composer;
use Aws\S3\StreamWrapper;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Aws\Credentials\CredentialProvider;
use Composer\Plugin\PreFileDownloadEvent;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Holds the composer instance.
     *
     * @var \Composer\Composer
     */
    protected $composer;

    /**
     * Activate the plugin.
     *
     * @param \Composer\Composer $composer
     * @param \Composer\IO\IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
    }


    /**
     * Get the subscribed events for the plugin.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PluginEvents::PRE_FILE_DOWNLOAD => [
                ['modifySushiS3ProtocolDownloads', 0]
            ],
        ];
    }

    /**
     * Modify sushi-s3 protocol downloads.
     *
     * @param \Composer\Plugin\PreFileDownloadEvent $event
     */
    public function modifySushiS3ProtocolDownloads(PreFileDownloadEvent $event)
    {
        $protocol = parse_url($event->getProcessedUrl(), PHP_URL_SCHEME);

        if ($protocol === 'sushi-s3') {
            $vendorDir = getenv('HOME') . '/.composer/vendor/';

            if (!function_exists('AWS\manifest')) {
                require_once $vendorDir . '/aws/aws-sdk-php/src/functions.php';
            }

            if (!function_exists('GuzzleHttp\Psr7\uri_for')) {
                require_once $vendorDir . '/guzzlehttp/psr7/src/functions_include.php';
            }

            if (!function_exists('GuzzleHttp\choose_handler')) {
                require_once $vendorDir . '/guzzlehttp/guzzle/src/functions_include.php';
            }

            if (!function_exists('GuzzleHttp\Promise\queue')) {
                require_once $vendorDir . '/guzzlehttp/promises/src/functions_include.php';
            }

            $client = new S3Client([
                'version' => 'latest',
                'region' => 'eu-west-2',
            ]);

            StreamWrapper::register($client, 'sushi-s3', null);
        }
    }
}
