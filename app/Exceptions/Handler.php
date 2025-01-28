<?php

namespace App\Exceptions;

use Throwable;
use App\Traits\Response;
use Illuminate\Support\Str;
use App\Http\Helpers\RegexHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Knuckles\Scribe\Exceptions\ScribeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use Response;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ScribeException::class
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (NotFoundHttpException $exception, Request $request) {
            if (Str::contains($exception->getMessage(), 'Backed Enum')) {
                return $this->error($exception->getMessage(), 404);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException && $request->isJson()) { // Customize ModelNotFoundException responses resulting from route model binding and change its format to our api response format.
            $result = explode(' ', \Str::replace('\\', ' ', $exception->getModel())); // TODO: Optimize this logic
            $result = array_pop($result);

            return $this->error("The ".\Str::lower(RegexHelper::format($result)) ." was not found!", 404);

            // return Route::respondWithRoute('api.fallback.404');
        }

        return parent::render($request, $exception);
    }
}
