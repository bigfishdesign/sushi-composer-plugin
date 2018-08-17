<?php

namespace BigfishDesign\SushiComposerPlugin;

use Aws\S3\S3Client;
use Composer\Composer;
use Aws\S3\StreamWrapper;
use Composer\IO\IOInterface;
use Aws\Credentials\Credentials;
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
     * Holds the config
     *
     * @var array
     */
    protected $config;

    /**
     * Holds an array of created clients.
     *
     * @var array
     */
    protected $clients = [];

    /**
     * Hold the state of the required files being loaded.
     *
     * @var bool
     */
    protected $requiredFilesLoaded = false;

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

        if ($this->shouldCreateClient($protocol)) {
            $this->loadAnyRequiredFiles();

            $wrapperConfig = $this->mapAnyCredentials(
                $this->getConfig()[$protocol]
            );

            $this->clients[$protocol] = new S3Client(array_merge([
                'version' => 'latest',
            ], $wrapperConfig));

            StreamWrapper::register($this->clients[$protocol], $protocol, null);
        }
    }

    /**
     * Map amy credentials into the given wrapper config.
     *
     * @param array $wrapperConfig
     * @return array
     */
    public function mapAnyCredentials(array $wrapperConfig)
    {
        if (!isset($wrapperConfig['credentials'])) {
            return $wrapperConfig;
        }

        return array_merge($wrapperConfig, $this->buildClientCredentials($wrapperConfig['credentials']));
    }

    /**
     * Build the credentials for a client.
     *
     * @param array $credentials
     * @return array
     */
    public function buildClientCredentials(array $credentials)
    {
        if (isset($credentials['key']) && isset($credentials['secret'])) {
            return [
                'credentials' => CredentialProvider::fromCredentials(
                    new Credentials($credentials['key'], $credentials['secret'])
                )
            ];
        }

        return [];
    }

    /**
     * Determine if a client should be created for the given protocol.
     *
     * @param string $protocol
     * @return bool
     */
    protected function shouldCreateClient($protocol)
    {
        return !in_array($protocol, array_keys($this->clients))
            && in_array($protocol, array_keys($this->getConfig()));
    }

    /**
     * Get the aws composer config.
     *
     * @return array
     */
    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = $this->buildPluginConfig();
        }

        return $this->config;
    }

    /**
     * Get the composer config.
     *
     * @return array
     */
    protected function getComposerConfig()
    {
        if (!$this->composer->getConfig()->has('aws-composer')) {
            return [];
        }

        return $this->composer->getConfig()->get('aws-composer');
    }

    /**
     * Build the plugin config.
     *
     * @return array
     */
    protected function buildPluginConfig()
    {
        $config = [];
        $composerConfig = $this->getComposerConfig();

        $defaults = !empty($composerConfig['defaults']) ? $composerConfig['defaults'] : [];
        $wrappers = !empty($composerConfig['wrappers']) ? $composerConfig['wrappers'] : [];

        foreach ($wrappers as $key => $value) {
            $protocol = is_array($value) ? $key : $value;
            $data = is_array($value) ? $value : [];

            $config[$protocol] = array_merge($defaults, $data);
        }

        return $config;
    }

    /**
     * Load any required files.
     */
    protected function loadAnyRequiredFiles()
    {
        if($this->requiredFilesLoaded) {
            return;
        }

        if (!function_exists('AWS\manifest')) {
            require_once __DIR__ . '/../../../aws/aws-sdk-php/src/functions.php';
        }

        if (!function_exists('GuzzleHttp\Psr7\uri_for')) {
            require_once __DIR__ . '/../../../guzzlehttp/psr7/src/functions_include.php';
        }

        require_once __DIR__ . '/../../../guzzlehttp/guzzle/src/functions_include.php';

        require_once __DIR__ . '/../../../guzzlehttp/promises/src/functions_include.php';

        $this->requiredFilesLoaded = true;
    }
}
