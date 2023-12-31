<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grievance\closeGrievanceReq;
use App\Models\ActiveCitizen;
use App\Models\Grievance\GrievanceActiveApplicantion;
use App\Models\Grievance\GrievanceActiveQuestion;
use App\Models\Grievance\GrievanceClosedQuestion;
use App\Models\Grievance\MGrievanceQuestion;
use App\Models\Property\PropActiveSaf;
use App\Models\ThirdParty\ApiMaster;
use App\Models\User;
use App\Models\Workflow\ModuleMaster;
use App\Models\Workflow\WfWorkflow;
use App\Pipelines\Grievance\SearchProperty;
use App\Traits\GrievanceTrait;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\CssSelector\Node\FunctionNode;

use function PHPSTORM_META\map;
use function PHPUnit\Framework\isNull;

/**
 * | Created by :
 * | Created at :
 * | Modified By : Sam Kerketta
 * | Modefied At : 02-09-2023
 * | Status : Open
 * | 
 * | Grievance Module Opreration and Agency side process
 */

class GrievanceAgencyController extends Controller
{
    use GrievanceTrait;
    use Workflow;

    private $_moduleId;
    private $_workflowMstId;
    private $_imageName;
    private $_relativePath;
    private $_grievanceDocCode;
    private $_grievanceRoleLevel;
    private $_databaseName;
    private $_wfDatabase;
    private $_idGenParamIds;
    private $_userType;
    private $_departmentType;
    private $_applythrough;
    private $_wfRejectedDatabase;
    private $_condition;
    private $_solvedStatus;
    private $_moduleIds;
    private $_grammarList;
    private $_masterQuestion;

    protected $_DB_NAME;
    protected $_DB;
    protected $_DB_NAME2;
    protected $_DB2;

    public function __construct()
    {
        $this->_moduleIds           = Config::get('workflow-constants.MODULE_LISTING');
        $this->_moduleId            = Config::get('workflow-constants.GRIEVANCE_MODULE_ID');
        $this->_workflowMstId       = Config::get('workflow-constants.GRIEVANCE_WF_MASTER_ID');
        $this->_imageName           = Config::get('grievance-constants.REF_IMAGE_NAME');
        $this->_relativePath        = Config::get('grievance-constants.RELATIVE_PATH');
        $this->_grievanceDocCode    = Config::get('grievance-constants.DOC_CODE');
        $this->_grievanceRoleLevel  = Config::get('workflow-constants.GRIVANCE_ROLE_LEVEL');
        $this->_databaseName        = Config::get('grievance-constants.DB_NAME');
        $this->_wfDatabase          = Config::get('grievance-constants.WF_DATABASE');
        $this->_idGenParamIds       = Config::get('grievance-constants.ID_GEN_PARAM');
        $this->_userType            = Config::get('grievance-constants.REF_USER_TYPE');
        $this->_departmentType      = Config::get('grievance-constants.DEPARTMENT_LISTING');
        $this->_applythrough        = Config::get('grievance-constants.APPLY_THROUGH');
        $this->_wfRejectedDatabase  = Config::get('grievance-constants.WF_REJECTED_DATABASE');
        $this->_condition           = Config::get('grievance-constants.CONDITION');
        $this->_solvedStatus        = Config::get('grievance-constants.SOLVED_STATUS');
        $this->_grammarList         = Config::get('grievance-constants.GRAMMER_LIST');
        # Database connectivity
        $this->_DB_NAME     = "pgsql_property";
        $this->_DB          = DB::connection($this->_DB_NAME);
        $this->_DB_NAME2    = "pgsql_master";
        $this->_DB2         = DB::connection($this->_DB_NAME2);
    }

