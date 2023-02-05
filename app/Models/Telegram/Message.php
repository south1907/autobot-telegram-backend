<?php


namespace App\Models\Telegram;


class Message
{
    private $messageId;

    private $userId;

    private $firstName;

    private $lastName;

    private $username;

    private $text;

    private $type;

    private $sourceId;

    private $migrateToChatId;

    private $replyToUserId;

    private $replyToUserFullname;

    private $sourceTitle;

    private $arrMemberChange;

    private $command;

    private $arrUrl = [];

    private $isMedia = false;

    // type of message: COMMENT, FORWARD, IMAGE, AUDIO, GIF, VIDEO, STICKER, FILE
    private $typeMessage;

    private $stickerPackage;

    private $forwardFrom;

    // command of owner bot
    private $isSelfCommand = false;

    // some command fixed form bot
    private $arrCommandBot = ['/setup', '/mute'];

    public function __construct($body)
    {
        if (array_key_exists('message', $body)) {
            $this->userId = $body['message']['from']['id'];
            if (array_key_exists('first_name', $body['message']['from'])) {
                $this->firstName = $body['message']['from']['first_name'];
            }
            if (array_key_exists('last_name', $body['message']['from'])) {
                $this->lastName = $body['message']['from']['last_name'];
            }
            if (array_key_exists('username', $body['message']['from'])) {
                $this->username = $body['message']['from']['username'];
            }
            $type = $body['message']['chat']['type'];
            if ($type == 'private') {
                // chat only bot
                $type = 'WITH_BOT';
            } else {
                // chat in group
                $type = 'WITH_GROUP';
            }
            $this->type = $type;
            $this->sourceId = $body['message']['chat']['id'];
            if (array_key_exists('title', $body['message']['chat'])) {
                $this->sourceTitle = $body['message']['chat']['title'];
            }
            if (array_key_exists('new_chat_participant', $body['message'])) {
                $this->type = 'MEMBER_JOIN';
                $this->arrMemberChange = $body['message']['new_chat_members'];
            }
            if (array_key_exists('left_chat_participant', $body['message'])) {
                $this->type = 'MEMBER_LEFT';
                $mem = $body['message']['left_chat_member'];
                $this->arrMemberChange[] = $mem;
            }

            if (array_key_exists('caption', $body['message'])) {
                // set text = caption when is media
                $this->text = $body['message']['caption'];
            }
            if (array_key_exists('message_id', $body['message'])) {
                // set message_id
                $this->messageId = $body['message']['message_id'];
            }

            if (array_key_exists('text', $body['message'])) {
                $this->text = $body['message']['text'];
                $this->typeMessage = 'COMMENT';
            }

            if (array_key_exists('reply_to_message', $body['message'])) {
                $this->replyToUserId = $body['message']['reply_to_message']['from']['id'];
                $firstname = '';
                $lastname = '';
                if (array_key_exists('last_name', $body['message']['reply_to_message']['from'])) {
                    $lastname = $body['message']['reply_to_message']['from']['last_name'];
                }
                if (array_key_exists('first_name', $body['message']['reply_to_message']['from'])) {
                    $firstname = $body['message']['reply_to_message']['from']['first_name'];
                }
                $this->replyToUserFullname = trim($firstname . ' ' . $lastname);
            }

            if (array_key_exists('entities', $body['message'])) {
                $firstEntity = $body['message']['entities'][0];
                if ($firstEntity['offset'] == 0 && $firstEntity['type'] == 'bot_command') {
                    $stringCommand = substr($this->text, 0, $firstEntity['length']);
                    if (strpos($stringCommand, '@')) {
                        $splitCommand = explode('@', $stringCommand);
                        $usernameBotSystem = env('BOT_USERNAME');
                        $this->command = $splitCommand[0];
                        if ($splitCommand[1] == $usernameBotSystem) {
                            $this->isSelfCommand = true;
                        }
                    } else {
                        $this->command = $stringCommand;
                        if (in_array($this->command, $this->arrCommandBot)) {
                            $this->isSelfCommand = true;
                        }
                    }
                }

                // list url
                $listEntity = $body['message']['entities'];
                foreach ($listEntity as $en) {
                    if ($en['type'] == 'url') {
                        $this->arrUrl[] = mb_substr($this->text, $en['offset'], $en['length']);
                    }
                }
            }
            if (array_key_exists('caption_entities', $body['message'])) {
                // list url
                $listEntity = $body['message']['caption_entities'];
                foreach ($listEntity as $en) {
                    if ($en['type'] == 'url') {
                        $this->arrUrl[] = mb_substr($this->text, $en['offset'], $en['length']);
                    }
                }
            }

            if (array_key_exists('photo', $body['message']) || array_key_exists('document', $body['message']) ||
                array_key_exists('sticker', $body['message']) || array_key_exists('video', $body['message']) ||
                array_key_exists('audio', $body['message']) || array_key_exists('voice', $body['message']) ||
                array_key_exists('animation', $body['message'])) {
                // IMAGE, AUDIO, GIF, VIDEO, STICKER, FILE
                if (array_key_exists('document', $body['message'])) {
                    $this->typeMessage = 'FILE';
                }
                if (array_key_exists('animation', $body['message'])) {
                    $this->typeMessage = 'GIF';
                }
                if (array_key_exists('video', $body['message'])) {
                    $this->typeMessage = 'VIDEO';
                }
                if (array_key_exists('audio', $body['message']) || array_key_exists('voice', $body['message'])) {
                    $this->typeMessage = 'AUDIO';
                }
                if (array_key_exists('sticker', $body['message'])) {
                    $this->typeMessage = 'STICKER';
                    $this->stickerPackage = $body['message']['sticker']['set_name'];
                }
                if (array_key_exists('photo', $body['message'])) {
                    $this->typeMessage = 'IMAGE';
                }
                $this->isMedia = true;
            }

            // type FORWARD
            if (array_key_exists('forward_from', $body['message'])) {
                $this->typeMessage = 'FORWARD';
                $this->forwardFrom = $body['message']['forward_from'];
            }
            if (array_key_exists('forward_from_chat', $body['message'])) {
                $this->typeMessage = 'FORWARD';
                $this->forwardFrom = $body['message']['forward_from_chat'];
            }
            if (array_key_exists('migrate_to_chat_id', $body['message'])) {
                $this->typeMessage = 'MIGRATE_SUPER_GROUP';
                $this->migrateToChatId = $body['message']['migrate_to_chat_id'];
            }

        }
    }

