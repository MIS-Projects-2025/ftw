<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Services\FtwService;
use App\Services\HrisApiService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FtwController extends Controller
{
    public function __construct(
        protected FtwService $service,
        protected HrisApiService $hris,
    ) {}



    public function create()
    {
        $formData  = $this->service->getFormData();
        $empData   = session('emp_data');
        $empId     = (int) ($empData['emp_id'] ?? 0);
        $isClinic  = (int) ($empData['emp_station_id'] ?? 0) === 39;
        $isSupervisor = ! $isClinic && $empId > 0
            && count($this->hris->fetchDirectReports($empId)) > 0;

        return Inertia::render('Ftw/CreateFtw', [
            'recommendations'   => $formData['recommendations'],
            'canSelectEmployee' => $isClinic || $isSupervisor,
        ]);
    }


    public function store(Request $request)
    {
        $empData     = session('emp_data', []);
        $empId       = (int) ($empData['emp_id'] ?? 0);
        $isClinic    = (int) ($empData['emp_station_id'] ?? 0) === 39;
        $isSupervisor = ! $isClinic && $empId > 0
            && count($this->hris->fetchDirectReports($empId)) > 0;

        // Regular employees submit for themselves
        if (! $isClinic && ! $isSupervisor) {
            $request->merge(['emp_no' => $empId]);
        }

        $rec = (int) $request->input('recommendation', 0);

        $validated = $request->validate([
            'emp_no'          => 'required|integer|min:1',
            'emp_name'        => 'nullable|string|max:255',
            'emp_dept'        => 'nullable|string|max:255',
            'emp_team'        => 'nullable|string|max:255',
            'recommendation'  => 'required|integer|exists:recommendation_ref,rec_id',
            // Fit to Work
            'emp_time_in'     => 'required_if:recommendation,1|nullable|date_format:H:i',
            'emp_diagnose'    => 'required_unless:recommendation,5|nullable|string|max:1000',
            'emp_shift'       => [
                Rule::requiredIf(in_array($rec, [1, 2, 3, 5])),
                'nullable',
                Rule::in(['1', '2', '3']),
            ],
            'absence_dates'   => 'required_if:recommendation,1|nullable|array|min:1',
            'absence_dates.*' => 'date',
            'absent_count'    => 'nullable|integer|min:0',
            'ftw_file'        => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            // SDH (Sent Home / Hospital / Unfit)
            'sdh_date'        => 'required_if:recommendation,2,3,5|nullable|date',
            'sdh_time'        => 'required_if:recommendation,2,3|nullable|date_format:H:i',
            // Unfit to Work
            'remarks'         => 'required_if:recommendation,5|nullable|string|max:1000',
            // Rest
            'rest_date'       => 'required_if:recommendation,4|nullable|date',
            'rest_time_in'    => 'required_if:recommendation,4|nullable|date_format:H:i',
        ]);

        // Handle file upload — save to ../uploads/ftw_upload/{emp_no}/
        $filePath = null;
        if ($request->hasFile('ftw_file') && $request->file('ftw_file')->isValid()) {
            $file      = $request->file('ftw_file');
            $empNo     = $validated['emp_no'];
            $rec       = $validated['recommendation'];
            $datestamp = now()->format('Ymd');
            $ext       = strtolower($file->getClientOriginalExtension() ?: 'pdf');
            $filename  = "{$datestamp}_{$empNo}_{$rec}.{$ext}";
            $dir       = public_path("../uploads/ftw_upload/{$empNo}");

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $filename);
            $filePath = "../uploads/ftw_upload/{$empNo}/{$filename}";
        }

        $this->service->store(
            array_merge($validated, ['ftw_file_link' => $filePath]),
            $empData,
        );

        return redirect()->route('ftw.create');
    }


    public function searchEmployees(Request $request)
    {
        $search = (string) $request->query('search', '');
        $page   = max(1, (int) $request->query('page', 1));
        $result = $this->hris->fetchActiveEmployees($search, $page, 20);
        // dd($result);
        return response()->json($result);
    }
}
