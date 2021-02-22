<?php

    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpMissingFieldTypeInspection */
    /** @noinspection PhpUndefinedClassInspection */
    /** @noinspection PhpUnused */
    /** @noinspection PhpIllegalPsrClassPathInspection */

    namespace Longman\TelegramBot\Commands\SystemCommands;

    use Exception;
    use IntellivoidAccounts\Abstracts\SearchMethods\AccountSearchMethod;
    use IntellivoidAccounts\Exceptions\AccountNotFoundException;
    use IntellivoidAccounts\Exceptions\AuthNotPromptedException;
    use IntellivoidAccounts\Exceptions\AuthPromptAlreadyApprovedException;
    use IntellivoidAccounts\Exceptions\AuthPromptExpiredException;
    use IntellivoidAccounts\Exceptions\DatabaseException;
    use IntellivoidAccounts\Exceptions\InvalidSearchMethodException;
    use IntellivoidAccounts\Exceptions\TelegramServicesNotAvailableException;
    use IntellivoidAccounts\IntellivoidAccounts;
    use IntellivoidBot;
    use Longman\TelegramBot\Commands\SystemCommand;
    use Longman\TelegramBot\Commands\UserCommands\WhoisCommand;
    use Longman\TelegramBot\Entities\InlineKeyboard;
    use Longman\TelegramBot\Entities\ServerResponse;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;
    use TelegramClientManager\Exceptions\InvalidSearchMethod;
    use TelegramClientManager\Exceptions\TelegramClientNotFoundException;

    class CallbackqueryCommand extends SystemCommand
    {
        /**
         * @var string
         */
        protected $name = "callbackquery";

        /**
         * @var string
         */
        protected $description = 'Reply to callback query';

        /**
         * @var string
         */
        protected $version = '"1.0.0';

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
         * @throws AccountNotFoundException
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws TelegramException
         * @throws \TelegramClientManager\Exceptions\DatabaseException
         * @throws InvalidSearchMethod
         * @throws TelegramClientNotFoundException
         */
        public function execute()
        {
            $IntellivoidAccounts = new IntellivoidAccounts();

            // Find all clients
            $this->WhoisCommand = new WhoisCommand($this->telegram, $this->update);
            $this->WhoisCommand->findCallbackClients($this->getCallbackQuery());


            $Client = $this->WhoisCommand->CallbackQueryUserClient;


            Request::editMessageReplyMarkup([
                'chat_id' => $Client->Chat->ID,
                'message_id' => $this->getCallbackQuery()->getMessage()->getMessageId(),
                'reply_markup' => new InlineKeyboard([])
            ]);


            if($Client->AccountID == 0)
            {
                return Request::answerCallbackQuery([
                    'callback_query_id' => $this->getCallbackQuery()->getId(),
                    'text'              => 'Telegram Account not linked',
                    'show_alert'        => $this->getCallbackQuery()->getData(),
                    'cache_time'        => 10,
                ]);
            }

            $Account = $IntellivoidAccounts->getAccountManager()->getAccount(AccountSearchMethod::byId, $Client->AccountID);

            switch($this->getCallbackQuery()->getData())
            {
                case "unlink_account":

                    try
                    {
                        $Account = $IntellivoidAccounts->getAccountManager()->getAccount(AccountSearchMethod::byId, $Client->AccountID);
                        $Account->Configuration->VerificationMethods->TelegramLink->disable();
                        $Account->Configuration->VerificationMethods->TelegramClientLinked = false;
                        $IntellivoidAccounts->getAccountManager()->updateAccount($Account);
                    }
                    catch(Exception $e)
                    {
                        unset($e);
                    }

                    $Client->AccountID = 0;
                    IntellivoidBot::getTelegramClientManager()->getTelegramClientManager()->updateClient($Client);

                    return Request::sendMessage([
                        'chat_id' => $Client->Chat->ID,
                        'text' => "\u{2705} You have successfully unlinked this Telegram Account from Intellivoid Accounts"
                    ]);

                case "auth_allow":
                    try
                    {
                        $IntellivoidAccounts->getTelegramService()->approveAuth($Client);

                        Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'Approved',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);

                        return Request::sendMessage([
                            'chat_id' => $Client->Chat->ID,
                            'text' => "\u{2705} You have approved for this authentication request"
                        ]);
                    }
                    catch (AuthNotPromptedException $e)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'No authentication request has been issued',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
                    catch (AuthPromptAlreadyApprovedException $e)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'This authentication request has already been approved',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
                    catch (AuthPromptExpiredException $e)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'This authentication request has expired',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
                    catch (TelegramServicesNotAvailableException $e)
                    {
                        Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'The service is unavailable',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);

                        return Request::sendMessage([
                            'chat_id' => $Client->Chat->ID,
                            'text' => "This service is not available at the moment"
                        ]);
                    }
                    catch(Exception $exception)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'Intellivoid Server Error (' . $exception->getCode() . ')',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }

                case "auth_deny":
                    try
                    {
                        $IntellivoidAccounts->getTelegramService()->disallowAuth($Client);

                        Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'Denied',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);

                        return Request::sendMessage([
                            'chat_id' => $Client->Chat->ID,
                            'text' => "\u{1F6AB} You have denied this authentication request"
                        ]);
                    }
                    catch (AuthNotPromptedException $e)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'No authentication request has been issued',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
                    catch (AuthPromptAlreadyApprovedException $e)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'This authentication request has already been approved',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
                    catch (AuthPromptExpiredException $e)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'This authentication request has expired',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
                    catch (TelegramServicesNotAvailableException $e)
                    {
                        Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'The service is unavailable',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);

                        return Request::sendMessage([
                            'chat_id' => $Client->Chat->ID,
                            'text' => "This service is not available at the moment"
                        ]);
                    }
                    catch(Exception $exception)
                    {
                        return Request::answerCallbackQuery([
                            'callback_query_id' => $this->getCallbackQuery()->getId(),
                            'text'              => 'Intellivoid Server Error (' . $exception->getCode() . ')',
                            'show_alert'        => $this->getCallbackQuery()->getData(),
                            'cache_time'        => 10,
                        ]);
                    }
            }

            return Request::sendMessage([
                'chat_id' => $Client->Chat->ID,
                'text' => "Invalid callback query"
            ]);

        }
    }