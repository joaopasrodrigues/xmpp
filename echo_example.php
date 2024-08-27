<?php

require 'vendor/autoload.php';
error_reporting(1);

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Fabiang\Xmpp\Options;
use Fabiang\Xmpp\Client;

use Fabiang\Xmpp\Protocol\Roster;
use Fabiang\Xmpp\Protocol\Presence;
use Fabiang\Xmpp\Protocol\Message;

$hostname       = 'jabber.example.com';
$port           = 5222;
$connectionType = 'tcp';
$address        = "$connectionType://$hostname:$port";
$username = 'my_jid';
$password = 'my_password';
$to = 'user@jabber.example.com';
$debugActive = true;


try
{

        $options = new Options($address);
        
        if ($debugActive){
            $logger = new Logger('xmpp');
            $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
            $options->setLogger($logger);         
        }
        
        $options->setUsername($username)->setPassword($password);

    

        $client = new Client($options);
        $client->connect();

        //Attach events
        
        //fails because 
        //<stream:stream xmlns:stream="http://etherx.jabber.org/streams" xmlns="jabber:client" ...
        //gets picked up in  startXml() @ XMLStream.php as "jabber:client"
        $client->getConnection()->getInputStream()->getEventManager()->attach('{http://etherx.jabber.org/streams}message', function($e) use ($client) {
                          echo "\n('{http://etherx.jabber.org/streams}message') ----->" . $e->getName();

        });; 

        // but this works !!!
        $client->getConnection()->getInputStream()->getEventManager()->attach('{jabber:client}message', function($e) use ($client) {
            echo "\n('{jabber:client}message') ----->" . $e->getName();

        });; 
        

        //attach to all - debug only
        /*
        $client->getConnection()->getInputStream()->getEventManager()->attach('*', function($e) use ($client) {
           echo "\n(*)>>>>" . $e->getName();
        });; 
        */
        
        $client->send(new Roster);
        $client->send(new Presence);


        // send a message to another user
        $message = new Message;
        $message->setMessage('Echo Bot '. date("c"))->setTo($to);
        $client->send($message);






 


        while (true) {
            $input = $client->getConnection()->receive(); // receives input data
            $message = $client->getConnection()->getInputStream()->parse($input);

            //echo "\n Msg = \n".$message->saveXML();

            // $message now is DOMDocument - do something with message
            $elements = $message->getElementsByTagName("body");
            if (count($elements)){
                 foreach ($elements as $res)
                 {
                     $textReceived = $res->nodeValue; 
                     //all done - break
                     break;

                 }
                        
                 // send a message to another user
                $message = new Message;
                $message->setMessage('You Sent: '. $textReceived)->setTo($to);
                $client->send($message);

            }
        }


        $client->disconnect();



}
catch (Exception $ex) {
            print $ex->getMessage().PHP_EOL;
            print $ex->getTraceAsString();
        }    
