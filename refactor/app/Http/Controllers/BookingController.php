<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */

    protected $repository;

    /**
     * @var $userId
     */

    protected $userId;

    /**
     * @var $authenticatedUser
     */

    protected $authenticatedUser;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */

    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
        $request = request();
        $this->authenticatedUser = $request->__authentecatedUser ;
        $this->userId = $request->user_id ;

    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function index(Request $request)
    {
        if ($this->userId == $request->get('user_id')) {
            $response = $this->repository->getUsersJobs($this->userId);
        } else if ($this->authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $this->authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */

    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function store(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->store($this->authenticatedUser, $data);
        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */

    public function update($id, Request $request)
    {
        $data = $request->all();
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $this->authenticatedUser);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->storeJobEmail($data);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function getHistory(Request $request)
    {
        if ($this->userId == $request->get('user_id')) {
            $response = $this->repository->getUsersJobsHistory($this->userId, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->acceptJob($data, $this->authenticatedUser);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $response = $this->repository->acceptJobWithId($data, $this->authenticatedUser);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->cancelJobAjax($data, $this->authenticatedUser);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function endJob(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->endJob($data);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function customerNotCall(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->customerNotCall($data);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */

    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs($this->authenticatedUser);
        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $affectedRows = null;
        $affectedRows1 = null;
        $distance = "";
        $time = "";
        $jobId = "";
        $session = "";
        $flagged = false;
        $manuallyHandled = "no";
        $byAdmin = "no";
        $adminComment = "";

        if (isset($data['job_id']) && !empty($data['job_id'])) {
            $jobId = $data['job_id'];
        }

        if (isset($data['distance']) && !empty($data['distance'])) {
            $distance = $data['distance'];
        }

        if (isset($data['time']) && !empty($data['time'])) {
            $time = $data['time'];
        }

        if (isset($data['session_time']) && !empty($data['session_time'])) {
            $session = $data['session_time'];
        }

        if ($data['flagged'] == true) {
            if ($data['admin_comment'] == '') {
                return response("Please, add comment");
            }
            $flagged = 'yes';
        }

        if ($data['manually_handled'] == 'true') {
            $manuallyHandled = 'yes';
        }

        if ($data['by_admin'] == 'true') {
            $byAdmin = 'yes';
        }

        if (isset($data['admin_comment']) && $data['admin_comment'] != "") {
            $adminComment = $data['admin_comment'];
        }

        if ($jobId && ($time || $distance)) {
            $affectedRows = Distance::where('job_id', '=', $jobId)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($jobId && ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin)) {
            $affectedRows1 = Job::where('id', '=', $jobId)->update(array('admin_comments' => $adminComment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manuallyHandled, 'by_admin' => $byAdmin));
        }

        if ($affectedRows || $affectedRows1) {
            return response('Record updated!');
        } else {
            return response('Nothing to Update!');
        }
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);
        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['job_id']);
        $jobData = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $jobData, '*');
        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => 'Something went wrong ! please try again.']);
        }
    }

}