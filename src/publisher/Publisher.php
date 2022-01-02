<?php

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
require 'vendor/autoload.php';

class Publisher 
{
    private $server;
    private $port = 1883;
    private $username;
    private $password;
    private $message_type;
    private $metadata;
    private $job_id;
    private $success_time;
    private $user;
    private $application;
    private $connection_settings;
    private $clean_session = false;
    private $mqtt_client;

    public function __construct($server, $username, $password, $metadata, $job_id, $success_time, $user, $application, $message_type = null)
    {
        $this->server = $server;
        $this->username = $username;
        $this->password = $password;
        $this->message_type = $message_type;
        $this->metadata = $metadata;
        $this->job_id = $job_id;
        $this->success_time = $success_time;
        $this->user = $user;
        $this->application = $application;
        $this->connection_settings = new ConnectionSettings();
        $this->connection_settings
            ->setUsername($this->username)
            ->setPassword($this->password)
            ->setKeepAliveInterval(120);
//            ->setLastWillTopic('emqx/test/last-will')
//            ->setLastWillMessage('client disconnect')
//            ->setLastWillQualityOfService(1);
        $this->mqtt_client = self::createClient();
    }
    public function __destruct()
    {
        try {
            $this->mqtt_client->disconnect();
        }catch (\Exception $e){
            Log::error($e->getMessage());
        }
    }

    private function createClient()
    {
        try {
            $clientId = rand(5, 15);
            $port = env('MQTT_PORT') ? env('MQTT_PORT') : $this->port;
            $client = new MqttClient($this->server, $port, $clientId);
            $client->connect($this->connection_settings, $this->clean_session);
            return $client;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    public function send()
    {

        try {
            //  dd($mqtt);
            $payload = self::createPayload();
            $this->mqtt_client->publish(
            // topic
                $this->user . '/' . $this->application,
                // payload
                json_encode($payload),
                // qos
                1,
                // retain
                true
            );
            // With a QoS level to 1 set on the message the client will receive acknowledgments from Solace messaging when it has successfully stored the message.
            printf("msg send\n");
            return $payload;
        } catch (MqttClientException $e) {
            Log::info('notification not sent: ' . $e->getMessage());
        }
    }


    private function createPayload()
    {
        return [
            'date' => $this->success_time,
            'type' => $this->message_type,
            'job_id' => $this->job_id,
            'metadata' => $this->metadata,
        ];
    }
}

