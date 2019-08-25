<?php
$systemId = "002c748b-9a37-47a2-9964-8a3e2eecbde0";

function RunReminders() {
    $bills = Bill::GetAll();

    $today = date('Y-m-d');
    foreach ($bills as $bill) {
        $days_until_due = (strtotime($bill->DueDate) - strtotime($today))/60/60/24;

        $notify = ($days_until_due <= 0) || ($days_until_due == 3);

        if (!$notify)
            continue;
        error_log(json_encode($bill));
        // Send notification
        Notifier::LateBillNotification($bill, $days_until_due <= 0);
    }
}

function RunNewBill($data) {

}

class ScheduledJob {
    public $Id;
    public $Key;
    public $Frequency;
    public $RunOn;
    public $LastRan;
    public $Data;

    public function Run() {
        //$this->LastRan = date('Y-m-d H:i:s');
        #echo "Running " . $this->Key;
        switch ($this->Key) {
            case "new-bill":
                RunNewBill($this->Data);
                break;

            case "reminders":
                RunReminders();
                break;
        }

        // Update the last ran time in the object store
        $me = [
            'Key' => $this->Key,
            'Frequency' => $this->Frequency,
            'RunOn' => $this->RunOn,
            'LastRan' => date('Y-m-d H:i:s'),
            'Data' => $this->Data
        ];
        ObjectStore::Save('job', $this->Id, $me);
    }

    public static function Parse($id, $raw) {
        if (is_array($raw))
            $raw = json_decode(json_encode($raw)); // Convert to object

        $job = new ScheduledJob();
        $job->Id = $id;
        $job->Key = $raw->Key;
        $job->Frequency = $raw->Frequency;
        $job->RunOn = $raw->RunOn;
        $job->Data = $raw->Data;

        return $job;
    }

    public static function GetAll() {
        $schedules = ObjectStore::GetKeysForScope('job');

        $jobs = [];
        foreach ($schedules as $jobId)
        {
            $job = ObjectStore::Get('job', $jobId);
            array_push($jobs, ScheduledJob::Parse($jobId, $job));
        }

        return $jobs;
    }
}


class Scheduler {

    public static function RunScheduledItems()
    {
        $jobs = ScheduledJob::GetAll();
        $today = date('d');

        foreach ($jobs as $job) {
            if ($today == $job->RunOn || $job->RunOn == 'daily') {
                $job->Run();
            }
        }
    }
}