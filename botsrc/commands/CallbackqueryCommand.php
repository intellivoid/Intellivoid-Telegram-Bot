<?php

    namespace Longman\TelegramBot\Commands\SystemCommands;
    use Exception;
    use IntellivoidAccounts\Exceptions\AuthNotPromptedException;
    use IntellivoidAccounts\Exceptions\AuthPromptAlreadyApprovedException;
    use IntellivoidAccounts\Exceptions\AuthPromptExpiredException;
    use IntellivoidAccounts\Exceptions\DatabaseException;
    use IntellivoidAccounts\Exceptions\InvalidSearchMethodException;
    use IntellivoidAccounts\Exceptions\TelegramClientNotFoundException;
    use IntellivoidAccounts\Exceptions\TelegramServicesNotAvailableException;
    use IntellivoidAccounts\IntellivoidAccounts;
    use IntellivoidAccounts\Objects\TelegramClient\Chat;
    use IntellivoidAccounts\Objects\TelegramClient\User;
    use Longman\TelegramBot\Commands\SystemCommand;
    use Longman\TelegramBot\Entities\InlineKeyboard;
    use Longman\TelegramBot\Entities\ServerResponse;
    use Longman\TelegramBot\Exception\TelegramException;
    use Longman\TelegramBot\Request;

    /**
    * Callback query command
    *
    * This command handles all callback queries sent via inline keyboard buttons.
    *
    * @see InlinekeyboardCommand.php
    */
    class CallbackqueryCommand extends SystemCommand
    {
        /**
         * @var string
         */
        protected $name = 'callbackquery';
        /**
         * @var string
         */
        protected $description = 'Reply to callback query';
        /**
         * @var string
         */
        protected $version = '1.0.0';

        /**
         * Command execute method
         *
         * @return ServerResponse
         * @throws DatabaseException
         * @throws InvalidSearchMethodException
         * @throws TelegramClientNotFoundException
         * @throws TelegramException
         */
        public function execute()
        {
            $IntellivoidAccounts = new IntellivoidAccounts();

            $Client = $IntellivoidAccounts->getTelegramClientManager()->registerClient(
                Chat::fromArray($this->getCallbackQuery()->getMessage()->getChat()->getRawData()),
                User::fromArray($this->getCallbackQuery()->getFrom()->getRawData())
            );

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

            switch($this->getCallbackQuery()->getData())
            {
                case 'auth_allow':
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

                case 'auth_deny':

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