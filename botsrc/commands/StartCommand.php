<?php

    /** @noinspection PhpMissingFieldTypeInspection */
    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpIllegalPsrClassPathInspection */

    namespace Longman\TelegramBot\Commands\SystemCommands;

    use IntellivoidAccounts\Abstracts\SearchMethods\AccountSearchMethod;
    use IntellivoidAccounts\Exceptions\AccountNotFoundException;
    use IntellivoidAccounts\Exceptions\DatabaseException;
    use IntellivoidAccounts\Exceptions\InvalidSearchMethodException;
    use Longman\TelegramBot\Commands\SystemCommand;
    use Longman\TelegramBot\Commands\UserCommands\BlacklistCommand;
    use Longman\TelegramBot\Commands\UserCommands\LanguageCommand;
    use Longman\TelegramBot\Commands\UserCommands\WhoisCommand;
    use Longman\TelegramBot\Entities\InlineKeyboard;
    use Longman\TelegramBot\Entities\ServerResponse;
    use IntellivoidBot;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use TelegramClientManager\Abstracts\TelegramChatType;

    /**
     * Start command
     *
     * Gets executed when a user first starts using the bot.
     */
    class StartCommand extends SystemCommand
    {
        /**
         * @var string
         */
        protected $name = "start";

        /**
         * @var string
         */
        protected $description = "Allows the user to link their account to Intellivoid";

        /**
         * @var string
         */
        protected $usage = "/start";

        /**
         * @var string
         */
        protected $version = "1.0.0";

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
         * @return ServerResponse|null
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws TelegramException
         * @noinspection DuplicatedCode
         */
        public function execute(): ?ServerResponse
        {
            // Find all clients
            $this->WhoisCommand = new WhoisCommand($this->telegram, $this->update);
            $this->WhoisCommand->findClients();

            // Tally DeepAnalytics
            $DeepAnalytics = IntellivoidBot::getDeepAnalytics();
            $DeepAnalytics->tally("intellivoid_bot", "messages", 0);
            $DeepAnalytics->tally("intellivoid_bot", "start_command", 0);
            $DeepAnalytics->tally("intellivoid_bot", "messages", (int)$this->WhoisCommand->ChatObject->ID);
            $DeepAnalytics->tally("intellivoid_bot", "start_command", (int)$this->WhoisCommand->ChatObject->ID);

            // Ignore forwarded commands
            if($this->getMessage()->getForwardFrom() !== null || $this->getMessage()->getForwardFromChat())
            {
                return null;
            }

            if($this->getMessage()->getChat()->getType() !== TelegramChatType::Private)
            {
                return null;
            }

            if($this->WhoisCommand->UserClient->AccountID == null)
            {
                return Request::sendMessage([
                    "chat_id" => $this->getMessage()->getChat()->getId(),
                    "text" =>
                        "This is the official Intellivoid Services bot for Telegram\n\n" .
                        "You can link your Telegram account to your Intellivoid account using this bot " .
                        "to receive security alerts, approve login requests and or receive notifications from " .
                        "third party applications that are linked to your Intellivoid Account",
                    "reply_markup" => new InlineKeyboard([
                        [
                            "text" => "Link your Intellivoid Account",
                            "url" => "https://accounts.intellivoid.net/auth/telegram?auth=telegram&client_id=" . $this->WhoisCommand->UserClient->PublicID
                        ]
                    ]),
                ]);
            }

            $Account = IntellivoidBot::getIntellivoidAccounts()->getAccountManager()->getAccount(
                AccountSearchMethod::byId, $this->WhoisCommand->UserClient->AccountID
            );

            return Request::sendMessage([
                "chat_id" => $this->getMessage()->getChat()->getId(),
                "text" => "Hi " . $Account->Username . ", you are currently linked to Intellivoid Accounts!"
            ]);

        }
    }