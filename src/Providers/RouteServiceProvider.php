<?php

namespace ReturnsPortal\Providers;

use Plenty\Plugin\RouteServiceProvider as ServiceProvider;
use Plenty\Plugin\Routing\Router;
use Plenty\Plugin\Routing\ApiRouter;

/**
 * Class RouteServiceProvider
 * @package ReturnsPortal\Providers
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register routes for the plugin
     * @param Router $router
     * @param ApiRouter $apiRouter
     */
    public function map(Router $router, ApiRouter $apiRouter)
    {
        // Customer-facing routes (Frontend)
        $router->get('returns', 'ReturnsPortal\Controllers\ReturnController@index');
        $router->get('returns/create/{orderId}', 'ReturnsPortal\Controllers\ReturnController@create');
        $router->post('returns/store', 'ReturnsPortal\Controllers\ReturnController@store');
        $router->get('returns/track/{returnId}', 'ReturnsPortal\Controllers\ReturnController@track');
        $router->get('returns/status/{returnId}', 'ReturnsPortal\Controllers\ReturnController@status');
        $router->post('returns/upload-image', 'ReturnsPortal\Controllers\ReturnController@uploadImage');
        $router->get('returns/print-label/{returnId}', 'ReturnsPortal\Controllers\ReturnController@printLabel');
        
        // Customer return history
        $router->get('my-returns', 'ReturnsPortal\Controllers\ReturnController@myReturns');
        
        // Admin routes (Backend)
        $router->get('admin/returns', 'ReturnsPortal\Controllers\AdminReturnController@index')
            ->middleware(['AdminAuthMiddleware']);
        $router->get('admin/returns/{returnId}', 'ReturnsPortal\Controllers\AdminReturnController@show')
            ->middleware(['AdminAuthMiddleware']);
        $router->post('admin/returns/{returnId}/approve', 'ReturnsPortal\Controllers\AdminReturnController@approve')
            ->middleware(['AdminAuthMiddleware']);
        $router->post('admin/returns/{returnId}/reject', 'ReturnsPortal\Controllers\AdminReturnController@reject')
            ->middleware(['AdminAuthMiddleware']);
        $router->post('admin/returns/{returnId}/receive', 'ReturnsPortal\Controllers\AdminReturnController@receive')
            ->middleware(['AdminAuthMiddleware']);
        $router->post('admin/returns/{returnId}/refund', 'ReturnsPortal\Controllers\AdminReturnController@refund')
            ->middleware(['AdminAuthMiddleware']);
        $router->get('admin/returns/export', 'ReturnsPortal\Controllers\AdminReturnController@export')
            ->middleware(['AdminAuthMiddleware']);
        
        // Statistics and reports
        $router->get('admin/returns/stats', 'ReturnsPortal\Controllers\AdminReturnController@statistics')
            ->middleware(['AdminAuthMiddleware']);
        
        // REST API routes
        $apiRouter->version(['v1'], ['middleware' => 'oauth'], function ($api) {
            // API endpoints for returns
            $api->get('returns', 'ReturnsPortal\Controllers\Api\ReturnApiController@index');
            $api->get('returns/{returnId}', 'ReturnsPortal\Controllers\Api\ReturnApiController@show');
            $api->post('returns', 'ReturnsPortal\Controllers\Api\ReturnApiController@store');
            $api->put('returns/{returnId}', 'ReturnsPortal\Controllers\Api\ReturnApiController@update');
            $api->delete('returns/{returnId}', 'ReturnsPortal\Controllers\Api\ReturnApiController@destroy');
            
            // Return items endpoints
            $api->get('returns/{returnId}/items', 'ReturnsPortal\Controllers\Api\ReturnItemApiController@index');
            $api->post('returns/{returnId}/items', 'ReturnsPortal\Controllers\Api\ReturnItemApiController@store');
            
            // Return status updates
            $api->post('returns/{returnId}/status', 'ReturnsPortal\Controllers\Api\ReturnApiController@updateStatus');
        });
    }
}
