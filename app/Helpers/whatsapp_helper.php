<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

if (!function_exists('WHATSAPPJHGOVT')) {
    function WHATSAPPJHGOVT($mobileno, $templateid, array $message = []) // $language
    {
        $bearerToken    = Config::get("grievance-constants.WHATSAPP_TOKEN");
        $numberId       = Config::get("grievance-constants.WHATSAPP_NUMBER_ID");
        $url            = Config::get("grievance-constants.WHATSAPP_URL");

        $data = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => "+91$mobileno",                             //<--------------------- here
            "type" => "template",
            "template" => [
                "name" => "$templateid",
                "language" => [
                    "code" => "en_US"                           //<-------------------- en:English/ en_US:EngPDf
                ],
                "components" => [
                    ($message
                        ?
                        (
                            ($message['content_type'] ?? "") == "pdf"
                            ?
                            ([
                                "type" => "header",
                                "parameters" => [
                                    [
                                        "type" => "document",
                                        "document" => $message[0]
                                        // [
                                        //     "link" => "http://www.xmlpdf.com/manualfiles/hello-world.pdf",
                                        //     "filename" => "Payment Receipt.pdf"
                                        // ]
                                    ]
                                ]
                            ])
                            : (($message['content_type'] ?? "") == "text" ?
                                ([
                                    "type" => "body",
                                    "parameters" => array_map(function ($val) {
                                        return ["type" => "text", "text" => $val];
                                    }, $message[0] ?? [])
                                ]
                                )
                                :
                                "")
                        )
                        : ""),
                ]
            ]
        ];

        # Calling the http request
        $result = Http::withHeaders([

            "Authorization" => "Bearer $bearerToken",
            "contentType" => "application/json"

        ])->post($url . $numberId . "/messages", $data);

        # Validate the resposne
        $responseBody = json_decode($result->getBody(), true);
        if (isset($responseBody["error"])) {
            $response = ['response' => false, 'status' => 'failure', 'msg' => $responseBody];
        } else {
            $response = ['response' => true, 'status' => 'success', 'msg' => $responseBody];
        }

        return $response;
    }
}

if (!function_exists('Whatsapp_Send')) {
    function Whatsapp_Send($mobileno, $templateid, array $message = []) // $language
    {
        $res = WHATSAPPJHGOVT($mobileno, $templateid, $message);  // $language
        return $res;
    }
}