    /**
     * | Database transaction connection
     */
    public function begin()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        $db3 = $this->_DB2->getDatabaseName();
        DB::beginTransaction();
        if ($db1 != $db2)
            $this->_DB->beginTransaction();
        if ($db1 != $db3 && $db2 != $db3)
            $this->_DB2->beginTransaction();
    }
    /**
     * | Database transaction connection
     */
    public function rollback()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        $db3 = $this->_DB2->getDatabaseName();
        DB::rollBack();
        if ($db1 != $db2)
            $this->_DB->rollBack();
        if ($db1 != $db3 && $db2 != $db3)
            $this->_DB2->rollBack();
    }
    /**
     * | Database transaction connection
     */
    public function commit()
    {
        $db1 = DB::connection()->getDatabaseName();
        $db2 = $this->_DB->getDatabaseName();
        $db3 = $this->_DB2->getDatabaseName();
        DB::commit();
        if ($db1 != $db2)
            $this->_DB->commit();
        if ($db1 != $db3 && $db2 != $db3)
            $this->_DB2->commit();
    }


    /**
     * | Get user details according to mobile No 
        | Serial No :
        | Under Con :
        | Priority first
     */
    public function getUserDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "filterBy"  => "required|",
                "parameter" => "required|",
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $key            = $request->filterBy;
            $mUser          = new User();
            $mActiveCitizen = new ActiveCitizen();
            $parameter      = $request->parameter;
            $msg            = "User and module related details!";

            # Distinguish btw filter parameter
            switch ($request->filterBy) {
                case ('mobileNo'):
                    $userDetails = $mActiveCitizen->getCitizenDetails($parameter)->first();
                    if (!$userDetails) {
                        $userDetails = $mUser->getUserByMobileNo($parameter)->first();
                    }
                    break;
                case ('holdingNo'):
                    $transferData = [
                        "connectionThrough"     => 1,                                                   // Static
                        "id"                    => $parameter,
                        "ulbId"                 => 2                                                    // Static
                    ];
                    $endPoint = "192.168.0.240:84/api/water/search-holding-saf";
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $userDetails = $this->structurePropDetails($unstructuredData);
                    break;
                case ('safNo'):
                    $transferData = [
                        "connectionThrough"     => 2,                                                   // Static
                        "id"                    => $parameter,
                        "ulbId"                 => 2                                                    // Static
                    ];
                    $endPoint = "192.168.0.240:84/api/water/search-holding-saf";
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $userDetails = $this->structurePropDetails($unstructuredData);
                    break;

                case ('waterApplicationNo'):

                default:
                    throw new Exception("Data in module dont exist!");
            }
            return responseMsgs(true, $msg, remove_null($userDetails), "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Structure the property details 
     * | for holdingNo,safNo,
        | Serial No :
        | Under Con
     */
    public function structurePropDetails($unstructuredData)
    {
        $ownerArray = (collect($unstructuredData->owners)->pluck('ownerName'))->toarray();
        $mobileArray = (collect($unstructuredData->owners)->pluck('mobileNo'))->toarray();
        $emailArray = (collect($unstructuredData->owners)->pluck('email'))->toarray();
        $structuredData = [
            "id" => $unstructuredData->id,
            "user_name" => implode(',', $ownerArray) ?? null,
            "mobile" => implode(',', $mobileArray) ?? null,
            "email" => implode(',', $emailArray) ?? null,
            "email_verified_at" => "",
            "user_type" => "Citizen",
            "ulb_id" => "",
            "suspended" => "",
            "super_user" => "",
            "description" => "",
            "address" => $unstructuredData->prop_address,
            "propertyId" => $unstructuredData->id ?? "",
            "holding_no" => $unstructuredData->holding_no ?? "",
            "saf_no" => $unstructuredData->saf_no ?? ""
        ];
        return $structuredData;
    }


    /**
     * | Get user transaction details for for respective module
        | Serial No :
        | Under Con
        | Get proper api for module details 
     */
    public function getTransactionDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "citizenId" => "required|numeric",
                "moduleId" => "required"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $moduleId       = $request->moduleId;
            $mModuleMaster  = new ModuleMaster();
            $mApiMaster     = new ApiMaster();
            $confModuleIds  = $this->_moduleIds;
            $msg            = "User transaction details in!";

            $listOfModule = $mModuleMaster->getModuleList()->get();
            $moduleIds = collect($listOfModule)->pluck('id');
            if (!in_array($moduleId, $moduleIds->toArray())) {
                throw new Exception("Provided module Id $moduleId is invalid!");
            }

            $transferData = [
                //"auth"      => $request->auth,
                "citizenId" => $request->citizenId
            ];
            switch ($moduleId) {
                case ($confModuleIds['WATER']):
                    $endPoint = "192.168.0.240:84/api/water/grievance/get-user-transactions";                                       // Static
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $returnData = $this->structureWaterTranData($unstructuredData);
                    break;
                case ($confModuleIds['PROPERTY']):
                    $endPoint = "192.168.0.240:84/api/property/get-user-transaction-details";                                       // Static
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $unstructuredData = array_merge($unstructuredData->propTransaction, $unstructuredData->safTransaction);
                    $returnData = $this->structurePropTranData($unstructuredData);
                    break;
                case ($confModuleIds['TRADE']):
                    $endPoint = "192.168.0.211:8087/api/trade/application/citizen-history";                                         // Static
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $returnData = $this->structureTradeTranData($unstructuredData);
                    break;
                default:
                    throw new Exception("Module dont exist!");
                    break;
            }

            if (!$returnData || empty($returnData)) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($returnData), "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Structer the water transaction data
        | Serial No :
        | Under Con 
     */
    public function structureWaterTranData($unstructuredData)
    {
        if ($unstructuredData) {
            $filteredData = collect($unstructuredData)->map(function ($value) {
                return [
                    "id"            => $value->id ?? null,
                    "tranNo"        => $value->tran_no ?? null,
                    "amount"        => $value->amount ?? null,
                    "tranDate"      => $value->tran_date ?? null,
                    "tranType"      => $value->tran_type ?? null,
                    "status"        => $value->status ?? null,
                    "paymentMode"   => $value->payment_mode ?? null
                ];
            });
            return $filteredData->toArray();
        }
    }


    /**
     * | Structure the trade transaction data
        | Serial No :
        | Under Con
     */
    public function structureTradeTranData($unstructuredData)
    {
        $transactionData = collect($unstructuredData->tranDtl)->map(function ($value, $key) {
            if (!empty($value->dtl)) {
                return collect($value->dtl)->first();
            }
        })->filter();
        if ($transactionData || !empty($transactionData)) {
            $filteredData = collect($transactionData)->map(function ($value) {
                return [
                    "id"            => $value->id ?? null,
                    "tranNo"        => $value->tran_no ?? null,
                    "amount"        => $value->paid_amount ?? null,
                    "tranDate"      => $value->tran_date ?? null,
                    "tranType"      => $value->tran_type ?? null,
                    "status"        => $value->status ?? null,
                    "paymentMode"   => $value->payment_mode ?? null
                ];
            });
            return $filteredData->toArray();
        }
    }


    /**
     * | Structure property  transaction data 
     */
    public function structurePropTranData($unstructuredData)
    {
        if (!empty($unstructuredData)) {
            $filteredData = collect($unstructuredData)->map(function ($value) {
                return [
                    "id"            => $value->id ?? null,
                    "tranNo"        => $value->tran_no ?? null,
                    "amount"        => $value->amount ?? null,
                    "tranDate"      => $value->tran_date ?? null,
                    "tranType"      => $value->tran_type ?? null,
                    "status"        => $value->status ?? null,
                    "paymentMode"   => $value->payment_mode ?? null
                ];
            });
            return $filteredData->toArray();
        };
    }



    /**
     * | Get the recent activity details 
        | Serial No :
        | Under Con
     */
    public function getMasterQuestions(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "questionId" => "nullable|int"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $mMGrievanceQuestion = new MGrievanceQuestion();
            if ($request->questionId && $request->questionId != 0) {
                $parentQuestions = $mMGrievanceQuestion->getQuestionListById($request->questionId)->get();
            } else {
                $parentQuestions = $mMGrievanceQuestion->getAllQuestionList()
                    ->where('parent_question_id', 0)
                    ->orWhereNull('parent_question_id')
                    ->get();
            }
            # this get all process
            $this->_masterQuestion = $mMGrievanceQuestion->getAllQuestionList()
                ->whereNot('parent_question_id', 0)
                ->get();
            $nestedData     = $this->getMultipleLevelNesting($parentQuestions);
            $refPropData    = (collect($nestedData)->where('module', 'property'));
            $refWaterData   = (collect($nestedData)->where('module', 'water'));
            $refTradeData   = (collect($nestedData)->where('module', 'trade'));
            $refAdvData     = (collect($nestedData)->where('module', 'advertisement'));
            $returnData = [
                "property"      => $refPropData->values(),
                "trade"         => $refTradeData->values(),
                "advertisement" => $refAdvData->values(),
                "water"         => $refWaterData->values()
            ];
            return responseMsgs(true, 'List of questions!', $returnData, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Get the multiple level nesting 
        | Serial No :
        | Under Con :
     */
    public function getMultipleLevelNesting($parentQuestions)
    {
        # Get formatted data 
        $consDatabase = collect($this->_masterQuestion);
        $finalizeQuestion = collect($parentQuestions)->map(function ($value)
        use ($consDatabase) {
            # Check if the question have child
            $childQuestions = $consDatabase->where('parent_question_id', $value->id)
                ->where('status', true)
                ->sortByDesc('id');
            if (!collect($childQuestions)->first()) {
                $value['childQuestions'] = [];
                return $value;
            }

            # Etarate the child process
            $subChildQuestion = collect($childQuestions)->map(function ($secondValue)
            use ($consDatabase) {
                $childQuestions = $consDatabase->where('parent_question_id', 1)
                    ->where('status', true)
                    ->sortByDesc('id');
                if (!collect($childQuestions)->first()) {
                    $secondValue['childQuestions'] = [];
                    return $secondValue;
                }
                $associatedChild = $this->getMultipleLevelNesting($childQuestions);
                $secondValue['childQuestions'] = $associatedChild;
                return $secondValue;
            });
            # Format the data 
            $value['childQuestions'] = ($subChildQuestion->filter())->toArray();
            return $value;
        });

        return ($finalizeQuestion->filter())->toArray();
    }


    /**
     * | Get details of user applications
        | Serial No :
        | Under Con
        | Data filteration of raw data from http is req. hence the key may be diff 
     */
    public function getUserApplicationList(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "citizenId" => "required|numeric",
                "moduleId" => "required"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            # Variable assigning
            $citizenId      = $request->citizenId;
            $moduleId       = $request->moduleId;
            $mModuleMaster  = new ModuleMaster();
            $mApiMaster     = new ApiMaster();
            $confModuleIds  = $this->_moduleIds;

            # Check the existence of module 
            $listOfModule = $mModuleMaster->getModuleList()->get();
            $moduleIds = collect($listOfModule)->pluck('id');
            if (!in_array($moduleId, $moduleIds->toArray())) {
                throw new Exception("Provided module Id $moduleId is invalid!");
            }

            # Http paylode
            $transferData = [
                "citizenId" => $citizenId,
                "userId" => $citizenId
            ];
            # distinguishing the module wise API 
            switch ($moduleId) {
                case ($confModuleIds['WATER']):
                    $endPoint = "192.168.0.240:84/api/water/application/citizen-application-list";
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $returnData = $this->structureDataForWater($unstructuredData);
                    break;
                case ($confModuleIds['PROPERTY']):
                    $transferData["auth"] = $request->auth;
                    $endPoint = "192.168.0.240:84/api/property/citizens/applied-applications";
                    $transferData['module'] = "Property";                                               // Static
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $returnData = $this->structureDataForProperty($unstructuredData);
                    break;
                case ($confModuleIds['TRADE']):
                    $endPoint = "192.168.0.211:8087/api/trade/application/citizen-application-list";
                    $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
                    $unstructuredData = $httpResponse->data;
                    $returnData = $this->structureDataForTrade($unstructuredData);
                    break;
            }


            # Data filteration is reqired for the raw data 
            return responseMsgs(true, "User Application details in !", $returnData, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Common function for calling http request 
        | Serial No :
        | Under Con :
     */
    public function launchHttpRequest($endPoint, $transferData)
    {
        $rawData = Http::withHeaders([
            'Authorization' => "Bearer " . "4716|I0JgiL4O5pwk2UyCkURr4IdWeKevLXI2L9xBflez" //collect($request->all())['token'],
        ])->post("$endPoint", $transferData);

        $httpReqData = json_decode($rawData);
        if (!$httpReqData) {
            throw new Exception("Data not found or the api not responding!");
        }
        if ($httpReqData->status == false) {
            throw new Exception($httpReqData->message ?? "Error in calling Http request!");
        }
        return $httpReqData;
    }


    /**
     * | get the data in structured format for display
        | Serial No :
        | Under Con
     */
    public function structureDataForWater($unstructuredData)
    {
        $filteredData = collect($unstructuredData)->map(function ($value) {
            return [
                "id"                => $value->id,
                "applicationNo"     => $value->application_no,
                "applyDate"         => $value->apply_date,
                "applicationType"   => $value->category,
                "ownerName"         => $value->applicantname,
                "guardianName"      => $value->guardianname,
                "email"             => $value->email,
                "paymentStatus"     => $value->payment_status,
                "docUploadStatus"   => $value->doc_upload_status,
                "currentRole"       => $value->current_role ?? "",
                "fieldVerified"     => $value->is_field_verified,
                "propType"          => $value->property_no_type,
            ];
        });
        return $filteredData->toArray();
    }

    /**
     * | Structure the trade raw data
        | Serial No : 
        | Under Con 
     */
    public function structureDataForTrade($unstructuredData)
    {
        $filteredData = collect($unstructuredData)->map(function ($value) {
            return [
                "id"                => $value->id,
                "applicationNo"     => $value->application_no,
                "applyDate"         => $value->application_date,
                "applicationType"   => $value->application_type,
                "ownerName"         => $value->owner_name,
                "guardianName"      => $value->guardian_name,
                "email"             => $value->email_id,
                "paymentStatus"     => $value->payment_status,
                "docUploadStatus"   => $value->document_upload_status,
                "currentRole"       => $value->currentRoleName ?? "",
                "fieldVerified"     => $value->pending_status ?? "",
                "propType"          => $value->license_type ?? "",
            ];
        });
        return $filteredData->toArray();
    }


    /**
     * | Structure the property data 
        | Serial No :
        | Under Cons
     */
    public function structureDataForProperty($unstructuredData)
    {
        $details = (collect($unstructuredData)->first());
        $data = collect($details)->map(function ($values) {
            return $values;
        })->filter();
        if (!$data->first()) {
            return [];
        };
        $filteredData = collect($unstructuredData)->map(function ($value) {
            return [
                "id"                => $value->id,
                "applicationNo"     => $value->application_no,
                "applyDate"         => $value->application_date,
                "applicationType"   => $value->application_type,
                "ownerName"         => $value->owner_name,
                "guardianName"      => $value->guardian_name,
                "email"             => $value->email_id,
                "paymentStatus"     => $value->payment_status,
                "docUploadStatus"   => $value->document_upload_status,
                "currentRole"       => $value->currentRoleName ?? "",
                "fieldVerified"     => $value->pending_status ?? "",
                "propType"          => $value->license_type ?? "",
            ];
        });
        return $filteredData->toArray();
    }


    /**
     * | Get User's Application full details 
        | Serial No :
        | Under Con :  
     */
    public function getUserApplicationDetails(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "id" => "required|numeric",
                "moduleId" => "required"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            # Variable assigning
            $applicationId  = $request->id;
            $moduleId       = $request->moduleId;
            $mModuleMaster  = new ModuleMaster();
            $mApiMaster     = new ApiMaster();
            $confModuleIds  = $this->_moduleIds;

            # Check the existence of module 
            $listOfModule = $mModuleMaster->getModuleList()->get();
            $moduleIds = collect($listOfModule)->pluck('id');
            if (!in_array($moduleId, $moduleIds->toArray())) {
                throw new Exception("Provided module Id $moduleId is invalid!");
            }

            # Http paylode
            $transferData = [
                "auth"      => $request->auth,
                "id"        => $applicationId
            ];
            # distinguishing the module wise API 
            switch ($moduleId) {
                case ($confModuleIds['WATER']):
                    $endPoint = "http://192.168.0.240:84/api/water/application/status";
                    break;
                case ($confModuleIds['PROPERTY']):
                    $endPoint = "prop_endpoint";
                    break;
                case ($confModuleIds['TRADE']):
                    $endPoint = "192.168.0.211:8002/api/trade/application/status";
                    break;
            }
            $httpResponse = $this->launchHttpRequest($endPoint, $transferData);
            $returnData = $httpResponse->data;
            # Data filteration is reqired for the raw data 
            return responseMsgs(true, "User Application details in !", $returnData, "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Search the Questions with the help of module and the question
        | Serial No :
        | Under Con
     */
    public function searchMasterQuestions(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "question"  => "required|",
                "moduleId"  => "required",
                "pages"     => "nullable|int"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $words = explode(' ', $request->question);
            $wordCount = count($words);
            if ($wordCount < 3) {
                return response()->json([
                    'status'    => false,
                    'message'   => "Validation Error!",
                    'error'     => ["question" => ["The question should contain atleast three words!."]]
                ], 422);
            }
            $msg = "List of Questions!";
            $confDatabaseName = $this->_databaseName;
            $pages = $request->pages > 50 || !$request->pages ? $pages = 10 : $pages = $request->pages;
            $mMGrievanceQuestion = new MGrievanceQuestion();

            # getting the grammar set list
            $articlesSet = array_flip($this->_grammarList);
            $inputText = $request->question;
            $words = preg_split("/[^A-Za-z0-9]+/", $inputText);

            # Filter out words that are in the $articles set
            $filteredWords = array_filter($words, function ($word) use ($articlesSet) {
                return !isset($articlesSet[strtolower($word)]);
            });
            if (count($filteredWords) < 2) {
                $question = $request->question;
            } else {
                $question = implode(" ", $filteredWords);
            }

            # Querry for search
            $rawSql = "SELECT *
            FROM " . $confDatabaseName['M_GRIEVANCE_QUESTION'] . "
            WHERE to_tsvector('english', questions) @@ plainto_tsquery('english', '" . $question . "')";
            $questionQuerry = $mMGrievanceQuestion->searchQuestions($request->moduleId);
            $questionList = $questionQuerry->whereIn('id', function ($query) use ($rawSql) {
                $query->select('id')
                    ->from(DB::raw("($rawSql) as subquery"));
            })->limit($pages)->get();

            if (!collect($questionList)->first()) {
                $msg = "Data not found!";
            }
            return responseMsgs(true, $msg, remove_null($questionList), "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Approve or post to the next level verification
        | Serial No :
        | Under Con   
        | Look for the process of sending to wf
     */
    public function closePassGrievance(closeGrievanceReq $request)
    {
        try {
            $now    = Carbon::now();
            $user   = authUser($request);
            $mGrievanceClosedQuestion   = new GrievanceClosedQuestion();
            $objGrievanceController     = new GrievanceController();

            $this->begin();
            switch ($request->status) {
                case ('yes'):
                    $msg = "Grievance closed!";
                    $request->merge([
                        "applyDate" => $request->applyDate ?? $now,
                        "closeDate" => $now,
                        "initiator" => $request->initiator ?? $user->id,
                        "finisher"  => $user->id,
                    ]);
                    $mGrievanceClosedQuestion->saveClosedQuestionData($request, null);
                    break;

                case ("no"):
                    $msg = "Grievance Passed to wf for solution!";
                    $status = 2;                                                // Static
                    $request->merge([
                        "applyDate"     => $request->applyDate,
                        "closeDate"     => null,
                        "initiator"     => $user->id,
                        "finisher"      => null,
                        "inWorkflow"    => 1,                                   // Static
                        "priority"      => $request->setPriority
                    ]);
                    $mGrievanceClosedQuestion->saveClosedQuestionData($request, $status);
                    // $newRequest = new Request([
                    //     "auth" => $request->auth
                    // ]);
                    // $objGrievanceController->registerGrievance($newRequest);
                    break;
            }
            $this->commit();
            return responseMsgs(true, $msg, [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Get the dashboard data for agrancy
        | Serial No :
        | Under Con
        | Test ground
        | Make changs for all dashboard data and diff from agency,jsk and wf members
     */
    public function getDashboardDetails(Request $request)
    {
        try {


            // $req->input

            $articles = [
                "a", "an", "the", "and", "but", "or", "for", "nor", "so", "yet", "in",
                "on", "at", "by", "with", "about", "before", "after", "during", "under",
                "over", "between", "through", "above", "below", "I", "you", "he", "she", "it",
                "we", "they", "me", "him", "her", "us", "them", "am", "is", "are", "was", "were",
                "be", "being", "been", "do", "does", "did", "have", "has", "had", "shall", "will",
                "should", "would", "may", "might", "must", "can", "could", "this", "that", "these",
                "those", "my", "your", "his", "her", "its", "our", "their", "oh", "wow", "ouch", "hey",
                "hello", "hi"
            ];

            strpos($request->value, $articles[0]);


            $array = [3523, 33];
            return collect($array)->map(function ($value) {
                $string = $value;
                if (strpos($value, "'")) {
                    return $string = "'" . $value . "'";
                }
                $string = $string[(strlen($string) - 2)] . $string[(strlen($string) - 1)];
                if (strlen($string) == 2) {
                    if ($string[1] === $string[0]) {
                        return $value;
                    };
                }
            })->filter()->values();
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Forward the application to AMP/PM /Agency action
        | Serial No :
        | Under Con 
        | Get the role according to workflow / Recheck
     */
    public function forwardToAmp(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "question"      => "required|",
                "moduleId"      => "required|int",
                "priorities"    => "required|int",
                "remarks"       => "nullable|",
                "userId"        => "required|",
                "mobileNo"      => "nullable|"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $confRoles = $this->_grievanceRoleLevel;
            $mGrievanceActiveQuestion = new GrievanceActiveQuestion();
            $request->merge([
                "initiator"     => $confRoles['APM'],
                "current_role"  => $confRoles['APM']
            ]);
            $mGrievanceActiveQuestion->saveQuestion($request);
            return responseMsgs(true, "Application forwarded!", [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Get the list of Question that are forwarded to Respondent 
        | Serial No :
        | Under Con
     */
    public function getActiveQuestions(Request $request)
    {
        try {
            $user   = authUser($request);
            $pages  = $request->pages ?? 10;
            $ulbId  = $user->ulb_id ?? 2;                                        // Static
            $mGrievanceActiveQuestion = new GrievanceActiveQuestion();
            $mWfWorkflow = new WfWorkflow();
            $refWorkflow = $this->_workflowMstId;

            # Check user detials
            // $ulbWorkflowId = $mWfWorkflow->getulbWorkflowId($refWorkflow, $ulbId);
            // if (!$ulbWorkflowId) {
            //     throw new Exception("Respective Ulb is not maped to Water Workflow!");
            // }
            // $request->merge([
            //     "workflowId" => $ulbWorkflowId->id
            // ]);
            // $roleDetails = $this->getRole($request);
            // if (!collect($roleDetails)->first()) {
            //     throw new Exception("Role not found!");
            // }

            $appliedQuestions = $mGrievanceActiveQuestion->listActiveQuestions()
                // ->where('current_role', $roleDetails['wf_role_id'])
                ->paginate($pages);
            return responseMsgs(true, "Question list!", remove_null($appliedQuestions), "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Close the question according to details
        | Serial No :
        | Under Con  
        | Remove the validation comment 
     */
    public function closeQuestionByLevel(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "questionId"    => "required|int",
                "answers"       => "required"
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $now                        = Carbon::now();
            $user                       = authUser($request);
            $questionId                 = $request->questionId;
            $mGrievanceClosedQuestion   = new GrievanceClosedQuestion();
            $mGrievanceActiveQuestion   = new GrievanceActiveQuestion();
            $msg                        = "Grievance closed!";
            $confStatus                 = $this->_solvedStatus;

            $activeQuestion = $mGrievanceActiveQuestion->listActiveQuestions()
                ->where('id', $questionId)
                ->first();
            // $this->checkParamForClose($activeQuestion, $user);
            $request->merge([
                "applyDate"     => $activeQuestion->apply_date,
                "closeDate"     => $now,
                "initiator"     => $activeQuestion->initiator,
                "finisher"      => $user->id,
                "questionId"    => $questionId,
                "ans"           => $request->answers,
                "moduleId"      => $activeQuestion->module_id,
                "remarks"       => $activeQuestion->remarks,
                "priority"      => $activeQuestion->priority,
            ]);
            $metaBody = [
                "status" => $confStatus['CLOSED']
            ];
            $this->begin();
            $mGrievanceClosedQuestion->saveClosedQuestionData($request, null);
            $mGrievanceActiveQuestion->updateDetails($metaBody, $questionId);
            $this->commit();
            return responseMsgs(true, "Question list!", [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            $this->rollback();
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Check param for close 
        | Serial No :
        | Under Con
     */
    public function checkParamForClose($activeQuestion, $user)
    {
        $roleId = $this->getRoleIdByUserId($user->id)->pluck('wf_role_id');
        if (!$activeQuestion) {
            throw new Exception("Active Question not found!");
        }
        if (!in_array($activeQuestion->current_role, $roleId->toArray())) {
            throw new Exception("You Dont have the autherised role!");
        }
    }


    /**
     * | send the querry question to Ts
        | Seria No :
        | Under Con
        | Uncomment the code for validation
     */
    public function forwardToTs(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "questionId"    => "required|",
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $confRoles = $this->_grievanceRoleLevel;
            $mGrievanceActiveQuestion = new GrievanceActiveQuestion();
            $activeQuestion = $mGrievanceActiveQuestion->getActiveQuestionById($request->questionId)->first();
            if (!$activeQuestion) {
                throw new Exception("Active question not found!");
            }
            // $this->checkParamForTs($request, $activeQuestion);
            $metaReq = [
                "current_role" => $confRoles['TS']
            ];
            $mGrievanceActiveQuestion->updateDetails($metaReq, $request->questionId);
            return responseMsgs(true, "Application forwarded!", [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


    /**
     * | Check the params for forwerding to Ts
        | Serial No :
        | Under Con
     */
    public function checkParamForTs($request, $activeQuestion)
    {
        $user           = authUser($request);
        $mWfWorkflow    = new WfWorkflow();
        $refWorkflow    = $this->_workflowMstId;
        $confRoles      = $this->_grievanceRoleLevel;
        $ulbId          = $user->ulb_id ?? 2;                                        // Static
        # Check user detials
        $ulbWorkflowId = $mWfWorkflow->getulbWorkflowId($refWorkflow, $ulbId);
        if (!$ulbWorkflowId) {
            throw new Exception("Respective Ulb is not maped to Water Workflow!");
        }
        $request->merge([
            "workflowId" => $ulbWorkflowId->id
        ]);
        $roleDetails = $this->getRole($request);
        if (!collect($roleDetails)->first()) {
            throw new Exception("Role not found!");
        }
        if ($roleDetails['wf_role_id'] != $confRoles['APM']) {
            throw new Exception("you are not autherised to forward!");
        }
    }


    /**
     * | P1 serch the user details for the grievance according to parmeter, module
     * | Use in the first search criteria
        | Serial No :
        | Under Con
     */
    public function getUserDetailsV2(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "filterBy"  => "required|",
                "module"    => "nullable|",
                "parameter" => "required|",
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }


















    // /**
    //  * | Get set of questions for grievance
    //     | Serial No :
    //     | Under Con :
    //  */
    // public function addJson(Request $request)
    // {
    //     $array = [
    //         [
    //             "questionId" => 1,
    //             "module" => "water",
    //             "title" => "How is water quality monitored in our municipality dipu?",
    //             "description" => "Water quality monitoring in our municipality is a comprehensive process aimed at ensuring the delivery of safe and clean drinking water to our residents. The process involves continuous surveillance, sampling, and analysis of water from various sources, including reservoirs, treatment plants, distribution networks, and even household taps.\n\nHighly trained water quality experts use state-of-the-art equipment to detect any potential contaminants or anomalies in the water supply. These professionals regularly collect samples from strategic locations, and the collected data is analyzed for various parameters, including chemical composition, microbial content, and physical characteristics.\n\nAdditionally, our municipality has implemented strict regulatory standards in compliance with national and international guidelines to maintain water quality. Regular inspections and audits are conducted to ensure the proper functioning of treatment facilities and distribution systems.\n\nOur commitment to water quality extends to transparency and public awareness. We provide regular updates to residents on the status of water quality, any detected issues, and the steps taken for remediation. Our goal is to uphold the highest standards of water quality to safeguard the health and well-being of our community."
    //         ],
    //         [
    //             "questionId" => 2,
    //             "module" => "property",
    //             "title" => "What is the procedure for property tax assessment?",
    //             "description" => "The procedure for property tax assessment in our municipality is a critical aspect of revenue collection and the provision of essential public services. Property taxes play a crucial role in funding infrastructure development, education, public safety, and more.\n\nProperty tax assessment involves a series of steps to determine the taxable value of real estate properties within our jurisdiction. These steps include property valuation, classification, and calculation of the tax amount owed by property owners.\n\n1. Property Valuation: Trained assessors or appraisers evaluate each property to determine its fair market value. Factors such as location, property size, improvements, and market trends are considered during this process.\n\n2. Classification: Properties are categorized into different classes based on their use, such as residential, commercial, industrial, or agricultural. Each class may have a different tax rate or assessment methodology.\n\n3. Tax Calculation: The assessed value of the property is multiplied by the applicable tax rate to calculate the property tax amount. In some cases, exemptions or deductions may apply, depending on local regulations and policies.\n\n4. Notification: Property owners are notified of their assessed value and tax liability. They are given the opportunity to review and appeal the assessment if they believe it is inaccurate.\n\n5. Payment: Property taxes are typically collected annually or in installments. Property owners are responsible for paying their taxes by the due date to avoid penalties or interest charges.\n\nOur municipality is committed to transparency and fairness in property tax assessment. We provide property owners with access to information about the assessment process and their rights to appeal. Additionally, tax revenue is utilized to benefit the entire community through vital services and infrastructure development."
    //         ],
    //         [
    //             "questionId" => 3,
    //             "module" => "trade",
    //             "title" => "What types of businesses require a trade license in our city?",
    //             "description" => "In our city, various types of businesses are subject to trade licensing requirements to ensure regulatory compliance, consumer protection, and public safety. Obtaining a trade license is a fundamental step for entrepreneurs and business owners looking to operate legally within our jurisdiction.\n\nThe types of businesses that typically require a trade license include:\n\n1. Retail Businesses: Retailers, including stores, boutiques, and shops selling goods directly to consumers, often need a trade license.\n\n2. Restaurants and Food Establishments: Restaurants, cafes, food vendors, and catering services are generally required to obtain licenses to ensure food safety and hygiene standards are met.\n\n3. Professional Services: Individuals and firms offering professional services, such as legal, medical, or accounting services, often require trade licenses.\n\n4. Construction and Contracting: Contractors, builders, and construction firms must comply with licensing regulations to engage in construction projects.\n\n5. Manufacturing and Industrial Activities: Manufacturing plants, factories, and industrial facilities may need licenses to operate safely and within environmental regulations.\n\n6. Health and Wellness: Businesses in the health and wellness sector, including fitness centers, spas, and healthcare providers, may be subject to licensing requirements.\n\n7. Entertainment and Events: Event organizers, theaters, and entertainment venues often need licenses for events and performances.\n\n8. Transportation and Logistics: Taxi services, logistics companies, and transportation providers may require trade licenses to operate legally.\n\n9. Home-Based Businesses: Even home-based businesses may need licenses, depending on local zoning and business activities.\n\nIt's important for prospective business owners to research and understand the specific licensing requirements that apply to their industry and location. Licensing procedures may involve documentation, inspections, fees, and adherence to local regulations. Failure to obtain a required trade license can result in legal consequences, fines, or business closure. Our city's licensing authorities are available to provide guidance and support to businesses throughout the licensing process, contributing to a thriving and compliant business community."
    //         ],
    //         [
    //             "questionId" => 4,
    //             "module" => "advertisement",
    //             "title" => "What are the regulations for placing advertisements on public property?",
    //             "description" => "Placing advertisements on public property in our city is subject to specific regulations designed to maintain aesthetics, safety, and fairness in advertising. These regulations aim to strike a balance between allowing businesses to promote their products or services and preserving the visual integrity of public spaces.\n\nHere are some key regulations for advertising on public property:\n\n1. Permits and Approvals: Generally, businesses and advertisers must obtain permits or approvals from local authorities before placing advertisements on public property. This process ensures compliance with zoning, safety, and aesthetic standards.\n\n2. Size and Placement: Regulations often specify the maximum size, height, and location of advertisements. Advertisements should not obstruct traffic visibility, impede pedestrian movement, or compromise safety.\n\n3. Content Restrictions: Advertisements must comply with content restrictions, such as avoiding offensive, misleading, or harmful content. Political campaign signage may have separate guidelines.\n\n4. Maintenance: Advertisers are typically responsible for the maintenance and removal of their advertisements within specified timeframes. This includes addressing damage or wear to the advertisement.\n\n5. Fees and Costs: There may be fees associated with obtaining permits, as well as costs for installation, maintenance, and removal. These fees contribute to city revenue and support maintenance efforts.\n\n6. Duration: Regulations often define the maximum duration an advertisement can be displayed on public property. Temporary or seasonal advertisements may have shorter display periods.\n\n7. Enforcement: Local authorities enforce these regulations, and non-compliance can result in fines, removal of advertisements, or legal actions.\n\nPublic property includes various locations, such as parks, bus shelters, public buildings, and roadways. Businesses and advertisers should consult with local authorities or municipal departments responsible for regulating advertising to ensure full compliance with applicable rules and guidelines. By adhering to these regulations, businesses can promote their products or services responsibly while contributing to the visual appeal and safety of our city's public spaces."
    //         ],
    //         [
    //             "questionId" => 5,
    //             "module" => "water",
    //             "title" => "How can residents report a water leak or broken pipe?",
    //             "description" => "Residents in our municipality play a crucial role in helping to maintain the integrity of our water supply system. Reporting water leaks or broken pipes promptly is essential to prevent water loss, minimize infrastructure damage, and ensure the continued delivery of safe and clean water to our community.\n\nHere are the steps for residents to report a water leak or broken pipe:\n\n1. Identify the Issue: If you notice any signs of a water leak or broken pipe, such as water pooling in unusual areas, a sudden decrease in water pressure, or water discoloration, take note of the location and any relevant details.\n\n2. Contact the Water Department: Reach out to the municipality's Water Department or the designated water utility provider's emergency contact number. These numbers are typically available on your water bill or the official municipality website.\n\n3. Provide Information: When reporting the issue, be prepared to provide specific information, such as the location of the leak, the severity of the problem, and any potential safety hazards.\n\n4. Follow Instructions: Follow any instructions provided by the water department or utility provider. They may ask you to turn off your water supply or take other precautionary measures.\n\n5. Monitor the Situation: While waiting for the response team to arrive, continue to monitor the situation to ensure the issue does not worsen.\n\n6. Repair and Restoration: Once the water department or utility provider assesses the situation, they will take appropriate measures to repair the leak or broken pipe. This may involve excavation, pipe replacement, or other repairs.\n\n7. Water Quality: After repairs are completed, be attentive to water quality. Run your taps for a few minutes to clear any air or debris from the pipes, and report any water quality concerns to the authorities.\n\nReporting water leaks promptly is essential for minimizing water wastage and preventing potential damage to property and infrastructure. It also ensures that residents continue to receive high-quality water services. Our municipality is dedicated to responding swiftly to such reports, prioritizing water conservation and the well-being of our community."
    //         ],
    //         [
    //             "questionId" => 6,
    //             "module" => "property",
    //             "title" => "What is the penalty for failing to pay property taxes on time?",
    //             "description" => "Failing to pay property taxes on time can have significant consequences for property owners in our municipality. Property taxes are a vital source of revenue that funds essential public services, including schools, infrastructure, and emergency services. Timely payment of property taxes is not only a legal obligation but also a civic responsibility.\n\nWhen property taxes are not paid on time, the municipality may impose penalties and interest charges. These penalties serve as incentives for property owners to meet their tax obligations promptly. Here are some key points to understand:\n\n1. Late Payment Penalties: Property owners who miss the due date for property tax payments may incur late payment penalties. These penalties are typically calculated as a percentage of the overdue tax amount and can vary depending on local regulations.\n\n2. Interest Charges: In addition to late payment penalties, interest charges may be applied to the unpaid tax balance. Interest rates and compounding methods are defined by local tax authorities.\n\n3. Accumulated Debt: Failure to address property tax arrears can result in accumulated debt over time, making it increasingly challenging to catch up on payments.\n\n4. Tax Lien or Sale: In extreme cases of prolonged non-payment, the municipality may place a tax lien on the property or initiate a tax sale process. A tax lien gives the municipality the right to collect the overdue taxes by selling the property, which can have serious implications for property owners.\n\n5. Legal Action: Property owners who persistently avoid paying property taxes may face legal action, including court proceedings and property seizure.\n\nTo avoid these consequences, property owners are strongly encouraged to prioritize property tax payments and adhere to the specified due dates. Many municipalities offer various payment options, including online payments and installment plans, to facilitate timely tax payments. Additionally, property owners should proactively seek information from the municipality's tax department to understand their tax obligations and any available assistance programs.\n\nOur municipality values responsible tax payment as a means of sustaining our community and providing vital services to all residents."
    //         ],
    //         [
    //             "questionId" => 7,
    //             "module" => "trade",
    //             "title" => "How can I apply for a street vendor license?",
    //             "description" => "Operating as a street vendor in our city can be an exciting and rewarding endeavor, but it requires adherence to specific regulations and obtaining the necessary permits. A street vendor license is essential for conducting business legally and ensuring compliance with local ordinances. Here's a step-by-step guide on how to apply for a street vendor license in our city:\n\n1. Eligibility Check: Determine if you meet the eligibility criteria set by the city for street vending. Eligibility requirements may include age restrictions, citizenship status, and compliance with health and safety standards.\n\n2. Business Plan: Prepare a comprehensive business plan outlining your street vending venture. Include details about the products or services you intend to offer, your target market, and your vending location preferences.\n\n3. Choose a Location: Identify suitable vending locations within the city. Ensure that your chosen location complies with zoning regulations and is not within restricted areas.\n\n4. Obtain Necessary Documents: Gather all required documents, which may include proof of identity, business permits, food handling certificates (if applicable), and any other documentation specified by the city.\n\n5. Complete the Application: Contact the city's licensing department or visit their website to access the street vendor license application form. Fill out the form accurately and completely, attaching all the necessary documents.\n\n6. Pay Fees: Pay the required application fees, which cover processing and inspection costs. Fees may vary depending on the type of vending and the city's fee structure.\n\n7. Inspection and Approval: After receiving your application, city officials may conduct inspections to ensure compliance with health, safety, and zoning regulations. If your application meets all requirements, you will receive approval for your street vendor license.\n\n8. License Issuance: Once approved, you will receive your street vendor license, along with any additional permits required for your specific type of vending.\n\n9. Compliance and Renewal: Adhere to all regulations and conditions specified in your license. Be aware of renewal deadlines and ensure timely license renewals to continue your vending activities legally.\n\nIt's crucial to stay informed about city-specific regulations and any updates related to street vending. Our city is committed to supporting street vendors while maintaining the safety and vibrancy of our public spaces. Feel free to contact our licensing department for guidance and assistance throughout the application process."
    //         ],
    //         [
    //             "questionId" => 8,
    //             "module" => "advertisement",
    //             "title" => "Is there a specific permit required for digital billboards?",
    //             "description" => "In our city, the installation and operation of digital billboards are subject to specific regulations and permit requirements to ensure safety, aesthetics, and compliance with local ordinances. Digital billboards, with their dynamic displays and potential impact on traffic safety and visual landscape, are carefully regulated to balance advertising opportunities with community interests.\n\nHere are the key points to understand regarding digital billboard permits in our city:\n\n1. Permit Application: Individuals or businesses interested in erecting digital billboards must submit a permit application to the city's planning or zoning department. The application typically includes details about the billboard's location, size, lighting, content, and compliance with zoning regulations.\n\n2. Zoning Compliance: Digital billboards must conform to zoning regulations, which dictate where such structures can be placed within the city. Zoning codes often specify minimum distances from residential areas, highways, and other billboards.\n\n3. Lighting and Brightness: Regulations often control the brightness and illumination levels of digital billboards, especially during nighttime hours, to prevent distractions to drivers and disruptions to nearby properties.\n\n4. Content Restrictions: The content displayed on digital billboards must adhere to content guidelines established by the city. Offensive, misleading, or inappropriate content may be prohibited.\n\n5. Permit Fees: Fees associated with digital billboard permits cover application processing, inspections, and ongoing compliance monitoring. These fees contribute to city revenue.\n\n6. Safety and Maintenance: Digital billboard operators are responsible for the safety and maintenance of their structures, including routine inspections, addressing malfunctions, and ensuring structural integrity.\n\n7. Duration and Renewal: Permits for digital billboards are typically granted for specific durations. Operators must renew permits in a timely manner to continue their advertising activities.\n\n8. Public Input: Some municipalities may require public input or hearings regarding the installation of digital billboards, giving residents and stakeholders an opportunity to voice their opinions and concerns.\n\nBy obtaining the necessary permits and adhering to regulations, businesses can benefit from digital billboard advertising while respecting community standards. Our city is dedicated to striking a balance between vibrant advertising opportunities and the well-being of our community. For detailed information on digital billboard permits and requirements, please contact the city's planning or zoning department."
    //         ],
    //         [
    //             "questionId" => 9,
    //             "module" => "water",
    //             "title" => "What conservation measures are in place to protect water resources?",
    //             "description" => "Water conservation is a top priority in our municipality to safeguard our precious water resources, promote sustainability, and ensure a reliable water supply for current and future generations. A comprehensive set of conservation measures and initiatives has been implemented to address the challenges of water scarcity, population growth, and environmental protection. Here are some of the key conservation measures in place:\n\n1. Water-Efficient Appliances: Encouraging the use of water-efficient appliances and fixtures, such as low-flow toilets and high-efficiency washing machines, helps reduce water consumption in households and businesses.\n\n2. Public Education: Our municipality actively promotes water conservation through public awareness campaigns, educational programs, and outreach efforts. Residents are informed about the importance of water conservation and provided with practical tips on reducing water usage.\n\n3. Leak Detection and Repair: Regular inspections and maintenance of water distribution systems help detect and repair leaks promptly, minimizing water losses from aging infrastructure.\n\n4. Xeriscaping: Promoting xeriscaping and drought-tolerant landscaping techniques encourages residents to create water-efficient gardens and landscapes that require less irrigation.\n\n5. Water Recycling: Implementing water recycling and reuse systems in industrial processes, agriculture, and wastewater treatment facilities conserves water resources and reduces the demand on freshwater sources.\n\n6. Drought Contingency Plans: Developing and implementing drought contingency plans allows for proactive response to water scarcity conditions, including water use restrictions and alternative supply sources.\n\n7. Water Pricing: Tiered water pricing structures may be used to incentivize water conservation, where higher consumption levels are charged at higher rates.\n\n8. Rainwater Harvesting: Encouraging rainwater harvesting systems on residential and commercial properties enables the collection and utilization of rainwater for non-potable uses, such as landscaping.\n\n9. Water Quality Protection: Protecting water quality through pollution control measures and watershed management is essential to ensure the availability of clean and safe water.\n\n10. Infrastructure Investment: Our municipality invests in modernizing and expanding water infrastructure to improve efficiency and reduce water losses during distribution.\n\nThese conservation measures reflect our commitment to responsible water management and environmental stewardship. By working together with residents, businesses, and community organizations, we can ensure the long-term sustainability of our water resources and the well-being of our community."
    //         ],
    //         [
    //             "questionId" => 10,
    //             "module" => "property",
    //             "title" => "Can I appeal the property tax assessment if I disagree with it?",
    //             "description" => "Describe the process for appealing property tax assessments."
    //         ],
    //         [
    //             "questionId" => 11,
    //             "module" => "trade",
    //             "title" => "Are there any exemptions for small businesses regarding trade licenses?",
    //             "description" => "Discuss special considerations for small business owners."
    //         ],
    //         [
    //             "questionId" => 12,
    //             "module" => "advertisement",
    //             "title" => "How far should an outdoor advertisement be from a residential area?",
    //             "description" => "Explain the distance regulations for outdoor ads near homes."
    //         ],
    //         [
    //             "questionId" => 13,
    //             "module" => "water",
    //             "title" => "What should I do if I notice discolored water coming from my tap?",
    //             "description" => "Provide guidance on handling water discoloration issues."
    //         ],
    //         [
    //             "questionId" => 14,
    //             "module" => "property",
    //             "title" => "Can I make property tax payments online?",
    //             "description" => "In our digitally connected age, convenience is key, and making property tax payments online is a service offered by our municipality to streamline the process for property owners. Here's how you can take advantage of this convenient option:\n\n1. Online Portal Access: To make property tax payments online, access the official municipal website or tax portal. Ensure that you are on a secure and authorized platform to protect your financial information.\n\n2. Account Registration: If you haven't already, you may need to register for an online account. This typically involves providing your property details, contact information, and creating login credentials.\n\n3. Property Identification: Once logged in, you'll need to identify your property using its unique identifier, such as the parcel or property tax account number.\n\n4. Payment Options: Our online system offers various payment methods, including credit cards, debit cards, electronic funds transfer (EFT), and digital wallets. Select the option that suits you best.\n\n5. Verify Payment Details: Carefully review the payment details, including the amount due, property information, and any applicable fees or discounts.\n\n6. Make the Payment: Follow the on-screen instructions to complete the payment process securely. You'll receive a confirmation of your payment.\n\n7. Receipt and Records: After making the payment, be sure to keep a copy of the electronic receipt for your records. It's proof of payment and may be needed for tax-related documentation.\n\n8. Auto-Pay Setup: To simplify future payments, consider setting up auto-pay if the municipality offers this feature. Auto-pay ensures timely payments without manual intervention.\n\n9. Payment Deadlines: Be aware of property tax due dates to avoid late payment penalties. Online payments should be made well in advance to ensure they are processed on time.\n\nMaking property tax payments online is a secure and efficient way to fulfill your tax obligations. It eliminates the need for physical checks or visits to government offices, saving you time and effort. Our municipality is committed to providing residents with convenient digital services, and online property tax payments are a testament to our dedication to modernizing government processes."
    //         ],
    //         [
    //             "questionId" => 15,
    //             "module" => "trade",
    //             "title" => "Is a health inspection required for businesses that serve food?",
    //             "description" => "Ensuring food safety and protecting public health are paramount concerns for our municipality. Therefore, businesses that serve food are subject to health inspections to prevent foodborne illnesses and maintain high hygiene standards. Here's what you need to know about health inspections for food-service establishments:\n\n1. Mandatory Inspections: Food businesses, including restaurants, food trucks, cafes, and catering services, are typically required to undergo regular health inspections. The frequency of inspections may vary based on local regulations and the type of establishment.\n\n2. Inspection Process: Health inspections are conducted by trained inspectors from the municipal health department. Inspectors evaluate various aspects of food handling, storage, preparation, and cleanliness.\n\n3. Hygiene and Sanitation: Inspectors assess the hygiene practices of food handlers, including handwashing, the use of gloves and hairnets, and the overall cleanliness of food preparation areas.\n\n4. Food Storage: The proper storage of perishable and non-perishable food items is closely scrutinized. This includes checking refrigeration temperatures and ensuring food items are stored in hygienic conditions.\n\n5. Kitchen Equipment: The maintenance and cleanliness of kitchen equipment, such as ovens, stoves, and utensils, are examined to prevent cross-contamination and foodborne illnesses.\n\n6. Pest Control: Inspectors check for the presence of pests, such as rodents and insects, and ensure businesses have effective pest control measures in place.\n\n7. Compliance with Regulations: Food businesses must comply with local health codes and regulations. Inspectors confirm that establishments adhere to these standards.\n\n8. Corrective Actions: If violations or deficiencies are identified during an inspection, the business is typically given a specific timeframe to address and correct them.\n\n9. Public Display: Some municipalities require food businesses to display their health inspection ratings or results publicly, allowing customers to make informed choices.\n\nHealth inspections are conducted with the aim of protecting both consumers and businesses. Compliance with health and sanitation standards not only prevents foodborne illnesses but also contributes to the overall success and reputation of food-service establishments. Businesses are encouraged to collaborate with health inspectors and proactively address any issues to maintain food safety."
    //         ],
    //         [
    //             "questionId" => 16,
    //             "module" => "advertisement",
    //             "title" => "Are there restrictions on the size of advertising banners?",
    //             "description" => "Advertising banners are a common means of promoting businesses and events in our municipality. While we value the promotional opportunities they provide, it's important to maintain aesthetics and safety standards. Therefore, there are regulations in place that may restrict the size of advertising banners. Here's what you need to know:\n\n1. Local Zoning Codes: The size of advertising banners is often regulated through local zoning codes and ordinances. These codes specify permissible dimensions and locations for banners.\n\n2. Size Limits: Zoning regulations may impose size limits on advertising banners, considering factors such as the type of banner, its placement, and the zoning district in which it is located.\n\n3. Permits Required: Depending on the size and placement of a banner, businesses or event organizers may need to obtain permits from the municipality. Permit requirements help ensure compliance with size restrictions and other regulations.\n\n4. Safety Considerations: Large banners can pose safety risks if they obstruct visibility for drivers, pedestrians, or emergency services. Therefore, size restrictions aim to maintain safety on roads and public spaces.\n\n5. Aesthetic Impact: Oversized banners can detract from the visual appeal of an area. Size regulations help maintain the aesthetic integrity of our municipality.\n\n6. Temporary vs. Permanent Banners: Some regulations differentiate between temporary banners, such as those used for events, and permanent banners, which may have different size allowances.\n\n7. Review and Approval: Before installing an advertising banner, it's essential to review the local zoning regulations and obtain any necessary approvals or permits. Failure to do so may result in fines or removal of the banner.\n\nBy adhering to size restrictions and obtaining the required permits, businesses and event organizers can effectively use advertising banners to promote their offerings while contributing to the overall attractiveness and safety of our community. For specific information on banner size limits in our municipality, please consult the local zoning department."
    //         ],
    //         [
    //             "questionId" => 17,
    //             "module" => "water",
    //             "title" => "How often does the municipality conduct water quality tests?",
    //             "description" => "Ensuring the safety and quality of our municipal water supply is a top priority. To achieve this, our municipality conducts regular water quality tests at various stages of the water treatment and distribution process. Here's an overview of our water quality testing procedures:\n\n1. Regulatory Compliance: Our water quality testing protocols comply with federal and state regulations, including guidelines set by the Environmental Protection Agency (EPA). These regulations outline the frequency and parameters for testing.\n"
    //         ],
    //         [
    //             "questionId" => 18,
    //             "module" => "property",
    //             "title" => "Can I claim a property tax deduction for home improvements?",
    //             "description" => "Discuss tax benefits for property improvements."
    //         ],
    //         [
    //             "questionId" => 19,
    //             "module" => "trade",
    //             "title" => "What is the process for renewing a trade license?",
    //             "description" => "Explain the steps to renew a trade license."
    //         ],
    //         [
    //             "questionId" => 20,
    //             "module" => "advertisement",
    //             "title" => "Do I need a permit for temporary advertising signs?",
    //             "description" => "Discuss requirements for temporary signage."
    //         ],
    //         [
    //             "questionId" => 21,
    //             "module" => "water",
    //             "title" => "How can I request a water meter installation at my property?",
    //             "description" => "Detail the procedure for installing a water meter."
    //         ],
    //         [
    //             "questionId" => 22,
    //             "module" => "property",
    //             "title" => "What happens if I miss the deadline for property tax payment?",
    //             "description" => "Explain the consequences of late property tax payments."
    //         ],
    //         [
    //             "questionId" => 23,
    //             "module" => "trade",
    //             "title" => "Is there a fee for applying for a trade license?",
    //             "description" => "Provide information on license application fees."
    //         ],
    //         [
    //             "questionId" => 24,
    //             "module" => "advertisement",
    //             "title" => "Can I advertise political campaigns on public property?",
    //             "description" => "Discuss regulations for political advertising."
    //         ],
    //         [
    //             "questionId" => 25,
    //             "module" => "water",
    //             "title" => "What measures are in place to conserve water during droughts?",
    //             "description" => "Explain drought management strategies."
    //         ],
    //         [
    //             "questionId" => 26,
    //             "module" => "property",
    //             "title" => "How is property tax calculated, and what factors influence it?",
    //             "description" => "Detail the property tax calculation process."
    //         ],
    //         [
    //             "questionId" => 27,
    //             "module" => "trade",
    //             "title" => "Can I transfer my trade license to a new owner?",
    //             "description" => "Explain the process of transferring a trade license."
    //         ],
    //         [
    //             "questionId" => 28,
    //             "module" => "advertisement",
    //             "title" => "What are the rules for illuminated signs in commercial areas?",
    //             "description" => "Discuss regulations for illuminated commercial signage."
    //         ],
    //         [
    //             "questionId" => 29,
    //             "module" => "water",
    //             "title" => "How can I request a water usage report for my property?",
    //             "description" => "Detail the procedure for obtaining a water usage report."
    //         ],
    //         [
    //             "questionId" => 30,
    //             "module" => "property",
    //             "title" => "What tax incentives are available for historic properties?",
    //             "description" => "Discuss tax benefits for preserving historic properties."
    //         ],
    //         [
    //             "questionId" => 31,
    //             "module" => "trade",
    //             "title" => "Are there specific zoning regulations for different types of businesses?",
    //             "description" => "Explain zoning requirements for various business types."
    //         ],
    //         [
    //             "questionId" => 32,
    //             "module" => "advertisement",
    //             "title" => "How can I apply for a permit to install a billboard?",
    //             "description" => "Detail the process for obtaining a billboard installation permit."
    //         ],
    //         [
    //             "questionId" => 33,
    //             "module" => "water",
    //             "title" => "What steps should I take if I suspect a water supply contamination issue?",
    //             "description" => "Provide guidance on handling water supply contamination concerns."
    //         ],
    //         [
    //             "questionId" => 34,
    //             "module" => "property",
    //             "title" => "Can I claim a property tax exemption for senior citizens?",
    //             "description" => "Discuss property tax exemptions for senior residents."
    //         ],
    //         [
    //             "questionId" => 35,
    //             "module" => "trade",
    //             "title" => "Is there a grace period for renewing a trade license after it expires?",
    //             "description" => "Explain the grace period for trade license renewal."
    //         ],
    //         [
    //             "questionId" => 36,
    //             "module" => "advertisement",
    //             "title" => "What are the rules regarding political campaign signage?",
    //             "description" => "Discuss regulations for political campaign advertisements."
    //         ],
    //         [
    //             "questionId" => 37,
    //             "module" => "water",
    //             "title" => "How can I inquire about the water rates and billing structure?",
    //             "description" => "Explain how residents can obtain information on water rates."
    //         ],
    //         [
    //             "questionId" => 38,
    //             "module" => "property",
    //             "title" => "Can I make partial property tax payments throughout the year?",
    //             "description" => "Discuss options for making partial property tax payments."
    //         ],
    //         [
    //             "questionId" => 39,
    //             "module" => "trade",
    //             "title" => "Is there a specific license for operating a home-based business?",
    //             "description" => "Explain the licensing requirements for home-based businesses."
    //         ],
    //         [
    //             "questionId" => 40,
    //             "module" => "advertisement",
    //             "title" => "What permits are needed for promotional events with temporary signage?",
    //             "description" => "Detail the permit requirements for temporary promotional signs."
    //         ],
    //         [
    //             "questionId" => 41,
    //             "module" => "water",
    //             "title" => "How can I report illegal water connections or theft?",
    //             "description" => "Provide information on reporting water theft incidents."
    //         ],
    //         [
    //             "questionId" => 42,
    //             "module" => "property",
    //             "title" => "What is the process for appealing property tax assessments?",
    //             "description" => "Explain how residents can appeal property tax assessments."
    //         ],
    //         [
    //             "questionId" => 43,
    //             "module" => "trade",
    //             "title" => "What are the consequences of operating a business without a trade license?",
    //             "description" => "Discuss the penalties for unlicensed business operations."
    //         ],
    //         [
    //             "questionId" => 44,
    //             "module" => "advertisement",
    //             "title" => "Are there specific guidelines for advertising near schools or hospitals?",
    //             "description" => "Detail regulations for advertisements near sensitive areas."
    //         ],
    //         [
    //             "questionId" => 45,
    //             "module" => "water",
    //             "title" => "How does the municipality handle water scarcity during peak demand periods?",
    //             "description" => "Explain strategies for managing water supply during high-demand seasons."
    //         ],
    //         [
    //             "questionId" => 46,
    //             "module" => "property",
    //             "title" => "Can I make property tax payments through automatic deductions from my bank account?",
    //             "description" => "Discuss automated property tax payment options."
    //         ],
    //         [
    //             "questionId" => 47,
    //             "module" => "trade",
    //             "title" => "What is the process for updating business information on a trade license?",
    //             "description" => "Explain how to update business details on a trade license."
    //         ],
    //         [
    //             "questionId" => 48,
    //             "module" => "advertisement",
    //             "title" => "How do I apply for a permit to install a digital advertising display?",
    //             "description" => "Detail the application process for digital advertising permits."
    //         ],
    //         [
    //             "questionId" => 49,
    //             "module" => "water",
    //             "title" => "What measures are in place to prevent water pollution in local rivers and streams?",
    //             "description" => "Explain initiatives to protect water bodies from pollution."
    //         ],
    //         [
    //             "questionId" => 50,
    //             "module" => "property",
    //             "title" => "Is there a property tax reduction for energy-efficient homes?",
    //             "description" => "Discuss property tax incentives for energy-efficient properties."
    //         ]
    //     ];
    //     // return collect($array)->first();
    //     collect($array)->map(function ($value, $key) {
    //         $mMGrievanceQuestion = new MGrievanceQuestion();
    //         $mMGrievanceQuestion->questions = $value['title'];
    //         $mMGrievanceQuestion->answers = $value['description'];
    //         $mMGrievanceQuestion->module = $value['module'];
    //         $mMGrievanceQuestion->save();
    //     });
    // }

    /**
     * | test the ump 
     */
    public function testUmps(Request $request)
    {
        $transferData = [
            "request" => [
                "action" => "cdrapi",
                "cookie" => "sid908136319-1695974155",
                "format" => "json",
                "callee" => "09421824647"
            ]
        ];
        $returnReq = Http::post("https://ucmserver.modernulb.com/api", $transferData);
        $httpReqData = json_decode($returnReq);

        if (!$httpReqData) {
            throw new Exception("Data not found or the api not responding! , $httpReqData");
        }
        return $httpReqData;
    }
}
