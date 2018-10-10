<?php
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use NikonM\Monolog\MattermostWebhookHandler;

/**
 * Created by PhpStorm.
 * User: nikita
 * Date: 10.10.18
 * Time: 12:27
 */
class MattermostWebhookHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var resource
     */
    private $res;
    
    /**
     * @var MattermostWebhookHandler
     */
    private $handler;
    
    public function setUp()
    {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('This test requires openssl to run');
        }
    }
    
    public function testWriteHeader()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);
        
        $this->assertRegexp(
            '/POST \/test HTTP\/1.1\\r\\nHost: localhost\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/',
            $content
        );
    }
    
    public function testWriteContent()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegExp('/"text":"test1"/', $content);
    }

    public function testWriteContentWithUsernameAndIcon()
    {
        $this->createHandler('ssl://localhost:1234/test',
            [
                'username' => 'USER',
                'icon_url' => 'http://somehost/icon.png'
            ]
        );
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/"username":"USER"/', $content);
        $this->assertRegexp('/"icon_url":"http:.*icon\.png"/', $content);
    }
   
    private function createHandler($url = 'ssl://localhost:1234/test', $options = [])
    {
        $constructorArgs = array($url, $options, $level = Logger::DEBUG, $bubble = true);
        $this->res = fopen('php://memory', 'a');
        $this->handler = $this->getMockBuilder(MattermostWebhookHandler::class)
            ->setConstructorArgs($constructorArgs)
            ->setMethods(
                array('fsockopen', 'streamSetTimeout', 'closeSocket')
            )->getMock();
        
        $reflectionProperty = new \ReflectionProperty('\Monolog\Handler\SocketHandler', 'connectionString');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->handler, 'localhost:1234');
        
        $this->handler->expects($this->any())
            ->method('fsockopen')
            ->will($this->returnValue($this->res));
        $this->handler->expects($this->any())
            ->method('streamSetTimeout')
            ->will($this->returnValue(true));
        $this->handler->expects($this->any())
            ->method('closeSocket')
            ->will($this->returnValue(true));
    
        $formatter = $this->getMockBuilder('Monolog\\Formatter\\FormatterInterface')->getMock();
        $formatter->expects($this->any())
            ->method('format')
            ->will(
                $this->returnCallback(
                    function ($record) {
                        return $record['message'];
                    }
                )
            );
    
        $this->handler->setFormatter($this->getIdentityFormatter());
    }
    
    /**
     * @return array Record
     */
    protected function getRecord($level = Logger::WARNING, $message = 'test', $context = array())
    {
        return array(
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => array(),
        );
    }
    
    /**
     * @return Monolog\Formatter\FormatterInterface
     */
    protected function getIdentityFormatter()
    {
        $formatter = $this->getMockBuilder('Monolog\\Formatter\\FormatterInterface')->getMock();
        $formatter->expects($this->any())
            ->method('format')
            ->will(
                $this->returnCallback(
                    function ($record) {
                        return $record['message'];
                    }
                )
            );
        
        return $formatter;
    }
}