<?php namespace CodeIgniter\Log\Handlers;

use Config\MockLogger as LoggerConfig;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class FileHandlerTest extends \CIUnitTestCase
{

	public function setUp()
	{
		$this->root = vfsStream::setup('root');
		$this->start = $this->root->url() . '/';
	}

	//--------------------------------------------------------------------

	public function testBasicHandle()
	{
		$config = new LoggerConfig();
		$config->path = $this->start . 'charlie/';
		$config->handlers['CodeIgniter\Log\Handlers\TestHandler']['handles'] = ['critical'];
		$logger = new TestHandler($config->handlers['CodeIgniter\Log\Handlers\TestHandler']);
//		$logger = new FileHandler($config->handlers['CodeIgniter\Log\Handlers\TestHandler']);
		$logger->setDateFormat("Y-m-d H:i:s:u");
		$this->assertTrue($logger->handle("warning", "This is a test log"));
	}

	public function testHandleExpectedFile()
	{
		$config = new LoggerConfig();
		$config->path = $this->start . 'charlie/';
		$logger = new MockFileHandler((array) $config);

		$logger->setDateFormat("Y-m-d H:i:s:u");
		$logger->handle("warning", "This is a test log");

		//are we in agreement re destination of log file?
		$expected = 'log-' . date('Y-m-d') . '.php';
		$this->assertEquals($config->path . $expected, $logger->destination);
	}

	public function testHandleCreateFile()
	{
		$config = new LoggerConfig();
		$config->path = $this->start;
		$logger = new MockFileHandler((array) $config);

		$logger->setDateFormat("Y-m-d H:i:s:u");
		$logger->handle("warning", "This is a test log");

		$expected = 'log-' . date('Y-m-d') . '.php';
		$fp = fopen($config->path . $expected, 'r');
		$line = fgets($fp);
		fclose($fp);

		// did the log file get created?
		$expectedResult = "<?php defined('BASEPATH') || exit('No direct script access allowed'); ?>\n";
		$this->assertEquals($expectedResult, $line);
	}

	public function testHandleDateTimeCorrectly()
	{
		$config = new LoggerConfig();
		$config->path = $this->start;
		$logger = new MockFileHandler((array) $config);

		$logger->setDateFormat('Y-m-d');
		$expected = 'log-' . date('Y-m-d') . '.php';

		$logger->handle('debug', 'Test message');

		$fp = fopen($config->path . $expected, 'r');
		$line = fgets($fp); // skip opening PHP tag
		$line = fgets($fp); // skip blank line
		$line = fgets($fp); // and get the second line
		fclose($fp);

		$expectedResult = 'DEBUG - ' . date('Y-m-d') . ' --> Test message';
		$this->assertEquals($expectedResult, substr($line,0,strlen($expectedResult)));
		
	}

}
