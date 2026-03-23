<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Modules\Announcement\Models\Announcement;
use Modules\Announcement\Models\AnnouncementComment;
use Carbon\Carbon;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = User::role('admin')->first();
        $members = User::role('member')->get();

        $announcements = [
            [
                'title'        => 'Welcome to CoopPay!',
                'body'         => 'Dear members, we are pleased to announce the launch of our new cooperative payment management platform. You can now log in to confirm your monthly deductions and track your loan repayment progress. Please ensure you confirm your deductions every month after salary payment.',
                'is_pinned'    => true,
                'published_at' => Carbon::now()->subMonths(4),
            ],
            [
                'title'        => 'December Salary Deductions',
                'body'         => 'Please be informed that December salary deductions have been processed by IPPIS. All members are required to log in and confirm their deductions before the 15th of this month. Contact the admin if you notice any discrepancies.',
                'is_pinned'    => false,
                'published_at' => Carbon::now()->subMonths(3),
            ],
            [
                'title'        => 'New Loan Applications Now Open',
                'body'         => 'The cooperative is now accepting new loan applications for the new year. Members who have completed their current repayment plan are eligible to apply. Please visit the admin office with your application form.',
                'is_pinned'    => false,
                'published_at' => Carbon::now()->subMonths(2),
            ],
            [
                'title'        => 'Important: Update Your Profile',
                'body'         => 'All members are required to update their profile information including phone number and next of kin details. This is mandatory for all cooperative members. Please do this before the end of the month.',
                'is_pinned'    => true,
                'published_at' => Carbon::now()->subMonths(1),
            ],
            [
                'title'        => 'March Deduction Reminder',
                'body'         => 'This is a reminder that March salary deductions will be processed by IPPIS shortly. Please ensure your account details with IPPIS are up to date. Log in to confirm your deduction once your salary is paid.',
                'is_pinned'    => false,
                'published_at' => Carbon::now(),
            ],
        ];

        foreach ($announcements as $data) {
            $announcement = Announcement::create([
                'user_id'      => $admin->id,
                'title'        => $data['title'],
                'body'         => $data['body'],
                'is_pinned'    => $data['is_pinned'],
                'published_at' => $data['published_at'],
            ]);

            // Add 2 comments from random members on each announcement
            $commentingMembers = $members->random(2);

            $comments = [
                'Thank you for the update.',
                'Noted, thank you sir.',
                'Confirmed, my deduction was made.',
                'Please can the admin check my account?',
                'Thank you for this information.',
                'Understood, will do.',
            ];

            foreach ($commentingMembers as $member) {
                AnnouncementComment::create([
                    'announcement_id' => $announcement->id,
                    'user_id'         => $member->id,
                    'body'            => $comments[array_rand($comments)],
                ]);
            }
        }
    }
}
