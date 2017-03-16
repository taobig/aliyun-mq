<?php

namespace aiershou\aliyunmq;


class MQException extends \Exception
{

    private $_topic_name;
    private $_topic_message;

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

    /**
     * @param string $message
     * @param string $topic_name
     * @param string|array $topic_message
     */
    public function __construct(string $topic_name, $topic_message = '', string $message)
    {
        $this->_topic_name = $topic_name;
        $this->_topic_message = $topic_message;

        parent::__construct($message);
    }
}