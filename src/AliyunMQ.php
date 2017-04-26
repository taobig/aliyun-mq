<?php

namespace aiershou\aliyunmq;

use aiershou\aliyunmq\models\Message;

/**
 *
 * @link  https://help.aliyun.com/document_detail/29573.html
 *
 */
class AliyunMQ
{
    /**
     * @var string  the MQ's base http url
     */
    protected $base_url;
    /**
     * @var string
     */
    protected $access_key;
    /**
     * @var string
     */
    protected $secret_key;


    public function __construct(string $url, string $accessKey, String $secretKey)
    {
        $this->base_url = $url;
        $this->access_key = $accessKey;
        $this->secret_key = $secretKey;
    }

    //计算签名
    private function sign(string $str, string $key): string
    {
        return base64_encode(hash_hmac("sha1", $str, $key, true));
    }

    //计算时间戳
    private static function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    public function produce(string $topic, string $producerId, string $body, string $tag = "http", string $key = "http", $curlOptions = [])
    {
        $max_try_times = 3;
        $try_times = 0;
        while (true) {
            $date = time() * 1000;
            $newline = "\n";
            //签名字符串
            $signString = $topic . $newline . $producerId . $newline . md5($body) . $newline . $date;
            //计算签名
            $signature = $this->sign($signString, $this->secret_key);
            $headers = [
                "Signature: " . $signature,//构造签名标记
                "AccessKey: " . $this->access_key,//构造密钥标记
                "ProducerID: " . $producerId,
                "Content-Type: text/html;charset=UTF-8",
            ];

            $url = sprintf("%s/message/?topic=%s&time=%d&tag=%s&key=%s", $this->base_url, $topic, $date, $tag, $key);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, x);  //The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
//            curl_setopt($ch, CURLOPT_TIMEOUT, x);
//            CURLOPT_TIMEOUT_MS
            if ($curlOptions) {
                foreach ($curlOptions as $option => $val) {
                    curl_setopt($ch, $option, $val);
                }
            }
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

            try {
                $result = curl_exec($ch);
                $errno = curl_errno($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (!$errno) {
                    //{"code":"SC_BAD_REQUEST","info":"parameter:Signature is invalid,can not be null or empty"}
                    //{"msgId":"0A97CB496DE1137A9034915421F297A7","sendStatus":"SEND_OK"}
                    $o = json_decode($result, true);
                    if (isset($o['sendStatus']) && $o['sendStatus'] == 'SEND_OK') {
                        return $o['msgId'];
                    }
                }
                if ($try_times < $max_try_times) {
                    ++$try_times;
                    continue;
                }
                throw new MQException($topic, $body, $result, "发送消息失败 ! {$http_code}");
            } finally {
                curl_close($ch);
            }
        }
    }

    /**
     * @param string $topic
     * @param string $consumerId
     * @return Message[]
     * @throws MQException
     */
    public function consume(string $topic, string $consumerId)
    {
        $date = time() * 1000;
        $newline = "\n";
        //签名字符串
        $signString = $topic . $newline . $consumerId . $newline . $date;
        //计算签名
        $signature = $this->sign($signString, $this->secret_key);
        $headers = [
            "Signature: " . $signature,//构造签名标记
            "AccessKey: " . $this->access_key,//构造密钥标记
            "ConsumerID: " . $consumerId,
            "Content-Type: text/html;charset=UTF-8",
        ];

        $url = sprintf("%s/message/?topic=%s&time=%d&num=32", $this->base_url, $topic, $date);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        try {
            $result = curl_exec($ch);
            $errno = curl_errno($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (!$errno) {
                $messages = [];
                $response = json_decode($result, true);
                foreach ($response as $item) {
                    $msg = new Message();
                    $msg->msgId = $item['msgId'];
                    $msg->tag = $item['tag'];
                    $msg->key = $item['key'];
                    $msg->body = $item['body'];
                    $msg->bornTime = $item['bornTime'];
                    $msg->msgHandle = $item['msgHandle'];
                    $msg->reconsumeTimes = $item['reconsumeTimes'];

                    $messages[] = $msg;
                }
                return $messages;
            }
            throw new MQException($topic, '', $result, "消费消息失败 ! {$http_code}");
        } finally {
            curl_close($ch);
        }
    }

    public function delete(string $topic, string $consumerId, string $messageHandle)
    {
        $newline = "\n";
        //获取时间戳
        $date = (int)($this->microtime_float() * 1000);
        //构造删除Topic消息URL
        $url = sprintf("%s/message/?msgHandle=%s&topic=%s&time=%d", $this->base_url, $messageHandle, $topic, $date);
        //签名字符串
        $signString = $topic . $newline . $consumerId . $newline . $messageHandle . $newline . $date;
        //计算签名
        $signature = $this->sign($signString, $this->secret_key);
        //构造HTTP请求头部信息
        $headers = [
            "Signature: " . $signature,//构造签名标记
            "AccessKey: " . $this->access_key,//构造密钥标记
            "ConsumerID: " . $consumerId,
            "Content-Type: text/html;charset=UTF-8",
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        try {
            $result = curl_exec($ch);
            $errno = curl_errno($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (!$errno) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 204) {//request success
                    return;
                }
            }
            throw new MQException($topic, '', $result, "删除消息失败 ! {$http_code}");
        } finally {
            curl_close($ch);
        }
    }

}