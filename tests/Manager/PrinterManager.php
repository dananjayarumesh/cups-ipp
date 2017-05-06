<?php

namespace Smalot\Cups\Tests\Units\Manager;

use mageekguy\atoum;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Model\PrinterInterface;
use Smalot\Cups\Transport\Client;

/**
 * Class PrinterManager
 *
 * @package Smalot\Cups\Tests\Units\Manager
 */
class PrinterManager extends atoum\test
{
    protected $printerUri = 'ipp://localhost:631/printers/PDF';

    public function testPrinterManager()
    {
        $builder = new Builder();
        $client = Client::create();

        $printerManager = new \Smalot\Cups\Manager\PrinterManager($builder, $client);
        $printerManager->setCharset('utf-8');
        $printerManager->setLanguage('fr-fr');
        $printerManager->setOperationId(5);
        $printerManager->setUsername('testuser');

        $this->string($printerManager->getCharset())->isEqualTo('utf-8');
        $this->string($printerManager->getLanguage())->isEqualTo('fr-fr');
        $this->integer($printerManager->getOperationId())->isEqualTo(5);
        $this->string($printerManager->getUsername())->isEqualTo('testuser');

        $this->integer($printerManager->getOperationId('current'))->isEqualTo(5);
        $this->integer($printerManager->getOperationId('new'))->isEqualTo(6);
    }

    public function testGetList()
    {
        $builder = new Builder();
        $client = Client::create();

        $printerManager = new \Smalot\Cups\Manager\PrinterManager($builder, $client);
        $printers = $printerManager->getList();

        $this->array($printers)->size->isGreaterThanOrEqualTo(1);

        $found = false;
        foreach ($printers as $printer) {
            if ($printer->getName() == 'PDF') {
                $found = true;

                $this->string($printer->getName())->isEqualTo('PDF');
                $this->string($printer->getUri())->isEqualTo($this->printerUri);
//                $this->string($printer->getStatus())->isEqualTo('idle');
                break;
            }
        }

        $this->boolean($found)->isEqualTo(true);
    }

    public function testPauseResume()
    {
        $user = getenv('USER');
        $password = getenv('PASS');

        $builder = new Builder();
        $client = Client::create();
        $client->setAuthentication($user, $password);
        $printerManager = new \Smalot\Cups\Manager\PrinterManager($builder, $client);

        $printer = new Printer();
        $printer->setUri($this->printerUri);

        // Reset status
        $printerManager->resume($printer);

        // Pause printer and check status
        $done = $printerManager->pause($printer);
        $this->boolean($done)->isEqualTo(true);
        $this->string($printer->getStatus())->isEqualTo('stopped');

        // Reset status and check status
        $done = $printerManager->resume($printer);
        $this->boolean($done)->isEqualTo(true);
        $this->string($printer->getStatus())->isEqualTo('idle');
    }

    public function testPurge()
    {
        return;

        $user = getenv('USER');
        $password = getenv('PASS');

        $builder = new Builder();
        $client = Client::create();
        $client->setAuthentication($user, $password);
        $printerManager = new \Smalot\Cups\Manager\PrinterManager($builder, $client);

        $printer = new Printer();
        $printer->setUri($this->printerUri);

        // Reset status
        $done = $printerManager->purge($printer);
        $this->boolean($done)->isEqualTo(true);
    }

    public function testGetDefault()
    {
        $builder = new Builder();
        $client = Client::create();
        $printerManager = new \Smalot\Cups\Manager\PrinterManager($builder, $client);

        // Reset status
        $printer = $printerManager->getDefault();
        $this->object($printer)->isInstanceOf('\Smalot\Cups\Model\Printer');
        $this->string($printer->getUri())->isEqualTo($this->printerUri);
//        $this->string($printer->getStatus())->isEqualTo('idle');
    }
}
