<?php

namespace App\Http\Response;

trait BaseApiResponse
{
    public function responseSuccess($data = '', $message = '', $total = '')
    {
        $response = [
            'code' => 1,
            'success' => true
        ];
        if($data) $response['data'] = $data;
        if($message) $response['message'] = $message;
        if($total !== '') $response['total'] = $total;

        return response()->json($response, 200);
    }

    public function responseError($message = '')
    {
        $response = [
            'code' => -1,
            'success' => false,
            'message' => $message
        ];

        return response()->json($response, 400);
    }

    public function responseForbidden($message = 'forbidden')
    {
        $response = [
            'code' => -1,
            'success' => false,
            'type' => 'forbidden',
            'message' => $message
        ];

        return response()->json($response, 403);
    }

    public function responseValidateError()
    {
        //$response = [
        //    'code' => -1,
        //    'success' => false,
        //    'message' => 'forbidden'
        //];
        //return response()->json($response, 400);
    }
}
