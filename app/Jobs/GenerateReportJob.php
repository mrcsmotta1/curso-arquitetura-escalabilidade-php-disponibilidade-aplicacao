<?php

namespace App\Jobs;

use App\Mail\ReportGenerated;
use App\Models\Specialist;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $userEmail)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $specialists = Specialist::select(
            ['specialists.id', 'specialists.name', DB::raw('avg(reviews.rating) avg_rating')]
        )
            ->join('reviews', 'reviews.specialist_id', '=', 'specialists.id')
            ->groupBy(['specialists.id', 'specialists.name'])
            ->orderBy('avg_rating', 'desc')
            ->limit(10)
            ->get();

        $fileName = 'highest_rated_specialists_' . time() . '.csv';
        $columns = 'Specialists ID,Specialists Name,Reviews Rating';
        $disk = Storage::disk('public');
        $disk->append($fileName, $columns);
        foreach ($specialists as $specialist) {
            $content = "{$specialist['id']},{$specialist['name']},{$specialist['avg_rating']}";
            $disk->append($fileName, $content);
        }

        $url = env('APP_URL') . Storage::url($fileName);
        Mail::to($this->userEmail)
            ->send(new ReportGenerated($url));
    }
}
