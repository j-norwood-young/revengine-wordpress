<?php

function revengine_fire_callback($endpoint, $data) {
    try {
        $url = rtrim(get_option("revengine_callback_url"), "/") . "/" . ltrim($endpoint, "/");
        $ch = curl_init();
        $payload = json_encode( $data );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization: Bearer ' . get_option("revengine_callback_token")));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("RevEngine Callback error: " . curl_error($ch));
            $response = curl_error($ch);
        }
        curl_close($ch);
        if (get_option("revengine_callback_debug")) {
            error_log("RevEngine Callback URL: " . $url);
            error_log("RevEngine Callback Response: " . print_r($response, true));
        }
        return $response;
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function revengine_test_callback() {
    try {
        return revengine_fire_callback("/test", array("test" => "test"));
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}