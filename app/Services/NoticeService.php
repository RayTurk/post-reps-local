<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\Notice;
use App\Models\NoticeAcknowledgement;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class NoticeService {

    public function getActive() {
        return Notice::query()
            ->where("end_date", ">=", Carbon::today())
            ->orderBy('start_date', 'DESC')
            ->get();
    }

    public function getExpired()
    {
        return Notice::query()
            ->whereDate("end_date", "<", Carbon::today())
            ->orderBy('end_date', 'DESC')
            ->get();
    }

    public function getNotice($id)
    {
        return Notice::findOrFail($id);
    }

    public function getTodayNotices()
    {
        return Notice::query()
            ->where("start_date", "<=", Carbon::today())
            ->where("end_date", ">=", Carbon::today())
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function create($data)
    {
        return Notice::create($data);
    }

    public function update($data, $id)
    {
        $notice = Notice::findOrFail($id);

        $notice->start_date = $data['start_date'];
        $notice->end_date = $data['end_date'];
        $notice->subject = $data['subject'];
        $notice->details = $data['details'];

        $notice->save();
    }

    public function delete($id)
    {
        $notice = Notice::findOrFail($id);

        Schema::disableForeignKeyConstraints();
        $notice->delete();
        Schema::enableForeignKeyConstraints();
    }

    public function getLatestNotice()
    {
        $latestNotice = Notice::latest('created_at')
            ->where("start_date", "<=", Carbon::today())
            ->where("end_date", ">=", Carbon::today())
            ->first();

        //If no latest notice then return null
        if ( ! $latestNotice ) {
            return null;
        }

        $isAcknowledged = NoticeAcknowledgement::where('user_id', auth()->user()->id)
            ->where('notice_id', $latestNotice->id)
            ->first();

        if (! $isAcknowledged) {
            return $latestNotice;
        }

        return null;
    }

    public function acknowledgeNotice($notice)
    {

        $today = Carbon::now()->format('m/d/Y g:i A');

        $acknowledgedNotice = NoticeAcknowledgement::create([
            'user_id' => auth()->id(),
            'notice_id' => $notice->id
        ]);

        if (auth()->user()->agent) {
            $agent = Agent::find(auth()->user()->agent->id);

            if ($agent->note) {
                $agent->note .= "\nAcknowledged notice '$notice->subject' on $today\n";
            } else {
                $agent->note .= "Acknowledged notice '$notice->subject' on $today\n";
            }
            $agent->save();

            return $acknowledgedNotice;
        }

        $office = Office::find(auth()->user()->office->id);

        if ($office->note) {
            $office->note .= "\nAcknowledged notice '$notice->subject' on $today\n";
        } else {
            $office->note .= "Acknowledged notice '$notice->subject' on $today\n";
        }
        $office->save();

        return $acknowledgedNotice;
    }

}
