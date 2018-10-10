<?php
namespace NikonM\Monolog;

use Monolog\Handler\MissingExtensionException;
use Monolog\Handler\SocketHandler;
use Monolog\Logger;

class MattermostWebhookHandler extends SocketHandler
{
    /** @var string */
    private $url;
    /** @var array */
    private $options;
    
    public function __construct($url, array $options = [], $level = Logger::DEBUG, $bubble = true)
    {
        if (!extension_loaded('openssl')) {
            throw new MissingExtensionException(
                'The OpenSSL PHP extension is required to use the MattermostWebhookHandler'
            );
        }
        $this->options = $options;
        $this->url = $url;
        parent::__construct($this->url, $level, $bubble);
    }
    
    /**
     * {@inheritdoc}
     *
     * @param array $record
     */
    protected function write(array $record)
    {
        parent::write($record);
        $this->finalizeWrite();
    }
    
    /**
     * Build body for API call
     *
     * @param array $record
     * @return string
     */
    private function buildContent($record)
    {
        $dataArray = array_merge($this->options, ['text' => $record['formatted']]);
        return json_encode($dataArray);
    }
    
    /**
     * @param $record
     * @return string
     */
    protected function generateDataStream($record)
    {
        $content = $this->buildContent($record);
        return $this->buildHeader($content) . $content;
    }
    
    protected function finalizeWrite()
    {
        $res = $this->getResource();
        if (is_resource($res)) {
            @fread($res, 2048);
        }
        $this->closeSocket();
    }
    
    /**
     * Build header for API Call
     *
     * @param  string $content
     * @return string
     */
    private function buildHeader($content)
    {
        $header = "POST " . parse_url($this->url, PHP_URL_PATH) . " HTTP/1.1\r\n";
        $header .= "Host: " . parse_url($this->url, PHP_URL_HOST) . "\r\n";
        $header .= "Content-Type: application/json\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "\r\n";
        
        return $header;
    }
}
