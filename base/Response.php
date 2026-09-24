<?php

class Response
{
    public static function JSON_OK($success, $data = null, $message = ''){
        echo json_encode([
            'success' => $success,
            'data' =>  $data,
            'message' => $message
        ]);
        exit();
    }
}
