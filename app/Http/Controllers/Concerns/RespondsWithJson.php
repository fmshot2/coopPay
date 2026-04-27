<?php

namespace App\Http\Controllers\Concerns;

use Inertia\Inertia;
use Illuminate\Support\Facades\App;


trait RespondsWithJson
{
    protected function respond(string $component, array $data = [], int $status = 200)
    {
        if (request()->expectsJson()) {
            return response()->json($data, $status);
        }

        return Inertia::render($component, $data);
    }

    protected function respondSuccess(string $message, array $data = [], int $status = 200)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $data,
            ], $status);
        }

        return back()->with('success', $message);
    }

    protected function respondError(array $errors = [], int $status = 422)
    {
        $message = "An Error Occured";

        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ], $status);
        }

        return back()->withErrors($errors)->with('error', $message);
    }

    protected function respondSingleError(string $message, array $errors = [], int $status = 422)
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ], $status);
        }
            return back()->with('error', $message);
    }

    protected function respondException(\Throwable $e, string $defaultMessage = 'An error occurred')
    {
        $isLocal = App::environment('local');

        if (request()->expectsJson()) {
            $response = [
                'success' => false,
                'message' => $defaultMessage,
            ];

            if ($isLocal) {
                $response['exception'] = get_class($e);
                $response['message']   = $e->getMessage();
                $response['file']      = $e->getFile();
                $response['line']      = $e->getLine();
                $response['trace']     = $e->getTrace();
            }

            return response()->json($response, 500);
        }

        if ($isLocal) {
            return back()->with('error', "{$defaultMessage}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}")->withInput();
        }

        return back()->with('error', $defaultMessage)->withInput();
    }
}
