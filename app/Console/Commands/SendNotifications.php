<?php

namespace App\Console\Commands;

use App\Models\Control;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deming:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for controls';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::debug('SendNotifications - Start.');

        // Need to send notifications today ?
        if ($this->needCheck()) {
            Log::debug('SendNotifications - notifications today');

            $controls = Control
                ::where('status', 0)
                    ->where('plan_date', '<=', Carbon::today()
                        ->addDays(intval(config('deming.notification.expire-delay')))->toDateString())
                    ->orderBy('plan_date')
                    ->count();

            Log::debug(
                'SendNotifications - ' .
                $controls .
                ' control(s) will expire within '.
                config('deming.notification.expire-delay') .
                ' days.'
            );

            // Loop on all users
            $users = User::all();
            foreach ($users as $user) {
                // get controls
                $controls = Control::where('status', 0)
                    ->join('control_user', 'control_id', '=', 'controls.id')
                    ->where('user_id', '=', $user->id)
                    ->where('plan_date', '<=', Carbon::today()
                        ->addDays(intval(config('deming.notification.expire-delay')))->toDateString())
                    ->orderBy('plan_date')
                    ->get();
                if ($controls->count() > 0) {
                    App::setlocale($user->language);
                    $txt = '';
                    foreach ($controls as $control) {
                        // Date
                        $txt .= '<a href="' . url('/bob/show/'. $control->id) . '">';
                        $txt .= '<b>';
                        if (strtotime($control->plan_date) >= strtotime('today')) {
                            $txt .= "<font color='green'>" . $control->plan_date .' </font>';
                        } else {
                            $txt .= "<font color='red'>" . $control->plan_date . '</font>';
                        }
                        $txt .= '</b>';
                        $txt .= '</a>';
                        // Space
                        $txt .= ' &nbsp; - &nbsp; ';
                        // Clauses
                        foreach ($control->measures as $measure) {
                            $txt .= '<a href="' . url('/alice/show/' . $measure->id) . '">'. htmlentities($measure->clause) . '</a>';
                            // Space
                            $txt .= ' &nbsp; ';
                        }
                        $txt .= ' - &nbsp; ';
                        // Name
                        $txt .= htmlentities($control->name);
                        $txt .= "<br>\n";
                    }

                    // Create message
                    $mail_from = config('deming.notification.mail-from');
                    $headers = [
                        'MIME-Version: 1.0',
                        'Content-type: text/html;charset=iso-8859-1',
                        'From: '. $mail_from,
                    ];
                    $to_email = $user->email;
                    $subject = config('deming.notification.mail-subject');

                    // Get message model
                    $message = config('deming.notification.mail-content');
                    if (($message==null)||(strlen($message)==0))
                        $message = trans('cruds.config.notifications.message_default_content');

                    // Replace %table% in message model
                    $message = str_replace("%table%",$txt,$message);

                    // Send mail
                    if (mail(
                        $to_email,
                        '=?UTF-8?B?' . base64_encode($subject) . '?=',
                        mb_convert_encoding($message, 'UTF-8', 'ISO-8859-1'),
                        implode("\r\n", $headers),
                        '-f ' .
                        $to_email
                    )
                    ) {
                        Log::debug('Mail sent to '.$to_email);
                    } else {
                        Log::debug('Email sending fail.');
                    }
                }
            }
        } else {
            Log::debug('SendNotifications - no notifications today');
        }

        Log::debug('SendNotifications - DONE.');
    }

    /**
     * return true if check is needed
     *
     * @return bool
     */
    private function needCheck()
    {
        $check_frequency = config('deming.notification.frequency');

        Log::debug('SendNotifications - frequency=' . $check_frequency . ' day=' . Carbon::today()->day . ' dayOfWeek=' . Carbon::today()->dayOfWeek);

        return ($check_frequency === '1') ||
            // Weekly
            (($check_frequency === '7') && (Carbon::today()->dayOfWeek === 1)) ||
            // Every two weeks
            (($check_frequency === '15') && ((Carbon::today()->day === 1) || (Carbon::today()->day === 15))) ||
            // Monthly
            (($check_frequency === '30') && (Carbon::today()->day === 1));
    }
}
