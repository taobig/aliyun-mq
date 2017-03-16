<?php

namespace aiershou\aliyunmq;


class MQException extends \Exception
{

    private $_topic_name;
    private $_topic_message;
    private $_topic_response;

    public function getTopicName(): string
    {
        return $this->_topic_name;
    }

    /**
     * @return array|string
     */
    public function getTopicMessage()
    {
        return $this->_topic_message;
    }

    public function getResponse(): string
    {
        return $this->_topic_response;
    }

    /**
     * MQException constructor.
     * @param string $topic_name
     * @param array|string $topic_message
     * @param string $response
     * @param string $message
     */
    public function __construct(string $topic_name, $topic_message, string $response, string $message)
    {
        $this->_topic_name = $topic_name;
        $this->_topic_message = $topic_message;
        $this->_topic_response = $response;

        parent::__construct($message);
    }
}