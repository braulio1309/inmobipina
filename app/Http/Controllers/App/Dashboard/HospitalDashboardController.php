<?php

namespace App\Http\Controllers\App\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\App\Dashboard\DashboardService;
use App\Services\App\Dashboard\HospitalDashboardService;
use App\Services\App\Dashboard\RealEstateDashboardService;
use Illuminate\Http\Request;

class HospitalDashboardController extends Controller
{
    protected $realEstateService;

    public function __construct(
        HospitalDashboardService $service, 
        DashboardService $dashboardService,
        RealEstateDashboardService $realEstateService
    )
    {
        $this->service = $service;
        $this->dashboardService = $dashboardService;
        $this->realEstateService = $realEstateService;
    }

    public function defaultData()
    {
        return $this->service->defaultData();
    }

    public function hospitalActivities(Request $request)
    {
        $date = $this->dashboardService->getDateRange($request->key);
        return $this->service->hospitalActivities($date['range']['to'], $date['range']['from'], $date['filter']);
    }

    public function patientStatistics()
    {
        return $this->service->patientStatistics();
    }

    public function doctorsList(Request $request)
    {
        return $this->service->doctorsList($request);
    }

    public function upComingAppointments(Request $request)
    {
        return $this->service->upComingAppointments($request);
    }

    // Real Estate Dashboard Methods
    public function getRealEstateData(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        return response()->json($this->realEstateService->getDashboardData($startDate, $endDate));
    }
}
