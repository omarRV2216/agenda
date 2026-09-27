<?php

namespace App\Http\Controllers;

class AppBaseController extends Controller
{
    public function sendResponse($result, $message = 'OK')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $result,
        ], 200);
    }

    public function sendError($error, $code = 404)
    {
        return response()->json([
            'success' => false,
            'message' => $error,
        ], $code);
    }

    public function sendSuccess($message)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);
    }
}