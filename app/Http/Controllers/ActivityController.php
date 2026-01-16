<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Activity;
use App\Filters\Common\Auth\ActivityFilter as AppUserFilter;
use App\Filters\Core\ActivityFilter;
use App\Services\Core\Auth\ActivityService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;


class ActivityController extends Controller
{

    public function __construct(ActivityService $Transaction, ActivityFilter $filter)
    {
        $this->service = $Transaction;
        $this->filter = $filter;
    }

    public function listado()
    {
        $user = Auth()->user();
        return (new AppUserFilter(
            $this->service->with('user')
                ->filters($this->filter)
                ->latest()
        ))->filter()
            ->paginate(request()->get('per_page', 10));
    }

    public function create(Request $request)
    {
        $Activity = Activity::create([
            'result' => $request->input('result'),
            'type' => $request->input('type'),
            'user_id' => Auth::user()->id,
            'date' => Carbon::parse($request->input('date')),
        ]);
        return created_responses('Activity');
    }

    public function edit(Request $request, $id)
    {

        $Activity = Activity::where('id', $id)->first();
        $Activity->update($request->all());

        return created_responses('Transaction');
    }

    public function show(Activity $Activity)
    {
        return response()->json($Activity);
    }
}
