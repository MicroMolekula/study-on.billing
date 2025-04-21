<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Service\Email\StudyOnMailer;
use Symfony\Component\Mime\Email;

class RentNotificationsService
{
    public function __construct(
        private StudyOnMailer $mailer,
        private UserRepository $userRepository
    ) {
    }

    public function sendNotifications(): void
    {
        $courses = $this->userRepository->findUsersWithExpiresCourses();
        foreach ($courses as $user => $course) {
            $this->sendEmail($user, $course);
        }
    }

    /**
     * @param string $to
     * @param array<string, mixed> $data
     * @return void
     */
    private function sendEmail(string $to, array $data): void
    {
        $notify = $this->generateNotifications($data);
        $email = (new Email())
            ->to($to)
            ->subject('Уведомление об окончании аренды курсов')
            ->text($notify);
        $this->mailer->send($email);
    }

    private function generateNotifications(array $data): string
    {
        $notify = "Уважаемый клиент! У вас есть курсы, срок аренды которых подходит к концу:\n";
        foreach ($data as $item) {
            $date = date("d.m.Y H:i", $item['expires_at']->getTimestamp());
            $notify .= "'{$item['title']}' действует до $date.\n";
        }
        return $notify;
    }
}