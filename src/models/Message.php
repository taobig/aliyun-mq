<?php

namespace aiershou\aliyunmq\models;


class Message
{
    /**
     * @var string
     */
    public $msgId;
    /**
     * @var string
     */
    public $tag;
    /**
     * @var string
     */
    public $body;
    /**
     * @vars tring
     */
    public $key;
    /**
     * @var string
     */
    public $msgHandle;
    /**
     * @var string
     */
    public $bornTime;
    /**
     * @var int
     */
    public $reconsumeTimes;
}