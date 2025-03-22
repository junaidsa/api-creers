<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\URL;

abstract class Controller
{

        function json_response($type, $code, $message, $status, $data = '')
        {
            $response['type'] = $type;
            $response['code'] = $code;
            $response['status'] = $status;
            $response['message'] = $message;
            $response['base_url'] = URL::to('/');
            if (!empty($data)) {
                $response['data'] = $data;
            }
            return response()->json($response, $status);
        }
}