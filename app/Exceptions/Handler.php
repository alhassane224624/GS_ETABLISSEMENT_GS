<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Compteurs interrogés en arrière-plan par les menus : une session expirée doit répondre 401 en JSON,
     * sans mémoriser l'adresse comme page à rouvrir après la connexion (sinon l'utilisateur atterrit sur {"count":0}).
     */
    protected function shouldReturnJson($request, Throwable $e)
    {
        return parent::shouldReturnJson($request, $e)
            || $request->is('messages/unread-count', 'notifications/unread-count', 'notifications/recent', 'bulletins/pending-count');
    }
}
