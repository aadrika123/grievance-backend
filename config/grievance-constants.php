<?php

return [

    "REF_IMAGE_NAME" => [
        "GRIEVANCE_APPLY" => "GrievanceApply",
    ],
    "RELATIVE_PATH" => [
        "1" => "Uploads/Grievances/Applications",
    ],
    "DOC_CODE" => "GRIEVANCE_DOC",
    "WF_DATABASE" => [
        "grievance_active_applicantions"                => 34,
        "associated_grievance_active_applicantions"     => 36,
    ],

    "WF_OTHER_DATABASE" => [
        "grievance_solved_applicantions"                => 34,
        "associated_grievance_solved_applicantions"     => 36,
    ],

    "WF_REJECTED_DATABASE" => [
        "grievance_rejected_applicantions"              => 34,
        "associated_grievance_rejected_applicantions"   => 36
    ],
    "DB_NAME" => [
        "P_GRIEVANCE"           => "grievance_active_applicantions",
        "FIRST_A_GRIEVANCE"     => "associated_grievance_active_applicantions",
        "M_GRIEVANCE_QUESTION"  => "m_grievance_questions",
        "P_SOLVED_GRIEVANCE"    => "grievance_solved_applicantions",
    ],
    "ID_GEN_PARAM" => [
        "PARENT_GRIEVANCE" => 41,
    ],

    "WHATSAPP_TOKEN"        => env("WHATSAPP_TOKEN", "xxx"),
    "WHATSAPP_NUMBER_ID"    => env("WHATSAPP_NUMBER_ID", "xxx"),
    "WHATSAPP_URL"          => env("WHATSAPP_URL", "xxx"),
    "SALT_VALUE"            => env("SALT_VALUE", "xxx"),

    "REF_USER_TYPE" => [
        "1" => "Citizen",
        "2" => "Employee",
        "3" => "JSK",
        "4" => "TC"
    ],

    "DEPARTMENT_LISTING" => [
        "1" => "FIRST",
        "2" => "SECOND",
        "3" => "THIRD",
        "4" => "FOURTH",
        "5" => "FIFTH"
    ],

    "APPLY_THROUGH" => [
        "1" => "ONLINE",
        "2" => "JSK",
        "3" => "EMAIL",
        "4" => "WHATSAPP"
    ],

    "CONDITION" => [
        "ACTIVE"        => 1,
        "REJECTED"      => 0,
        "WF_REJECTED"   => 5,
        "SOLVED"        => 6
    ],

    "SOLVED_STATUS" => [
        "ACTIVE"    => 1,
        "DEACTIVE"  => 0,
        "CLOSED"    => 2,
        "REOPEN"    => 3
    ],

    "GRAMMER_LIST" => [
        "a", "an", "the", "and", "but", "or", "for", "nor", "so", "yet", "in", "on", "at", "by",
        "with", "about", "before", "after", "during", "under", "over", "between", "through",
        "above", "below", "i", "you", "he", "she", "it", "we", "they", "me", "him", "her",
        "us", "them", "am", "is", "are", "was", "were", "be", "being", "been", "do", "does",
        "did", "have", "has", "had", "shall", "will", "should", "would", "may", "might", "must",
        "can", "could", "this", "that", "these", "those", "my", "your", "his", "her", "its",
        "our", "their", "oh", "wow", "ouch", "hey", "hello", "hi", "how", "to", "in", "of",
        "by", "with", "about", "as", "on", "off", "up", "down", "out", "over", "under", "at",
        "before", "after", "since", "during", "above", "below", "through", "beside", "beyond",
        "inside", "outside", "near", "through", "between", "within", "among",
    ],

    "FILTER_PARAMETER" => [
        "MOBILE_NO"         => 1,
        "HOLDING_NO"        => 2,
        "SAF_NO"            => 3,
        "APPLICATION_NO"    => 4,
        "CONSUMER_NO"       => 5,
        "TRADE_LICENCE_NO"  => 6,
    ],
];
