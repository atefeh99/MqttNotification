<?php

namespace MqttNotification;

use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class Publisher
{
    private $server;
    private $port;
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

    public function __construct($metadata, $job_id, $success_time, $user, $application, $message_type = null, $server = null, $username = null, $password = null,$port=null)
    {
        if ($server) {
            $this->server = $server;
        } else {
            $this->server = env('MQTT_HOST', 'localhost');
        }
        if ($username) {
            $this->username = $username;
        } else {
            $this->username = env('MQTT_USERNAME', 'guest');
        }
        if ($password) {
            $this->password = $password;
        } else {
            $this->password = env('MQTT_PASSWORD', 'guest');
        }
        if ($port) {
            $this->port = $port;
        } else {
            $this->port = env('MQTT_PORT', '1883');
        }
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
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function createClient()
    {
        try {
            $clientId = rand(5, 15);
            $client = new MqttClient($this->server, $this->port, $clientId);
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

