<?php

namespace App\Pipelines\Grievance;

use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use Carbon\Carbon;
use Closure;
use Exception;

/**
 * | Created On- 21-04-2023 
 * | Created By- Sam kerketta
 * | -------------------------------------------
 * | PipeLine Class to get user details for property
 */
class SearchProperty
{

    private $_mActiveCitizenUnderCares;
    private $_mPropProperty;
    private $_currentDate;
    private $_mPropOwner;
    private $_propertyId;

    /**
     * | Initializing Master Values
     */
    public function __construct()
    {
        $this->_mPropOwner = new PropOwner();
        $this->_
    }
    public function handle($request, Closure $next)
    {

        if (request()->input('filterBy') != 1) {
            return $next($request);
        }

        $referenceNo = request()->input('referenceNo');
        $property = $this->_mPropProperty->getPropByPtnOrHolding($referenceNo);
        $this->_propertyId = $property->id;
        $this->isPropertyAlreadyTagged();           // function (1.1)
        $propOwner = $this->_mPropOwner->getfirstOwner($property->id);
        $underCareReq = [
            'property_id' => $property->id,
            'date_of_attachment' => $this->_currentDate,
            'mobile_no' => $propOwner->mobile_no,
            'citizen_id' => auth()->user()->id
        ];
        $this->_mActiveCitizenUnderCares->store($underCareReq);
        return "Property Successfully Tagged";
    }
}