    public function checkNull() {
        if ($this->userId) {
            return false;
        }
        return true;
    }

    public function checkNullTypeMessage() {
        if ($this->type == 'MEMBER_LEFT' || $this->type == 'MEMBER_JOIN' || $this->type == 'MIGRATE_SUPER_GROUP') {
            return false;
        }
        if ($this->typeMessage) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @return mixed
     */
    public function getMigrateToChatId()
    {
        return $this->migrateToChatId;
    }

    /**
     * @return mixed
     */
    public function getReplyToUserId()
    {
        return $this->replyToUserId;
    }

    /**
     * @return string
     */
    public function getReplyToUserFullname()
    {
        return $this->replyToUserFullname;
    }

    /**
     * @return mixed
     */
    public function getSourceTitle()
    {
        return $this->sourceTitle;
    }

    /**
     * @return string
     */
    public function getTypeMessage()
    {
        return $this->typeMessage;
    }

    /**
     * @return mixed
     */
    public function getStickerPackage()
    {
        return $this->stickerPackage;
    }

    /**
     * @return mixed
     */
    public function getForwardFrom()
    {
        return $this->forwardFrom;
    }

    /**
     * @return mixed
     */
    public function getArrMemberChange()
    {
        return $this->arrMemberChange;
    }

    /**
     * @return array
     */
    public function getArrUrl(): array
    {
        return $this->arrUrl;
    }

    /**
     * @return false|mixed|string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return bool
     */
    public function isSelfCommand(): bool
    {
        return $this->isSelfCommand;
    }

    /**
     * @return bool
     */
    public function isMedia(): bool
    {
        return $this->isMedia;
    }

}
