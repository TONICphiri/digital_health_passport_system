<?php

use App\Console\Commands\CreateAdminCommand;
use App\Exceptions\WorkflowException;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
       
        $middleware->trustProxies(at: '*');

        // Signs out users whose account or facility has been deactivated,
        // and sends users with a one time password to choose their own.
        $middleware->append([
            EnsureAccountIsActive::class,
            EnsurePasswordIsChanged::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withCommands([
        CreateAdminCommand::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business rule messages are expected and shown to the user, not logged.
        $exceptions->dontReport(WorkflowException::class);

        // Every logged error carries a short reference number.
        $exceptions->context(fn () => ['reference' => app()->has('error.reference')
            ? app('error.reference')
            : tap(strtoupper(Str::random(8)), fn ($reference) => app()->instance('error.reference', $reference))]);

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()->withInput($request->except('password', 'password_confirmation', '_token'))
                ->with('error', 'Your session expired before the form was sent. Please try again.');
        });

  
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (config('app.debug')
                || $exception instanceof HttpExceptionInterface
                || $exception instanceof ModelNotFoundException
                || $exception instanceof AuthorizationException
                || $exception instanceof WorkflowException
                || $exception instanceof ValidationException
                || $exception instanceof AuthenticationException) {
                return null;
            }

            $reference = app()->has('error.reference') ? app('error.reference') : null;

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Something went wrong on our side. Please try again.',
                    'reference' => $reference,
                ], 500);
            }

            return response()->view('errors.500', ['reference' => $reference], 500);
        });
    })->create();