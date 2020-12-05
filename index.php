<?php

use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Exception;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\RPCErrorException;

set_time_limit(0);
if (!\file_exists('madeline.php')) {
    \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

class MyEventHandler extends EventHandler
{

    const ADMIN = "777000";

    public function report($message, $extra = array())
    {
        try {
            $this->messages->sendMessage([
                'peer' => self::ADMIN,
                'message' => $message
            ]);
        } catch (\Throwable $e) {
            $this->logger("While reporting: $e", Logger::FATAL_ERROR);
        }
    }

    public function onUpdateNewChannelMessage(array $update): \Generator
    {
        return $this->onUpdateNewMessage($update);
    }
    public function onUpdateEditMessage(array $update): \Generator
    {
        return $this->onUpdateNewMessage($update);
    }

    public function onUpdateNewMessage(array $update): \Generator
    {
        @$peer = yield $this->getInfo($update);
        @$chat_id = $peer['bot_api_id'];
        @$text = $update['message']['message'];
        @$message_id = $update['message']['id'];
        if (!$update['message']['out'])
            try {
                @$id = yield $this->get_id($update);
                @$fwd_id = $update['message']['fwd_from'];
                if (isset($fwd_id) && isset($fwd_id['from_id']))
                    @$id = $fwd_id['from_id'];
                elseif (isset($fwd_id) && !isset($fwd_id['from_id']))
                    return yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'FoRwArD LoCkEd', 'reply_to_msg_id' => $message_id]);
                elseif (is_numeric(@$text) or strpos(@$text, '@') !== false)
                    @$id = yield $this->getInfo($text)['User']['id'];
                if ($id)
                    yield $this->messages->sendMessage(['peer' => $chat_id, 'message' => 'Id : `' . $id . '`', 'reply_to_msg_id' => $message_id, 'parse_mode' => 'markdown']);
            } catch (RPCErrorException $e) {
                $this->report("Surfaced:$e");
            } catch (Exception $e) {
                if (\stripos($e->getMessage(), 'invalid constructor given') === false) {
                    $this->report("Surfaced:$e");
                }
            }
    }
}
foreach (array_keys(get_defined_vars()) as $strVarName)
    unset(${$strVarName});
$settings = [
    'logger' => [
        'logger_level' => \danog\MadelineProto\Logger::ERROR
    ],
    'serialization' => [
        'serialization_interval' => 30
    ],
    'connection_settings' => [
        'media_socket_count' => [
            'min' => 5,
            'max' => 1000
        ]
    ],
    'upload' => [
        'allow_automatic_upload' => false,
        'parallel_chunks' => 3
    ],
    'download' => [
        'parallel_chunks' => 3
    ],
    'app_info' => [
        'api_id' => '107227',
        'api_hash' => 'b72fabac8d841cb4fd7d4ddfae4664b5'

    ]
];
$MadelineProto = new API('Safa.madeline', $settings);
$MadelineProto->startAndLoop(MyEventHandler::class);
