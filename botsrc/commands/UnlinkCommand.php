<?php

    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpIllegalPsrClassPathInspection */

    namespace Longman\TelegramBot\Commands\SystemCommands;

    use Longman\TelegramBot\Commands\SystemCommand;
    use Longman\TelegramBot\Commands\UserCommand;
    use Longman\TelegramBot\Commands\UserCommands\BlacklistCommand;
    use Longman\TelegramBot\Commands\UserCommands\LanguageCommand;
    use Longman\TelegramBot\Commands\UserCommands\WhoisCommand;
    use Longman\TelegramBot\Entities\InlineKeyboard;
    use Longman\TelegramBot\Entities\ServerResponse;
    use IntellivoidBot;
    use Longman\TelegramBot\Request;

    /**
     * Class StartCommand
     * @package Longman\TelegramBot\Commands\SystemCommands
     */
    class UnlinkCommand extends UserCommand
    {
        /**
         * @var string
         */
        protected $name = 'start';

        /**
         * @var string
         */
        protected $description = 'Gets executed when a user first starts using the bot.';

        /**
         * @var string
         */
        protected $usage = '/unlink';

        /**
         * @var string
         */
        protected $version = '2.0.0';

        /**
         * @var bool
         */
        protected $private_only = false;

        /**
         * The whois command used for finding targets
         *
         * @var WhoisCommand|null
         */
        public $WhoisCommand = null;

        /**
         * Command execute method
         *
         * @return ServerResponse
         * @noinspection DuplicatedCode
         */
        public function execute()
        {
            // Find all clients
            $this->WhoisCommand = new WhoisCommand($this->telegram, $this->update);
            $this->WhoisCommand->findClients();

            // Tally DeepAnalytics
            $DeepAnalytics = IntellivoidBot::getDeepAnalytics();
            $DeepAnalytics->tally('intellivoid_bot', 'messages', 0);
            $DeepAnalytics->tally('intellivoid_bot', 'unlink_command', 0);
            $DeepAnalytics->tally('intellivoid_bot', 'messages', (int)$this->WhoisCommand->ChatObject->ID);
            $DeepAnalytics->tally('intellivoid_bot', 'unlink_command', (int)$this->WhoisCommand->ChatObject->ID);

            // Ignore forwarded commands
            if($this->getMessage()->getForwardFrom() !== null || $this->getMessage()->getForwardFromChat())
            {
                return null;
            }

            $Client = $this->WhoisCommand->UserClient;

            if($Client->AccountID == 0)
            {
                return Request::sendMessage([
                    'chat_id'      => $this->getMessage()->getChat()->getId(),
                    'text'         => "This Telegram account is not linked a Intellivoid Account!",
                    'reply_markup' => new InlineKeyboard([
                        [
                            'text' => 'Link your Intellivoid Account',
                            'url' => "https://accounts.intellivoid.net/auth/telegram?auth=telegram&client_id=" . $this->WhoisCommand->UserClient->PublicID
                        ]
                    ]),
                ]);
            }

            return Request::sendMessage([
                "chat_id"      => $this->getMessage()->getChat()->getId(),
                "text"         => "This account is linked to .. are you sure you want to unlink this Telegram account from your Intellivoid Account?",
                "reply_markup" => new InlineKeyboard([
                    [
                        "text" => "Confirm",
                        "callback_data" => "unlink_account"
                    ]
                ]),
            ]);

        }
    }