<?php

    /** @noinspection PhpMissingFieldTypeInspection */
    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpIllegalPsrClassPathInspection */

    namespace Longman\TelegramBot\Commands\UserCommands;

    use Exception;
    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Entities\CallbackQuery;
    use Longman\TelegramBot\Entities\ServerResponse;
    use Longman\TelegramBot\Request;
    use IntellivoidBot;
    use TelegramClientManager\Abstracts\TelegramChatType;
    use TelegramClientManager\Objects\TelegramClient;
    use TgFileLogging;
    use VerboseAdventure\Abstracts\EventType;

    /**
     * Info command
     *
     * Allows the user to see the current information about requested user, either by
     * a reply to a message or by providing the private Telegram ID or Telegram ID
     */
    class WhoisCommand extends UserCommand
    {
        /**
         * @var string
         */
        protected $name = 'whois';

        /**
         * @var string
         */
        protected $description = 'Resolves information about the target object';

        /**
         * @var string
         */
        protected $usage = '[None]';

        /**
         * @var string
         */
        protected $version = '2.0.0';

        /**
         * @var bool
         */
        protected $private_only = false;

        /**
         * The chat/channel object of the current chat/channel
         *
         * @var TelegramClient\Chat|null
         */
        public $ChatObject = null;

        /**
         * The client of the chat/channel of the current chat/channel
         *
         * @var TelegramClient|null
         */
        public $ChatClient = null;

        /**
         * The user/bot object of the initializer (Entity that sent the message/action)
         *
         * @var TelegramClient\User|null
         */
        public $UserObject = null;

        /**
         * The user/bot client of the initializer (Entity that sent the message/action)
         *
         * @var TelegramClient|null
         */
        public $UserClient = null;

        /**
         * The direct client combination of the user initializer and the current chat/channel
         *
         * @var TelegramClient|null
         */
        public $DirectClient = null;

        /**
         * The original sender object of the forwarded content
         *
         * @var TelegramClient\User|null
         */
        public $ForwardUserObject = null;

        /**
         * The original sender client of the forwarded content
         *
         * @var TelegramClient|null
         */
        public $ForwardUserClient = null;

        /**
         * The channel origin object of the forwarded content
         *
         * @var TelegramClient\Chat|null
         */
        public $ForwardChannelObject = null;

        /**
         * The channel origin client of the forwarded content
         *
         * @var TelegramClient|null
         */
        public $ForwardChannelClient = null;

        /**
         * The target user object of the message that the reply is to
         *
         * @var TelegramClient\User|null
         */
        public $ReplyToUserObject = null;

        /**
         * The target user client of the message that the reply is to
         *
         * @var TelegramClient|null
         */
        public $ReplyToUserClient = null;

        /**
         * The original sender object of the forwarded content that this message is replying to
         *
         * @var TelegramClient\User|null
         */
        public $ReplyToUserForwardUserObject = null;

        /**
         * The original sender client of the forwarded content that this message is replying to
         *
         * @var TelegramClient|null
         */
        public $ReplyToUserForwardUserClient = null;

        /**
         * The original channel object origin of the forwarded content that this message is replying to
         *
         * @var TelegramClient\Chat|null
         */
        public $ReplyToUserForwardChannelObject = null;

        /**
         * The original channel cient origin of the forwarded content that this message is replying to
         *
         * @var TelegramClient|null
         */
        public $ReplyToUserForwardChannelClient = null;

        /**
         * Array of user mentions by UserID:ObjectType
         *
         * @var TelegramClient\User[]|null
         */
        public $MentionUserObjects = null;

        /**
         * Array of user mentions by UserID:ObjectClient
         *
         * @var TelegramClient[]|null
         */
        public $MentionUserClients = null;

        /**
         * Array of new chat members (objects) that has been added to the chat
         *
         * @var TelegramClient\User[]|null
         */
        public $NewChatMembersObjects = null;

        /**
         * Array of new chat members (clients) that has been added to the chat
         *
         * @var TelegramClient[]|null
         */
        public $NewChatMembersClients = null;

        /**
         * When enabled, the results will be sent privately and
         * the message will be deleted
         *
         * @var bool
         */
        public $PrivateMode = false;

        /**
         * The destination chat relative to the private mode
         *
         * @var TelegramClient\Chat|null
         */
        public $DestinationChat = null;

        /**
         * The message ID to reply to
         *
         * @var int|null
         */
        public $ReplyToID = null;

        /**
         * The chat of the callback query
         *
         * @var TelegramClient\Chat|null
         */
        public $CallbackQueryChatObject = null;

        /**
         * The chat client of the callback query
         *
         * @var TelegramClient|null
         */
        public $CallbackQueryChatClient = null;

        /**
         * The user of the callback query
         *
         * @var TelegramClient\User|null
         */
        public $CallbackQueryUserObject = null;

        /**
         * The user client of the callback query
         *
         * @var TelegramClient|null
         */
        public $CallbackQueryUserClient = null;

        /**
         * The client of the callback query
         *
         * @var TelegramClient|null
         */
        public $CallbackQueryClient = null;

        /**
         * Finds the callback clients
         *
         * @param CallbackQuery $callbackQuery
         */
        public function findCallbackClients(CallbackQuery $callbackQuery)
        {
            $TelegramClientManager = IntellivoidBot::getTelegramClientManager();

            if($callbackQuery !== null)
            {
                if($callbackQuery->getFrom() !== null)
                {
                    try
                    {
                        $this->CallbackQueryUserObject = TelegramClient\User::fromArray($callbackQuery->getFrom()->getRawData());
                        $this->CallbackQueryUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($this->CallbackQueryUserObject);
                    }
                    catch(Exception $e)
                    {
                        unset($e);
                    }
                }

                if($callbackQuery->getMessage() !== null)
                {
                    if($callbackQuery->getMessage()->getChat() !== null)
                    {
                        try
                        {
                            $this->CallbackQueryChatObject = TelegramClient\Chat::fromArray($callbackQuery->getMessage()->getChat()->getRawData());
                            $this->CallbackQueryChatClient = $TelegramClientManager->getTelegramClientManager()->registerChat($this->CallbackQueryChatObject);
                        }
                        catch(Exception $e)
                        {
                            unset($e);
                        }
                    }
                }

                if($this->CallbackQueryUserObject !== null && $this->CallbackQueryChatObject !== null)
                {
                    try
                    {
                        $this->CallbackQueryClient = $TelegramClientManager->getTelegramClientManager()->registerClient(
                            $this->CallbackQueryChatObject, $this->CallbackQueryUserObject
                        );
                    }
                    catch(Exception $e)
                    {
                        unset($e);
                    }
                }
            }
        }

        /**
         * Parses the request and establishes all client connections
         * @noinspection DuplicatedCode
         */
        public function findClients()
        {
            $TelegramClientManager = IntellivoidBot::getTelegramClientManager();

            $this->ChatObject = TelegramClient\Chat::fromArray($this->getMessage()->getChat()->getRawData());
            $this->UserObject = TelegramClient\User::fromArray($this->getMessage()->getFrom()->getRawData());

            // Parse the callback query
            if($this->getCallbackQuery() !== null)
            {
                if($this->getCallbackQuery()->getFrom() !== null)
                {
                    try
                    {
                        $this->CallbackQueryUserObject = TelegramClient\User::fromArray($this->getCallbackQuery()->getFrom()->getRawData());
                        $this->CallbackQueryUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($this->CallbackQueryUserObject);
                    }
                    catch(Exception $e)
                    {
                        IntellivoidBot::getLogHandler()->log(EventType::WARNING, "There was an error while trying to parse the CallbackQuery (From)", "WhoisCommand");
                        IntellivoidBot::getLogHandler()->logException($e, "WhoisCommand");
                    }
                }

                if($this->getCallbackQuery()->getMessage() !== null)
                {
                    if($this->getCallbackQuery()->getMessage()->getChat() !== null)
                    {
                        try
                        {
                            $this->CallbackQueryChatObject = TelegramClient\Chat::fromArray($this->getCallbackQuery()->getMessage()->getChat()->getRawData());
                            $this->CallbackQueryChatClient = $TelegramClientManager->getTelegramClientManager()->registerChat($this->CallbackQueryChatObject);
                        }
                        catch(Exception $e)
                        {
                            IntellivoidBot::getLogHandler()->log(EventType::WARNING, "There was an error while trying to parse the CallbackQuery (Chat)", "WhoisCommand");
                            IntellivoidBot::getLogHandler()->logException($e, "WhoisCommand");
                        }
                    }
                }

                if($this->CallbackQueryUserObject !== null && $this->CallbackQueryChatObject !== null)
                {
                    try
                    {
                        $this->CallbackQueryClient = $TelegramClientManager->getTelegramClientManager()->registerClient(
                            $this->CallbackQueryChatObject, $this->CallbackQueryUserObject
                        );
                    }
                    catch(Exception $e)
                    {
                        IntellivoidBot::getLogHandler()->log(EventType::WARNING, "There was an error while trying to parse the CallbackQuery (User)", "WhoisCommand");
                        IntellivoidBot::getLogHandler()->logException($e, "WhoisCommand");
                    }
                }
            }

            try
            {
                $this->DirectClient = $TelegramClientManager->getTelegramClientManager()->registerClient(
                    $this->ChatObject, $this->UserObject
                );
            }
            catch(Exception $e)
            {
                IntellivoidBot::getLogHandler()->log(EventType::WARNING, "There was an error while trying to register the client (Direct)", "WhoisCommand");
                IntellivoidBot::getLogHandler()->logException($e, "WhoisCommand");
            }

            // Define and update chat client
            try
            {
                $this->ChatClient = $TelegramClientManager->getTelegramClientManager()->registerChat($this->ChatObject);
            }
            catch(Exception $e)
            {
                IntellivoidBot::getLogHandler()->log(EventType::WARNING, "There was an error while trying to register the client (Chat)", "WhoisCommand");
                IntellivoidBot::getLogHandler()->logException($e, "WhoisCommand");
            }

            // Define and update user client
            try
            {
                $this->UserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($this->UserObject);
            }
            catch(Exception $e)
            {
                IntellivoidBot::getLogHandler()->log(EventType::WARNING, "There was an error while trying to register the client (User)", "WhoisCommand");
                IntellivoidBot::getLogHandler()->logException($e, "WhoisCommand");
            }

            // Define and update the forwarder if available
            try
            {
                if($this->getMessage()->getForwardFrom() !== null)
                {
                    $this->ForwardUserObject = TelegramClient\User::fromArray($this->getMessage()->getForwardFrom()->getRawData());
                    $this->ForwardUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($this->ForwardUserObject);
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::forward_from_user.bin</code>"
                ]);
            }

            // Define and update the channel forwarder if available
            try
            {
                if($this->getMessage()->getForwardFromChat() !== null)
                {
                    $this->ForwardChannelObject = TelegramClient\Chat::fromArray($this->getMessage()->getForwardFromChat()->getRawData());
                    $this->ForwardChannelClient = $TelegramClientManager->getTelegramClientManager()->registerChat($this->ForwardChannelObject);
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::forward_from_channel.bin</code>"
                ]);
            }

            try
            {
                if($this->getMessage()->getReplyToMessage() !== null)
                {
                    if($this->getMessage()->getReplyToMessage()->getFrom() !== null)
                    {
                        $this->ReplyToUserObject = TelegramClient\User::fromArray($this->getMessage()->getReplyToMessage()->getFrom()->getRawData());
                        $this->ReplyToUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($this->ReplyToUserObject);
                    }
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::reply_to_user.bin</code>"
                ]);
            }

            try
            {
                if($this->getMessage()->getReplyToMessage() !== null)
                {
                    if($this->getMessage()->getReplyToMessage()->getForwardFrom() !== null)
                    {
                        $this->ReplyToUserForwardChannelObject = TelegramClient\User::fromArray($this->getMessage()->getReplyToMessage()->getForwardFrom()->getRawData());
                        $this->ReplyToUserForwardChannelClient = $TelegramClientManager->getTelegramClientManager()->registerUser($this->ReplyToUserForwardChannelObject);
                    }
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::reply_to_user_forwarder_channel.bin</code>"
                ]);
            }

            try
            {
                if($this->getMessage()->getReplyToMessage() !== null)
                {
                    if($this->getMessage()->getReplyToMessage()->getForwardFromChat() !== null)
                    {
                        $this->ReplyToUserForwardChannelObject = TelegramClient\Chat::fromArray($this->getMessage()->getReplyToMessage()->getForwardFromChat()->getRawData());
                        $this->ReplyToUserForwardChannelClient = $TelegramClientManager->getTelegramClientManager()->registerChat($this->ReplyToUserForwardChannelObject);
                    }
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::reply_to_user_forwarder_user.bin</code>"
                ]);
            }

            try
            {
                $this->MentionUserObjects = array();
                $this->MentionUserClients = array();

                // The message in general
                if($this->getMessage()->getEntities() !== null)
                {
                    foreach($this->getMessage()->getEntities() as $messageEntity)
                    {
                        /** @noinspection DuplicatedCode */
                        if($messageEntity->getUser() !== null)
                        {
                            $MentionUserObject = TelegramClient\User::fromArray($messageEntity->getUser()->getRawData());
                            /** @noinspection DuplicatedCode */
                            $MentionUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($MentionUserObject);
                            $this->MentionUserObjects[$MentionUserObject->ID] = $MentionUserObject;
                            $this->MentionUserClients[$MentionUserObject->ID] = $MentionUserClient;
                        }
                    }
                }

                // If the reply contains mentions
                if($this->getMessage()->getReplyToMessage() !== null)
                {
                    if($this->getMessage()->getReplyToMessage()->getEntities() !== null)
                    {
                        foreach($this->getMessage()->getReplyToMessage()->getEntities() as $messageEntity)
                        {
                            /** @noinspection DuplicatedCode */
                            if($messageEntity->getUser() !== null)
                            {
                                $MentionUserObject = TelegramClient\User::fromArray($messageEntity->getUser()->getRawData());
                                if(isset($this->MentionUserObjects[$MentionUserObject->ID]) == false)
                                {
                                    /** @noinspection DuplicatedCode */
                                    $MentionUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($MentionUserObject);
                                    $this->MentionUserObjects[$MentionUserObject->ID] = $MentionUserObject;
                                    $this->MentionUserClients[$MentionUserObject->ID] = $MentionUserClient;
                                }
                            }
                        }
                    }
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::mentions.bin</code>"
                ]);
            }

            try
            {
                $this->NewChatMembersObjects = array();
                $this->NewChatMembersClients = array();

                // The message in general
                if($this->getMessage()->getNewChatMembers() !== null)
                {
                    foreach($this->getMessage()->getNewChatMembers() as $chatMember)
                    {
                        /** @noinspection DuplicatedCode */
                        if($chatMember->getUser() !== null)
                        {
                            $NewUserObject = TelegramClient\User::fromArray($chatMember->getUser()->getRawData());
                            /** @noinspection DuplicatedCode */
                            $NewUserClient = $TelegramClientManager->getTelegramClientManager()->registerUser($NewUserObject);
                            $this->NewChatMembersObjects[$NewUserObject->ID] = $NewUserObject;
                            $this->NewChatMembersClients[$NewUserObject->ID] = $NewUserClient;
                        }
                    }
                }
            }
            catch(Exception $e)
            {
                $ReferenceID = IntellivoidBot::getLogHandler()->logException($e, "Worker");
                /** @noinspection PhpUnhandledExceptionInspection */
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "reply_to_message_id" => $this->getMessage()->getMessageId(),
                    "parse_mode" => "html",
                    "text" =>
                        "Oops! Something went wrong! contact someone in @IntellivoidDiscussions\n\n" .
                        "Error Code: <code>" . $ReferenceID . "</code>\n" .
                        "Object: <code>Events/generic_request::mentions.bin</code>"
                ]);
            }

            return $this;
        }

        /**
         * Attempts to find the target user that the reply/message is referring to
         *
         * @param bool $reply_only If enabled, the target user can refer to the user of that sent the message
         * @return TelegramClient|null
         */
        public function findTarget(bool $reply_only=true): ?TelegramClient
        {
            if($this->ReplyToUserClient !== null)
            {
                return $this->ReplyToUserClient;
            }

            if($this->MentionUserClients !== null)
            {
                if(count($this->MentionUserClients) > 0)
                {
                    return $this->MentionUserClients[array_keys($this->MentionUserClients)[0]];
                }
            }

            if($reply_only == false)
            {
                if($this->UserClient !== null)
                {
                    return $this->UserClient;
                }
            }

            return null;
        }

        /**
         * Finds the original target of a forwarded message
         *
         * @param bool $reply_only If enabled, the target user can refer to the user of that sent the message
         * @return TelegramClient|null
         */
        public function findForwardedTarget(bool $reply_only=true)
        {
            if($this->ReplyToUserForwardUserClient !== null)
            {
                return $this->ReplyToUserForwardUserClient;
            }

            if($this->ReplyToUserForwardChannelClient !== null)
            {
                return $this->ReplyToUserForwardChannelClient;
            }

            if($reply_only == false)
            {
                if($this->ForwardUserClient !== null)
                {
                    return $this->ForwardUserClient;
                }

                if($this->ForwardChannelClient !== null)
                {
                    return $this->ForwardChannelClient;
                }
            }

            return null;
        }

        /**
         * Generates a HTML mention
         *
         * @param TelegramClient $client
         * @return string
         */
        public static function generateMention(TelegramClient $client)
        {
            switch($client->Chat->Type)
            {
                case TelegramChatType::Private:
                    /** @noinspection DuplicatedCode */
                    if($client->User->Username == null)
                    {
                        if($client->User->LastName == null)
                        {
                            return "<a href=\"tg://user?id=" . $client->User->ID . "\">" . self::escapeHTML($client->User->FirstName) . "</a>";
                        }
                        else
                        {
                            return "<a href=\"tg://user?id=" . $client->User->ID . "\">" . self::escapeHTML($client->User->FirstName . " " . $client->User->LastName) . "</a>";
                        }
                    }
                    else
                    {
                        return "@" . $client->User->Username;
                    }

                case TelegramChatType::SuperGroup:
                case TelegramChatType::Group:
                case TelegramChatType::Channel:
                    /** @noinspection DuplicatedCode */
                    if($client->Chat->Username == null)
                    {
                        if($client->Chat->Title !== null)
                        {
                            return "<a href=\"tg://user?id=" . $client->User->ID . "\">" . self::escapeHTML($client->Chat->Title) . "</a>";
                        }
                    }
                    else
                    {
                        return "@" . $client->Chat->Username;
                    }

                    break;

                default:
                    return "<a href=\"tg://user?id=" . $client->Chat->ID . "\">Unknown</a>";
            }

            return "Unknown";
        }

        /**
         * Command execute method
         *
         * @return ServerResponse
         * @noinspection DuplicatedCode
         */
        public function execute(): ?ServerResponse
        {
           return null;
        }

        /**
         * Escapes problematic characters for HTML content
         *
         * @param string $input
         * @return string
         */
        private static function escapeHTML(string $input): string
        {
            $input = str_ireplace("<", "&lt;", $input);
            $input = str_ireplace(">", "&gt;", $input);
            $input = str_ireplace("&", "&amp;", $input);

            return $input;
        }
    }