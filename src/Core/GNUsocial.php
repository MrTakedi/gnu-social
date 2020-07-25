<?php

// {{{ License

// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

// }}}

/**
 * Main GNU social entry point
 *
 * @package   GNUsocial
 * @category  Framework
 *
 * StatusNet and GNU social 1
 *
 * @author    Refer to CREDITS.md
 * @copyright 2010 Free Software Foundation, Inc http://www.fsf.org
 *
 * GNU social 2
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 *
 * GNU social 3
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2018-2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Core\DB\DB;
use App\Core\DB\DefaultSettings;
use App\Core\I18n\I18n;
use App\Core\Queue\Queue;
use App\Core\Router\Router;
use App\Util\Common;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security as SSecurity;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class GNUsocial implements EventSubscriberInterface
{
    use TargetPathTrait;

    protected LoggerInterface          $logger;
    protected TranslatorInterface      $translator;
    protected EntityManagerInterface   $entity_manager;
    protected RouterInterface          $router;
    protected FormFactoryInterface     $form_factory;
    protected MessageBusInterface      $message_bus;
    protected EventDispatcherInterface $event_dispatcher;
    protected SessionInterface         $session;
    protected SSecurity                $security;

    /**
     * Symfony dependency injection gives us access to these services
     */
    public function __construct(LoggerInterface $logger,
                                TranslatorInterface $trans,
                                EntityManagerInterface $em,
                                RouterInterface $router,
                                FormFactoryInterface $ff,
                                MessageBusInterface $mb,
                                EventDispatcherInterface $ed,
                                SessionInterface $sess,
                                SSecurity $sec)
    {
        $this->logger           = $logger;
        $this->translator       = $trans;
        $this->entity_manager   = $em;
        $this->router           = $router;
        $this->form_factory     = $ff;
        $this->message_bus      = $mb;
        $this->event_dispatcher = $ed;
        $this->session          = $sess;
        $this->security         = $sec;

        $this->initialize();
    }

    /**
     * Store these services to be accessed statically and load modules
     *
     * @param EventDispatcherInterface $event_dispatcher
     */
    public function initialize(): void
    {
        Log::setLogger($this->logger);
        Event::setDispatcher($this->event_dispatcher);
        I18n::setTranslator($this->translator);
        DB::setManager($this->entity_manager);
        Router::setRouter($this->router);
        Form::setFactory($this->form_factory);
        Queue::setMessageBus($this->message_bus);
        Security::setHelper($this->security);

        DefaultSettings::setDefaults();

        // TODO use configuration properly
        try {
            if (Common::config('site', 'use_email')) {
                Mailer::setMailer($this->containen->get('mailer'));
            }
        } catch (Exception $e) {
            // Table doesn't exist yet
        }

        Cache::setupCache();
        ModulesManager::loadModules();
    }

    /**
     * Event very early on in the Symfony HTTP lifecycle, but after everyting is registered
     * where we get access to the event dispatcher
     *
     * @param RequestEvent             $event
     * @param string                   $event_name
     * @param EventDispatcherInterface $event_dispatcher
     *
     * @return RequestEvent
     */
    public function onKernelRequest(RequestEvent $event,
                                    string $event_name): RequestEvent
    {
        $request = $event->getRequest();

        // Save the target path, so we can redirect back after logging in
        if (!(!$event->isMasterRequest() || $request->isXmlHttpRequest() || in_array($request->attributes->get('_route'), ['login', 'initialize']))) {
            $this->saveTargetPath($this->session, 'main', $request->getBaseUrl());
        }

        $this->initialize();
        return $event;
    }

    /**
     * Event after everything is initialized when using the `bin/console` command
     *
     * @param ConsoleCommandEvent      $event
     * @param string                   $event_name
     * @param EventDispatcherInterface $event_dispatcher
     *
     * @return ConsoleCommandEvent
     */
    public function onCommand(ConsoleCommandEvent $event,
                              string $event_name): ConsoleCommandEvent
    {
        $this->initialize();
        return $event;
    }

    /**
     * Tell Symfony which events we want to listen to, which Symfony detects and autowires
     * due to this implementing the `EventSubscriberInterface`
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            'console.command'     => 'onCommand',
        ];
    }
}
