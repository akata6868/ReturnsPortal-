<?php

namespace ReturnsPortal\Providers;

use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Order\Models\Order;
use ReturnsPortal\Extensions\TwigServiceProvider;

/**
 * Class ReturnPortalServiceProvider
 * @package ReturnsPortal\Providers
 */
class ReturnPortalServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->getApplication()->register(RouteServiceProvider::class);
        $this->getApplication()->register(TwigServiceProvider::class);
    }

    /**
     * Boot additional services for the plugin
     * @param Dispatcher $eventDispatcher
     * @param Twig $twig
     */
    public function boot(Dispatcher $eventDispatcher, Twig $twig)
    {
        // Register event listeners
        $this->registerEventListeners($eventDispatcher);
        
        // Add Twig extensions
        $twig->addExtension(TwigServiceProvider::class);
        
        // Register middleware
        $this->registerMiddleware();
    }

    /**
     * Register event listeners for return management
     * @param Dispatcher $eventDispatcher
     */
    private function registerEventListeners(Dispatcher $eventDispatcher)
    {
        // Listen for order creation
        $eventDispatcher->listen(
            'Plenty\Modules\Order\Events\OrderCreated',
            function ($order) {
                // Log order creation for return eligibility
                $this->logOrderForReturns($order);
            }
        );

        // Listen for return status changes
        $eventDispatcher->listen(
            'ReturnsPortal\Events\ReturnStatusChanged',
            'ReturnsPortal\Listeners\SendReturnStatusEmail'
        );

        // Listen for return created
        $eventDispatcher->listen(
            'ReturnsPortal\Events\ReturnCreated',
            'ReturnsPortal\Listeners\SendReturnCreatedEmail'
        );

        // Listen for return approved
        $eventDispatcher->listen(
            'ReturnsPortal\Events\ReturnApproved',
            'ReturnsPortal\Listeners\ProcessReturnApproval'
        );

        // Listen for return received
        $eventDispatcher->listen(
            'ReturnsPortal\Events\ReturnReceived',
            'ReturnsPortal\Listeners\ProcessReturnReceived'
        );
    }

    /**
     * Register middleware for authentication and authorization
     */
    private function registerMiddleware()
    {
        // Register authentication middleware for admin routes
        $this->app->middleware([
            'ReturnsPortal\Middleware\AdminAuthMiddleware'
        ]);
    }

    /**
     * Log order for future return eligibility
     * @param Order $order
     */
    private function logOrderForReturns(Order $order)
    {
        // This will be handled by the repository
        // Just a placeholder for the event
    }
}
