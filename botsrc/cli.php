<?php

    /** @noinspection DuplicatedCode */


    /**
     * cli.php is the main execution point for the bot to start polling, this method uses BackgroundWorker to
     * instantly process a batch of updates in the background without waiting for the updates to be completed.
     *
     * In exchange for this performance upgrade, each worker will use up database connections, make sure
     * the database can handle these connections without maxing out
     */

    use BackgroundWorker\BackgroundWorker;
    use Longman\TelegramBot\Exception\TelegramException;
    use ppm\ppm;
    use VerboseAdventure\Abstracts\EventType;
    use VerboseAdventure\Classes\ErrorHandler;
    use VerboseAdventure\VerboseAdventure;

    // Import all required auto loaders
    /** @noinspection PhpIncludeInspection */
    require("ppm");

    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.acm");
    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.background_worker");
    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.deepanalytics");
    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.telegram_client_manager");
    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.verbose_adventure");
    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.accounts");
    /** @noinspection PhpUnhandledExceptionInspection */
    ppm::import("net.intellivoid.tdlib");

    VerboseAdventure::setStdout(true); // Enable stdout
    ErrorHandler::registerHandlers(); // Register error handlers

    $current_directory = getcwd();

    if(class_exists("IntellivoidBot") == false)
    {
        if(file_exists($current_directory . DIRECTORY_SEPARATOR . 'IntellivoidBot.php'))
        {
            require_once($current_directory . DIRECTORY_SEPARATOR . 'IntellivoidBot.php');
        }
        elseif(file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'IntellivoidBot.php'))
        {
            require_once(__DIR__ . DIRECTORY_SEPARATOR . 'IntellivoidBot.php');
        }
        else
        {
            throw new RuntimeException("Cannot locate bot class");
        }
    }

    // Load all configurations
    /** @noinspection PhpUnhandledExceptionInspection */
    $TelegramServiceConfiguration = IntellivoidBot::getTelegramConfiguration();

    /** @noinspection PhpUnhandledExceptionInspection */
    $DatabaseConfiguration = IntellivoidBot::getDatabaseConfiguration();

    /** @noinspection PhpUnhandledExceptionInspection */
    $BackgroundWorkerConfiguration = IntellivoidBot::getBackgroundWorkerConfiguration();

    // Create the Telegram Bot instance (NO SQL)
    define("TELEGRAM_BOT_NAME", $TelegramServiceConfiguration['BotName']);
    IntellivoidBot::setLogHandler(new VerboseAdventure(TELEGRAM_BOT_NAME));

    if(strtolower($TelegramServiceConfiguration['BotName']) == 'true')
    {
        define("TELEGRAM_BOT_ENABLED", true);
    }
    else
    {
        define("TELEGRAM_BOT_ENABLED", false);
    }

    IntellivoidBot::getLogHandler()->log(EventType::INFO, "Starting Service", "Main");
    
    try
    {
        $telegram = new Longman\TelegramBot\Telegram(
            $TelegramServiceConfiguration['BotToken'],
            $TelegramServiceConfiguration['BotName']
        );
    }
    catch (Longman\TelegramBot\Exception\TelegramException $e)
    {
        IntellivoidBot::getLogHandler()->logException($e, "Main");
        exit(255);
    }

    $telegram->useGetUpdatesWithoutDatabase();

    // Start the workers using the supervisor
    IntellivoidBot::getLogHandler()->log(EventType::INFO, "Starting Supervisor", "Main");

    try
    {
        IntellivoidBot::$BackgroundWorker = new BackgroundWorker();
        IntellivoidBot::getBackgroundWorker()->getClient()->addServer(
            $BackgroundWorkerConfiguration["Host"],
            (int)$BackgroundWorkerConfiguration["Port"]
        );
        IntellivoidBot::getBackgroundWorker()->getSupervisor()->restartWorkers(
            $current_directory . DIRECTORY_SEPARATOR . 'worker.php', TELEGRAM_BOT_NAME,
            (int)$BackgroundWorkerConfiguration['MaxWorkers']
        );
    }
    catch(Exception $e)
    {
        IntellivoidBot::getLogHandler()->logException($e, "Main");
        exit(255);
    }

    $NextRestart = (int)time() + 1500;

    // Start listening to updates
    while(true)
    {
        try
        {
            if((int)time() >= $NextRestart)
            {
                $NextRestart = (int)time() + 1500;

                IntellivoidBot::getBackgroundWorker()->getSupervisor()->restartWorkers(
                    $current_directory . DIRECTORY_SEPARATOR . 'worker.php', TELEGRAM_BOT_NAME,
                    (int)$BackgroundWorkerConfiguration['MaxWorkers']
                );
            }
        }
        catch(Exception $e)
        {
            unset($e);
        }

        try
        {
            //SpamProtectionBot::getLogHandler()->log(EventType::INFO, "Listen for updates", "Main");
            $server_response = $telegram->handleBackgroundUpdates(IntellivoidBot::getBackgroundWorker());

            if ($server_response->isOk())
            {
                $update_count = count($server_response->getResult());
                if($update_count > 0)
                {
                    IntellivoidBot::getLogHandler()->log(EventType::INFO, "Processed $update_count update(s)", "Main");

                }
            }
            else
            {
                IntellivoidBot::getLogHandler()->log(EventType::ERROR, "Failed to fetch updates: " . $server_response->printError(true), "Main");

            }
        }
        catch (TelegramException $e)
        {
            IntellivoidBot::getLogHandler()->logException($e, "Main");
        }
    }